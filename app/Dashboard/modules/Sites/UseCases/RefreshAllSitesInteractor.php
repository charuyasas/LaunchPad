<?php

namespace App\Dashboard\Modules\Sites\UseCases;

use App\Dashboard\Modules\Sites\Entities\Site;
use App\Dashboard\Modules\Sites\IO\Screenshots\BatchedSiteScreenshotter;
use App\Dashboard\Modules\Sites\IO\Screenshots\SiteScreenshotter;

final class RefreshAllSitesInteractor
{
    public function __construct(
        private SiteRepository $sites,
        private RefreshSiteInteractor $refreshSite,
        private SiteScreenshotter $screenshots,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function execute(): array
    {
        $sites = $this->sites->all();

        if ($sites === [] || ! $this->screenshots instanceof BatchedSiteScreenshotter) {
            return array_map(
                fn (Site $site) => $this->refreshSite->execute($site->id)->present(),
                $sites,
            );
        }

        $measured = [];
        $jobs = [];

        foreach ($sites as $site) {
            $result = $this->refreshSite->measure($site);
            $measured[] = [$site, $result];
            $jobs[] = [
                'url' => $site->baseUrl,
                'destination' => $result['destination'],
            ];
        }

        $captured = $this->screenshots->captureMany($jobs);
        $presented = [];

        foreach ($measured as [$site, $result]) {
            $presented[] = $this->refreshSite->commit(
                $site,
                $result['outcome'],
                $result['version'],
                $result['destination'],
                $captured[$result['destination']] ?? false,
            )->present();
        }

        return $presented;
    }
}
