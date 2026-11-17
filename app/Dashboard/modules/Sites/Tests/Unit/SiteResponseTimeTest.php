<?php

use App\Dashboard\Modules\Sites\Entities\Site;

test('the response time survives storage and is presented', function () {
    $site = (new Site('site-1', 'Example', 'https://example.test', null, 'down', null, null, null))
        ->withCheck(status: 'live', version: null, screenshotPath: null, checkedAt: '2026-11-14T00:00:00+00:00', responseMs: 640);

    $restored = Site::fromStorage($site->toStorage());

    expect($restored->responseMs)->toBe(640)
        ->and($restored->present()['response_ms'])->toBe(640);
});

test('sites saved before response times existed load without one', function () {
    $site = Site::fromStorage([
        'id' => 'old',
        'name' => 'Old record',
        'base_url' => 'https://old.test',
        'status' => 'live',
    ]);

    expect($site->responseMs)->toBeNull()
        ->and($site->present()['response_ms'])->toBeNull();
});
