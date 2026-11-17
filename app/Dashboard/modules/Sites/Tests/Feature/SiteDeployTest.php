<?php

use App\Dashboard\Modules\Sites\Entities\Site;
use App\Dashboard\Modules\Sites\UseCases\SiteRepository;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

const TAGS_URL = 'https://api.github.com/repos/acme/paylance/tags';
const DISPATCH_URL = 'https://api.github.com/repos/acme/paylance/actions/workflows/deploy-on-main.yml/dispatches';

beforeEach(function () {
    $directory = storage_path('framework/testing/deploy');
    if (! is_dir($directory)) {
        mkdir($directory, 0777, true);
    }

    $sitesPath = $directory.'/sites.json';
    if (file_exists($sitesPath)) {
        unlink($sitesPath);
    }

    config()->set('launchpad.sites_path', $sitesPath);
    config()->set('launchpad.screenshots_path', $directory.'/screenshots');
    config()->set('launchpad.deploy', [
        'repository' => 'acme/paylance',
        'workflow' => 'deploy-on-main.yml',
        'token' => 'test-token',
    ]);

    app(SiteRepository::class)->save(new Site(
        id: 'rasangi',
        name: 'Rasangi Electricals',
        baseUrl: 'https://app.rasangielectricals.com',
        versionPath: '/api/version',
        status: 'live',
        version: 'v1.0.1',
        screenshotPath: null,
        checkedAt: null,
        client: 'rasangi',
    ));
    app(SiteRepository::class)->save(new Site('textifys', 'Textifys', 'https://app.textifys.com', null, 'live', null, null, null));
});

/**
 * Fake GitHub: two pages of tags and an accepting dispatch endpoint.
 */
function fakeGitHub(int $dispatchStatus = 204, array $dispatchBody = [], ?string $onlyFailClient = null): void
{
    Http::fake(function (Request $request) use ($dispatchStatus, $dispatchBody, $onlyFailClient) {
        if (str_starts_with($request->url(), TAGS_URL)) {
            return str_contains($request->url(), 'page=2')
                ? Http::response([['name' => 'v1.0.0']])
                : Http::response(
                    [['name' => 'v1.0.1'], ['name' => 'v1.0.2']],
                    200,
                    ['Link' => '<'.TAGS_URL.'?per_page=100&page=2>; rel="next"'],
                );
        }

        if ($request->url() === DISPATCH_URL) {
            if ($onlyFailClient !== null && $request->data()['inputs']['client'] !== $onlyFailClient) {
                return Http::response(null, 204);
            }

            return Http::response($dispatchBody, $dispatchStatus);
        }

        return Http::response(null, 404);
    });
}

/**
 * Only GitHub matters here: Inertia may also probe a running Vite dev server for SSR.
 */
function assertGitHubNotCalled(): void
{
    Http::assertNotSent(fn (Request $request) => str_starts_with($request->url(), 'https://api.github.com/'));
}

test('lists every tag in the repository, newest first', function () {
    fakeGitHub();
    $this->actingAs(User::factory()->create());

    $this->getJson(route('deploy.tags'))
        ->assertOk()
        ->assertExactJson(['tags' => ['v1.0.2', 'v1.0.1', 'v1.0.0']]);

    Http::assertSent(fn (Request $request) => str_starts_with($request->url(), TAGS_URL)
        && $request->hasHeader('Authorization', 'Bearer test-token'));
});

