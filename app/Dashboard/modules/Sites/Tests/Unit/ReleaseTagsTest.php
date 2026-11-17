<?php

use App\Dashboard\Modules\Sites\UseCases\ReleaseTags;

test('tags are ordered newest version first', function () {
    expect(ReleaseTags::newestFirst(['v1.0.0', 'v1.0.10', 'v1.0.2', 'v1.1.0', 'v0.9.9']))
        ->toBe(['v1.1.0', 'v1.0.10', 'v1.0.2', 'v1.0.0', 'v0.9.9']);
});

test('tags with and without a v prefix sort together', function () {
    expect(ReleaseTags::newestFirst(['1.2.0', 'v1.10.0', 'v1.3.0']))
        ->toBe(['v1.10.0', 'v1.3.0', '1.2.0']);
});

test('tags that are not version numbers go last, alphabetically', function () {
    expect(ReleaseTags::newestFirst(['staging', 'v2.0.0', 'hotfix', 'v1.0.0']))
        ->toBe(['v2.0.0', 'v1.0.0', 'hotfix', 'staging']);
});

test('a release candidate sorts below its final release', function () {
    expect(ReleaseTags::newestFirst(['v1.1.0-rc.1', 'v1.1.0', 'v1.0.0']))
        ->toBe(['v1.1.0', 'v1.1.0-rc.1', 'v1.0.0']);
});
