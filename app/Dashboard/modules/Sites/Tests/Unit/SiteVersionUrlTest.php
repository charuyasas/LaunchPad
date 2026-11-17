<?php

use App\Dashboard\Modules\Sites\Entities\Site;

function siteAt(string $baseUrl, ?string $versionPath): Site
{
    return new Site('site-1', 'Example', $baseUrl, $versionPath, 'live', null, null, null);
}

test('the version path resolves from the site origin', function (string $baseUrl, string $expected) {
    expect(siteAt($baseUrl, '/api/version')->versionUrl())->toBe($expected);
})->with([
    'bare origin' => ['https://paylance.test', 'https://paylance.test/api/version'],
    'trailing slash' => ['https://paylance.test/', 'https://paylance.test/api/version'],
    'base url with a page path' => ['https://app.phonex.lk/login', 'https://app.phonex.lk/api/version'],
    'base url with a nested path' => ['https://example.com/app/dashboard/', 'https://example.com/api/version'],
    'custom port' => ['https://example.com:8443/login', 'https://example.com:8443/api/version'],
]);

test('a site without a version path has no version url', function () {
    expect(siteAt('https://paylance.test', null)->versionUrl())->toBeNull();
});