test('deploying runs the workflow with the tag, client folder and test choice', function () {
    fakeGitHub();
    $this->actingAs(User::factory()->create(['name' => 'Charitha']));

    $this->post(route('sites.deploy', 'rasangi'), ['ref' => 'v1.0.2', 'run_tests' => true])
        ->assertRedirect(route('dashboard'))
        ->assertInertiaFlash('toast', [
            'type' => 'success',
            'message' => 'Deploying v1.0.2 to rasangi.',
            'link' => [
                'label' => 'View on GitHub',
                'url' => 'https://github.com/acme/paylance/actions/workflows/deploy-on-main.yml',
            ],
        ]);

    Http::assertSent(fn (Request $request) => $request->url() === DISPATCH_URL
        && $request->method() === 'POST'
        && $request->hasHeader('Authorization', 'Bearer test-token')
        && $request->data() === ['ref' => 'v1.0.2', 'inputs' => ['client' => 'rasangi', 'run_tests' => 'yes']]);

    $site = app(SiteRepository::class)->find('rasangi');

    expect($site?->lastDeploy)->toMatchArray(['ref' => 'v1.0.2', 'run_tests' => true, 'by' => 'Charitha'])
        ->and($site?->present()['last_deploy']['ref'])->toBe('v1.0.2');
});

test('tests can be skipped', function () {
    fakeGitHub();
    $this->actingAs(User::factory()->create());

    $this->post(route('sites.deploy', 'rasangi'), ['ref' => 'v1.0.1', 'run_tests' => false]);

    Http::assertSent(fn (Request $request) => $request->url() === DISPATCH_URL
        && $request->data()['inputs']['run_tests'] === 'no');
});

test('only tags that exist in the repository can be deployed', function () {
    fakeGitHub();
    $this->actingAs(User::factory()->create());

    $this->post(route('sites.deploy', 'rasangi'), ['ref' => 'main; rm -rf', 'run_tests' => true])
        ->assertSessionHasErrors('ref');

    Http::assertNotSent(fn (Request $request) => $request->url() === DISPATCH_URL);
});

test('a site without a client folder cannot be deployed', function () {
    fakeGitHub();
    $this->actingAs(User::factory()->create());

    $this->post(route('sites.deploy', 'textifys'), ['ref' => 'v1.0.2', 'run_tests' => true])
        ->assertRedirect(route('dashboard'))
        ->assertInertiaFlash('toast', ['type' => 'error', 'message' => 'Textifys has no client folder, so it can\'t be deployed.']);

    Http::assertNotSent(fn (Request $request) => $request->url() === DISPATCH_URL);
});

test('a GitHub error is reported and nothing is recorded', function () {
    fakeGitHub(422, ['message' => 'Unexpected inputs provided: ["client"]']);
    $this->actingAs(User::factory()->create());

    $this->post(route('sites.deploy', 'rasangi'), ['ref' => 'v1.0.2', 'run_tests' => true])
        ->assertRedirect(route('dashboard'))
        ->assertInertiaFlash('toast', [
            'type' => 'error',
            'message' => 'GitHub refused the deploy: Unexpected inputs provided: ["client"]',
        ]);

    expect(app(SiteRepository::class)->find('rasangi')?->lastDeploy)->toBeNull();
});

test('deploying is switched off until a repository and token are configured', function () {
    config()->set('launchpad.deploy.token', null);
    Http::fake();
    $this->actingAs(User::factory()->create());

    $this->get(route('dashboard'))->assertInertia(fn ($page) => $page->where('deployEnabled', false));
    $this->getJson(route('deploy.tags'))->assertStatus(503);
    $this->post(route('sites.deploy', 'rasangi'), ['ref' => 'v1.0.2', 'run_tests' => true])->assertStatus(503);

    assertGitHubNotCalled();
});

test('the dashboard says deploying is available when configured', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('dashboard'))->assertInertia(fn ($page) => $page
        ->where('deployEnabled', true)
        ->where('sites.0.client', 'rasangi')
        ->where('sites.1.client', null));
});

test('guests cannot list tags or deploy', function () {
    Http::fake();

    $this->getJson(route('deploy.tags'))->assertUnauthorized();
    $this->post(route('sites.deploy', 'rasangi'), ['ref' => 'v1.0.2'])->assertRedirect(route('login'));

    assertGitHubNotCalled();
});

