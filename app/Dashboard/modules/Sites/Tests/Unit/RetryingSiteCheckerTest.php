<?php

use App\Dashboard\Modules\Sites\IO\Http\HttpResult;
use App\Dashboard\Modules\Sites\Tests\Unit\Support\RecordingSleeper;
use App\Dashboard\Modules\Sites\Tests\Unit\Support\SequenceTransport;
use App\Dashboard\Modules\Sites\UseCases\RetryingSiteChecker;
use App\Dashboard\Modules\Sites\UseCases\SiteStatusClassifier;
use App\Dashboard\Modules\Sites\UseCases\Sleeper;

test('retries a failed check and marks the site live when a later try succeeds', function () {
    $transport = new SequenceTransport([
        new HttpResult(successful: false, statusCode: 500, durationMs: 100),
        new HttpResult(successful: false, statusCode: 0, durationMs: 100),
        new HttpResult(successful: true, statusCode: 200, durationMs: 180),
    ]);
    $sleeper = new RecordingSleeper;

    $outcome = checker($transport, $sleeper)->check('https://paylance.test');

    expect($outcome->status)->toBe('live')
        ->and($transport->calls)->toBe(3)
        ->and($sleeper->slept)->toBe([200, 400]);
});

test('marks the site down after three failed attempts', function () {
    $transport = new SequenceTransport([
        new HttpResult(successful: false, statusCode: 500, durationMs: 50),
        new HttpResult(successful: false, statusCode: 502, durationMs: 50),
        new HttpResult(successful: false, statusCode: 0, durationMs: 50),
    ]);

    $outcome = checker($transport, new RecordingSleeper)->check('https://down.test');

    expect($outcome->status)->toBe('down')
        ->and($transport->calls)->toBe(3);
});

test('does not retry a slow successful response', function () {
    $transport = new SequenceTransport([
        new HttpResult(successful: true, statusCode: 200, durationMs: 2200),
    ]);

    $outcome = checker($transport, new RecordingSleeper)->check('https://slow.test');

    expect($outcome->status)->toBe('slow')
        ->and($transport->calls)->toBe(1);
});

test('reports how long the successful response took', function () {
    $transport = new SequenceTransport([
        new HttpResult(successful: true, statusCode: 200, durationMs: 340),
    ]);

    $outcome = checker($transport, new RecordingSleeper, samples: 2)->check('https://fast.test');

    expect($outcome->status)->toBe('live')
        ->and($outcome->durationMs)->toBe(340)
        ->and($transport->calls)->toBe(1);
});

test('a slow response is measured again and the faster sample wins', function () {
    $transport = new SequenceTransport([
        new HttpResult(successful: true, statusCode: 200, durationMs: 2400),
        new HttpResult(successful: true, statusCode: 200, durationMs: 420),
    ]);

    $outcome = checker($transport, new RecordingSleeper, samples: 2)->check('https://cold-start.test');

    expect($outcome->status)->toBe('live')
        ->and($outcome->durationMs)->toBe(420)
        ->and($transport->calls)->toBe(2);
});

test('a site that is slow on every sample stays slow', function () {
    $transport = new SequenceTransport([
        new HttpResult(successful: true, statusCode: 200, durationMs: 2400),
        new HttpResult(successful: true, statusCode: 200, durationMs: 1900),
    ]);

    $outcome = checker($transport, new RecordingSleeper, samples: 2)->check('https://slow.test');

    expect($outcome->status)->toBe('slow')
        ->and($outcome->durationMs)->toBe(1900)
        ->and($transport->calls)->toBe(2);
});

test('a failed extra sample does not turn a slow site down', function () {
    $transport = new SequenceTransport([
        new HttpResult(successful: true, statusCode: 200, durationMs: 2100),
        new HttpResult(successful: false, statusCode: 0, durationMs: 8000),
    ]);

    $outcome = checker($transport, new RecordingSleeper, samples: 2)->check('https://flaky.test');

    expect($outcome->status)->toBe('slow')
        ->and($outcome->durationMs)->toBe(2100);
});

function checker(SequenceTransport $transport, Sleeper $sleeper, int $samples = 1): RetryingSiteChecker
{
    return new RetryingSiteChecker(
        transport: $transport,
        classifier: new SiteStatusClassifier(slowThresholdMs: 1500),
        sleeper: $sleeper,
        attempts: 3,
        timeoutMs: 8000,
        backoffMs: [200, 400],
        samples: $samples,
    );
}
