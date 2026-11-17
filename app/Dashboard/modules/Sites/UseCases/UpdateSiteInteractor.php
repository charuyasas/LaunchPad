<?php

namespace App\Dashboard\Modules\Sites\UseCases;

use App\Dashboard\Modules\Sites\Entities\Site;

final class UpdateSiteInteractor
{
    public function __construct(private SiteRepository $sites) {}

    public function execute(string $id, string $name, string $baseUrl, ?string $versionPath, ?string $client = null): Site
    {
        $site = $this->sites->find($id);

        if ($site === null) {
            abort(404);
        }

        $updated = $site->withDetails($name, $baseUrl, $versionPath, $client);
        $this->sites->save($updated);

        return $updated;
    }
}