test('the client folder is saved when adding and editing a site', function () {
    $this->actingAs(User::factory()->create());

    $this->post(route('sites.store'), [
        'name' => 'PhoneX',
        'base_url' => 'https://app.phonex.lk',
        'client' => ' phonex ',
    ]);

    $phonex = collect(app(SiteRepository::class)->all())->firstWhere('name', 'PhoneX');
    expect($phonex?->client)->toBe('phonex');

    $this->put(route('sites.update', $phonex->id), [
        'name' => 'PhoneX',
        'base_url' => 'https://app.phonex.lk',
        'client' => '',
    ]);

    expect(app(SiteRepository::class)->find($phonex->id)?->client)->toBeNull();
});

test('a client folder must be a plain folder name', function () {
    $this->actingAs(User::factory()->create());

    $this->post(route('sites.store'), [
        'name' => 'Bad',
        'base_url' => 'https://bad.test',
        'client' => '../etc',
    ])->assertSessionHasErrors('client');
});

// --- Bulk deploy ----------------------------------------------------------

function addPaylanceSite(): void
{
    app(SiteRepository::class)->save(new Site(
        id: 'paylance',
        name: 'Paylance',
        baseUrl: 'https://paylance.test',
        versionPath: '/api/version',
        status: 'live',
        version: 'v1.0.2',
        screenshotPath: null,
        checkedAt: null,
        client: 'paylance',
    ));
}

/**
 * @return list<string> the client folders that were dispatched, in order
 */
function dispatchedClients(): array
{
    return Http::recorded()
        ->filter(fn (array $pair) => $pair[0]->url() === DISPATCH_URL)
        ->map(fn (array $pair) => $pair[0]->data()['inputs']['client'])
        ->values()
        ->all();
}

test('bulk deploy runs the workflow once per selected site with the same tag and test choice', function () {
    addPaylanceSite();
    fakeGitHub();
    $this->actingAs(User::factory()->create(['name' => 'Charitha']));

    $this->post(route('deploy.bulk'), ['ref' => 'v1.0.2', 'run_tests' => false, 'sites' => ['rasangi', 'paylance']])
        ->assertRedirect(route('dashboard'))
        ->assertInertiaFlash('toast', [
            'type' => 'success',
            'message' => 'Deploying v1.0.2 to 2 sites.',
            'link' => [
                'label' => 'View on GitHub',
                'url' => 'https://github.com/acme/paylance/actions/workflows/deploy-on-main.yml',
            ],
        ]);

    expect(dispatchedClients())->toBe(['rasangi', 'paylance']);

    Http::assertSent(fn (Request $request) => $request->url() === DISPATCH_URL
        && $request->data()['ref'] === 'v1.0.2'
        && $request->data()['inputs']['run_tests'] === 'no');

    // Tags are fetched once for the whole batch, not once per site.
    expect(Http::recorded()->filter(fn (array $pair) => str_starts_with($pair[0]->url(), TAGS_URL.'?per_page=100') && ! str_contains($pair[0]->url(), 'page=2'))->count())->toBe(1);

    $repository = app(SiteRepository::class);
    expect($repository->find('rasangi')?->lastDeploy)->toMatchArray(['ref' => 'v1.0.2', 'run_tests' => false, 'by' => 'Charitha'])
        ->and($repository->find('paylance')?->lastDeploy)->toMatchArray(['ref' => 'v1.0.2']);
});

test('bulk deploy only touches the selected sites', function () {
    addPaylanceSite();
    fakeGitHub();
    $this->actingAs(User::factory()->create());

    $this->post(route('deploy.bulk'), ['ref' => 'v1.0.1', 'run_tests' => true, 'sites' => ['paylance']])
        ->assertInertiaFlash('toast', [
            'type' => 'success',
            'message' => 'Deploying v1.0.1 to 1 site.',
            'link' => [
                'label' => 'View on GitHub',
                'url' => 'https://github.com/acme/paylance/actions/workflows/deploy-on-main.yml',
            ],
        ]);

    expect(dispatchedClients())->toBe(['paylance'])
        ->and(app(SiteRepository::class)->find('rasangi')?->lastDeploy)->toBeNull();
});

