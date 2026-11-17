<?php

namespace App\Dashboard\Modules\Sites\UseCases;

use App\Dashboard\Modules\Sites\Entities\Site;
use Illuminate\Support\Str;

final class RegisterSiteInteractor
{
    public function __construct(private SiteRepository $sites) {}

    public function execute(string $name, string $baseUrl, ?string $versionPath, ?string $client = null): Site
    {
        $site = new Site(
            id: (string) Str::ulid(),
            name: trim($name),
            baseUrl: rtrim(trim($baseUrl), '/'),
            versionPath: $versionPath,
            status: 'down',
            version: null,
            screenshotPath: null,
            checkedAt: null,
            client: $client,
        );

        $this->sites->save($site);

        return $site;
    }
}
