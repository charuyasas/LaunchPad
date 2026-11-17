<?php

use App\Dashboard\Modules\Sites\Entities\Site;
use App\Dashboard\Modules\Sites\IO\Screenshots\SiteScreenshotter;
use App\Dashboard\Modules\Sites\UseCases\CheckOutcome;
use App\Dashboard\Modules\Sites\UseCases\SiteChecker;
use App\Dashboard\Modules\Sites\UseCases\SiteRepository;
use App\Dashboard\Modules\Sites\UseCases\VersionReader;
use App\Models\User;

beforeEach(function () {
    $directory = storage_path('framework/testing/refresh');
    if (! is_dir($directory)) {
        mkdir($directory, 0777, true);
    }

    $sitesPath = $directory.'/sites.json';
    if (file_exists($sitesPath)) {
        unlink($sitesPath);
    }

    config()->set('launchpad.sites_path', $sitesPath);
    config()->set('launchpad.screenshots_path', $directory.'/screenshots');

    $this->checkLog = new ArrayObject;
    $log = $this->checkLog;
    $this->app->instance(SiteChecker::class, new class($log) implements SiteChecker
    {
        public function __construct(private ArrayObject $log) {}

        public function check(string $url): CheckOutcome
        {
            $this->log->append($url);

            return new CheckOutcome(status: 'slow', durationMs: 1800, attempts: 1);
        }
    });

    $this->app->instance(VersionReader::class, new class implements VersionReader
    {
        public function read(string $url): ?string
        {
            return '3.1.0';
        }
    });

    $this->app->instance(SiteScreenshotter::class, new class implements SiteScreenshotter
    {
        public function capture(string $url, string $destination): bool
        {
            return false;
        }
    });
});

test('refreshing one site updates its status from the latest check', function () {
    $this->actingAs(User::factory()->create());

    $site = new Site(
        id: 'site-1',
        name: 'Playhere',
        baseUrl: 'https://playhere.test',
        versionPath: '/version',
        status: 'down',
        version: null,
        screenshotPath: null,
        checkedAt: null,
    );

    app(SiteRepository::class)->save($site);

    $this->post(route('sites.refresh', $site->id))
        ->assertRedirect(route('dashboard'))
        ->assertInertiaFlash('toast', ['type' => 'warning', 'message' => 'Playhere is slow.']);

    $stored = app(SiteRepository::class)->find($site->id);

    expect($this->checkLog->getArrayCopy())->toBe(['https://playhere.test'])
        ->and($stored?->status)->toBe('slow')
        ->and($stored?->responseMs)->toBe(1800)
        ->and($stored?->version)->toBe('3.1.0')
        ->and($stored?->screenshotPath)->toBeNull();
});

test('global refresh checks every registered site', function () {
    $this->actingAs(User::factory()->create());

    $repository = app(SiteRepository::class);
    $repository->save(new Site('a', 'One', 'https://one.test', null, 'down', null, null, null));
    $repository->save(new Site('b', 'Two', 'https://two.test', null, 'down', null, null, null));

    $this->post(route('sites.refresh-all'))
        ->assertRedirect(route('dashboard'))
        ->assertInertiaFlashMissing('toast');

    expect($this->checkLog->getArrayCopy())->toBe(['https://one.test', 'https://two.test']);
});

test('a manual global refresh reports a summary', function () {
    $this->actingAs(User::factory()->create());

    $repository = app(SiteRepository::class);
    $repository->save(new Site('a', 'One', 'https://one.test', null, 'down', null, null, null));
    $repository->save(new Site('b', 'Two', 'https://two.test', null, 'down', null, null, null));

    $this->post(route('sites.refresh-all'), ['manual' => true])
        ->assertRedirect(route('dashboard'))
        ->assertInertiaFlash('toast', ['type' => 'warning', 'message' => 'Checked 2 sites: 2 slow.']);
});

test('a failed screenshot still keeps the check status', function () {
    $this->actingAs(User::factory()->create());

    app(SiteRepository::class)->save(new Site(
        id: 'site-2',
        name: 'Echo',
        baseUrl: 'https://echodata.test',
        versionPath: null,
        status: 'down',
        version: null,
        screenshotPath: null,
        checkedAt: null,
    ));

    $this->post(route('sites.refresh', 'site-2'))->assertRedirect(route('dashboard'));

    expect(app(SiteRepository::class)->find('site-2')?->status)->toBe('slow')
        ->and(app(SiteRepository::class)->find('site-2')?->version)->toBeNull();
});