test('bulk deploy rejects a tag that is not in the repository', function () {
    addPaylanceSite();
    fakeGitHub();
    $this->actingAs(User::factory()->create());

    $this->post(route('deploy.bulk'), ['ref' => 'not-a-tag', 'run_tests' => true, 'sites' => ['rasangi', 'paylance']])
        ->assertSessionHasErrors('ref');

    expect(dispatchedClients())->toBe([]);
});

test('one failing site does not stop the others', function () {
    addPaylanceSite();
    fakeGitHub(422, ['message' => 'Unexpected inputs provided'], onlyFailClient: 'rasangi');
    $this->actingAs(User::factory()->create());

    $this->post(route('deploy.bulk'), ['ref' => 'v1.0.2', 'run_tests' => true, 'sites' => ['rasangi', 'paylance']])
        ->assertRedirect(route('dashboard'))
        ->assertInertiaFlash('toast', [
            'type' => 'warning',
            'message' => 'Started 1 of 2 deploys of v1.0.2. Not started: Rasangi Electricals (Unexpected inputs provided).',
            'link' => [
                'label' => 'View on GitHub',
                'url' => 'https://github.com/acme/paylance/actions/workflows/deploy-on-main.yml',
            ],
        ]);

    $repository = app(SiteRepository::class);
    expect(dispatchedClients())->toBe(['rasangi', 'paylance'])
        ->and($repository->find('rasangi')?->lastDeploy)->toBeNull()
        ->and($repository->find('paylance')?->lastDeploy)->toMatchArray(['ref' => 'v1.0.2']);
});

test('a selected site without a client folder is reported, not deployed', function () {
    addPaylanceSite();
    fakeGitHub();
    $this->actingAs(User::factory()->create());

    $this->post(route('deploy.bulk'), ['ref' => 'v1.0.2', 'run_tests' => true, 'sites' => ['textifys', 'paylance']])
        ->assertInertiaFlash('toast', [
            'type' => 'warning',
            'message' => 'Started 1 of 2 deploys of v1.0.2. Not started: Textifys (no client folder).',
            'link' => [
                'label' => 'View on GitHub',
                'url' => 'https://github.com/acme/paylance/actions/workflows/deploy-on-main.yml',
            ],
        ]);

    expect(dispatchedClients())->toBe(['paylance']);
});

test('when every site fails the deploy is reported as an error', function () {
    fakeGitHub(500, ['message' => 'Server Error']);
    $this->actingAs(User::factory()->create());

    $this->post(route('deploy.bulk'), ['ref' => 'v1.0.2', 'run_tests' => true, 'sites' => ['rasangi']])
        ->assertInertiaFlash('toast', [
            'type' => 'error',
            'message' => 'No deploys started. Rasangi Electricals (Server Error).',
        ]);
});

test('bulk deploy needs at least one site', function () {
    fakeGitHub();
    $this->actingAs(User::factory()->create());

    $this->post(route('deploy.bulk'), ['ref' => 'v1.0.2', 'run_tests' => true, 'sites' => []])
        ->assertSessionHasErrors('sites');

    assertGitHubNotCalled();
});

test('bulk deploy is off until configured and closed to guests', function () {
    Http::fake();

    $this->post(route('deploy.bulk'), ['ref' => 'v1.0.2', 'sites' => ['rasangi']])->assertRedirect(route('login'));

    config()->set('launchpad.deploy.token', null);
    $this->actingAs(User::factory()->create())
        ->post(route('deploy.bulk'), ['ref' => 'v1.0.2', 'run_tests' => true, 'sites' => ['rasangi']])
        ->assertStatus(503);

    assertGitHubNotCalled();
});
