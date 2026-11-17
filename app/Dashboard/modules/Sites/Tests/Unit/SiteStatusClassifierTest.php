<?php

use App\Dashboard\Modules\Sites\UseCases\SiteStatusClassifier;

test('a fast successful response is live', function () {
    $status = (new SiteStatusClassifier(slowThresholdMs: 1500))->classify(
        successful: true,
        durationMs: 400,
    );

    expect($status)->toBe('live');
});

test('a successful response at the slow threshold is slow', function () {
    $status = (new SiteStatusClassifier(slowThresholdMs: 1500))->classify(
        successful: true,
        durationMs: 1500,
    );

    expect($status)->toBe('slow');
});

test('an unsuccessful response is down', function () {
    $status = (new SiteStatusClassifier(slowThresholdMs: 1500))->classify(
        successful: false,
        durationMs: 8000,
    );

    expect($status)->toBe('down');
});
