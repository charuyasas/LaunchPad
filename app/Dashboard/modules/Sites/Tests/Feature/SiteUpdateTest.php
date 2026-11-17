<?php

use App\Dashboard\Modules\Sites\Entities\Site;
use App\Dashboard\Modules\Sites\UseCases\SiteRepository;
use App\Models\User;

beforeEach(function () {
    $directory = storage_path('framework/testing/update');
    if (! is_dir($directory)) {
        mkdir($directory, 0777, true);
    }

    $sitesPath = $directory.'/sites.json';
    if (file_exists($sitesPath)) {
        unlink($sitesPath);
    }

    config()->set('launchpad.sites_path', $sitesPath);
    config()->set('launchpad.screenshots_path', $directory.'/screenshots');
});

test('an authenticated user can edit a site name, url, and version path', function () {
    $this->actingAs(User::factory()->create());

    $repository = app(SiteRepository::class);
    $repository->save(new Site('site-1', 'Example', 'https://example.com', null, 'live', '1.0.0', null, '2026-11-12T00:00:00+00:00'));
    $repository->save(new Site('site-2', 'Glint', 'https://glint.test', '/version', 'down', null, null, null));

    $this->put(route('sites.update', 'site-1'), [
        'name' => 'Paylance',
        'base_url' => 'https://paylance.test/',
        'version_path' => 'version',
    ])
        ->assertRedirect(route('dashboard'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Paylance updated.']);

    $this->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('refresh', false)
            ->where('checkSite', 'site-1'));

    $updated = $repository->find('site-1');

    expect($updated?->name)->toBe('Paylance')
        ->and($updated?->baseUrl)->toBe('https://paylance.test')
        ->and($updated?->versionPath)->toBe('/version')
        ->and($updated?->status)->toBe('live')
        ->and($repository->find('site-2')?->name)->toBe('Glint');
});

test('clearing the version path removes it', function () {
    $this->actingAs(User::factory()->create());

    app(SiteRepository::class)->save(new Site('site-1', 'Example', 'https://example.com', '/version', 'live', '1.0.0', null, null));

    $this->put(route('sites.update', 'site-1'), [
        'name' => 'Example',
        'base_url' => 'https://example.com',
        'version_path' => '',
    ])->assertRedirect(route('dashboard'));

    expect(app(SiteRepository::class)->find('site-1')?->versionPath)->toBeNull();
});

test('editing rejects a base url that is not https', function () {
    $this->actingAs(User::factory()->create());

    app(SiteRepository::class)->save(new Site('site-1', 'Example', 'https://example.com', null, 'live', null, null, null));

    $this->put(route('sites.update', 'site-1'), [
        'name' => 'Example',
        'base_url' => 'http://example.com',
    ])->assertSessionHasErrors('base_url');

    expect(app(SiteRepository::class)->find('site-1')?->baseUrl)->toBe('https://example.com');
});

test('guests cannot edit a site', function () {
    $this->put(route('sites.update', 'site-1'), [
        'name' => 'Example',
        'base_url' => 'https://example.com',
    ])->assertRedirect(route('login'));
});

test('editing a missing site returns not found', function () {
    $this->actingAs(User::factory()->create());

    $this->put(route('sites.update', 'missing'), [
        'name' => 'Example',
        'base_url' => 'https://example.com',
    ])->assertNotFound();
});
