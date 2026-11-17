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
function fakeGitHub(int $dispatchStatus = 204, array $dispatchBody = []): void
{
    Http::fake(function (Request $request) use ($dispatchStatus, $dispatchBody) {
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
