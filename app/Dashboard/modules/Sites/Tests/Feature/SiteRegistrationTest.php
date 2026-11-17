<?php

use App\Dashboard\Modules\Sites\IO\Screenshots\SiteScreenshotter;
use App\Dashboard\Modules\Sites\UseCases\CheckOutcome;
use App\Dashboard\Modules\Sites\UseCases\SiteChecker;
use App\Dashboard\Modules\Sites\UseCases\VersionReader;
use App\Models\User;

beforeEach(function () {
    $directory = storage_path('framework/testing/sites');
    if (! is_dir($directory)) {
        mkdir($directory, 0777, true);
    }

    $sitesPath = $directory.'/sites.json';
    if (file_exists($sitesPath)) {
        unlink($sitesPath);
    }

    config()->set('launchpad.sites_path', $sitesPath);
    config()->set('launchpad.screenshots_path', $directory.'/screenshots');

    $this->app->instance(SiteChecker::class, new class implements SiteChecker
    {
        public function check(string $url): CheckOutcome
        {
            return new CheckOutcome(status: 'live', durationMs: 120, attempts: 1);
        }
    });

    $this->app->instance(VersionReader::class, new class implements VersionReader
    {
        public function read(string $url): ?string
        {
            return '9.9.9';
        }
    });

    $this->app->instance(SiteScreenshotter::class, new class implements SiteScreenshotter
    {
        public function capture(string $url, string $destination): bool
        {
            if (! is_dir(dirname($destination))) {
                mkdir(dirname($destination), 0777, true);
            }

            file_put_contents($destination, 'png');

            return true;
        }
    });
});

test('guests cannot view the dashboard', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('an authenticated user can register a site', function () {
    $this->actingAs(User::factory()->create());

    $this->post(route('sites.store'), [
        'name' => 'Paylance',
        'base_url' => 'https://paylance.test',
        'version_path' => '/version',
    ])
        ->assertRedirect(route('dashboard'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Paylance added.']);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('refresh', false)
            ->where('checkSite', fn ($id) => is_string($id) && $id !== '')
            ->has('sites', 1)
            ->where('sites.0.name', 'Paylance')
            ->where('sites.0.base_url', 'https://paylance.test')
            ->where('sites.0.version_path', '/version')
            ->where('sites.0.version', null));

    $this->post(route('sites.refresh-all'))->assertRedirect(route('dashboard'));

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('refresh', false)
            ->where('sites.0.status', 'live')
            ->where('sites.0.version', '9.9.9')
            ->where('sites.0.response_ms', 120)
            ->where('sites.0.preview_url', fn ($url) => is_string($url) && str_contains($url, '/preview')));
});

test('the version endpoint path is optional', function () {
    $this->actingAs(User::factory()->create());

    $this->post(route('sites.store'), [
        'name' => 'Glint',
        'base_url' => 'https://glint.test',
        'version_path' => '',
    ])->assertRedirect(route('dashboard'));

    $this->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('sites.0.version_path', null)
            ->where('sites.0.version', null));
});

test('registration rejects a base url that is not https', function () {
    $this->actingAs(User::factory()->create());

    $this->post(route('sites.store'), [
        'name' => 'Local',
        'base_url' => 'http://local.test',
    ])->assertSessionHasErrors(['base_url' => 'The base URL must start with https://.']);
});

test('a rejected form does not re-check every site', function () {
    $this->actingAs(User::factory()->create());

    $this->post(route('sites.store'), ['name' => 'Seed', 'base_url' => 'https://seed.test']);
    $this->get(route('dashboard'));

    $this->from(route('dashboard'))
        ->post(route('sites.store'), ['name' => 'Local', 'base_url' => 'http://local.test'])
        ->assertRedirect(route('dashboard'));

    $this->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('refresh', false)
            ->where('checkSite', null));
});

test('opening the dashboard checks every site', function () {
    $this->actingAs(User::factory()->create());

    $this->post(route('sites.store'), ['name' => 'Seed', 'base_url' => 'https://seed.test']);
    $this->get(route('dashboard'));

    $this->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('refresh', true)
            ->where('checkSite', null));
});

test('guests cannot register a site', function () {
    $this->post(route('sites.store'), [
        'name' => 'Paylance',
        'base_url' => 'https://paylance.test',
    ])->assertRedirect(route('login'));
});
