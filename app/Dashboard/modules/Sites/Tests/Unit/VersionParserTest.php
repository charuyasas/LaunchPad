<?php

use App\Dashboard\Modules\Sites\UseCases\VersionParser;

test('reads a version field from json', function () {
    expect((new VersionParser)->parse('{"version":"2.4.1"}'))->toBe('2.4.1');
});

test('reads plain text as the version', function () {
    expect((new VersionParser)->parse("  1.0.0-beta\n"))->toBe('1.0.0-beta');
});

test('returns null for an empty body', function () {
    expect((new VersionParser)->parse('   '))->toBeNull();
});
