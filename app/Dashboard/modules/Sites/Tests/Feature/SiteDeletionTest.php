<?php

use App\Dashboard\Modules\Sites\Entities\Site;
use App\Dashboard\Modules\Sites\UseCases\SiteRepository;
use App\Models\User;

beforeEach(function () {
    $directory = storage_path('framework/testing/delete');
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

test('an authenticated user can delete a site and its screenshot', function () {
    $this->actingAs(User::factory()->create());

    $screenshot = config('launchpad.screenshots_path').'/site-1.png';
    if (! is_dir(dirname($screenshot))) {
        mkdir(dirname($screenshot), 0777, true);
    }
    file_put_contents($screenshot, 'png');

    $repository = app(SiteRepository::class);
    $repository->save(new Site('site-1', 'Example', 'https://example.com', null, 'live', null, $screenshot, null));
    $repository->save(new Site('site-2', 'Paylance', 'https://paylance.test', null, 'live', null, null, null));

    $this->delete(route('sites.destroy', 'site-1'))
        ->assertRedirect(route('dashboard'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Example removed.']);

    expect($repository->find('site-1'))->toBeNull()
        ->and($repository->find('site-2'))->not->toBeNull()
        ->and(is_file($screenshot))->toBeFalse();
});

test('guests cannot delete a site', function () {
    $this->delete(route('sites.destroy', 'site-1'))
        ->assertRedirect(route('login'));
});

test('deleting a missing site returns not found', function () {
    $this->actingAs(User::factory()->create());

    $this->delete(route('sites.destroy', 'missing'))
        ->assertNotFound();
});
