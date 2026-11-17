<?php

namespace App\Dashboard\Modules\Sites\UseCases;

use App\Dashboard\Modules\Sites\Entities\Site;
use App\Dashboard\Modules\Sites\IO\Screenshots\SiteScreenshotter;
use Illuminate\Support\Carbon;

final class RefreshSiteInteractor
{
    public function __construct(
        private SiteRepository $sites,
        private SiteChecker $checker,
        private VersionReader $versions,
        private SiteScreenshotter $screenshots,
    ) {}

    public function execute(string $id): Site
    {
        $site = $this->sites->find($id);

        if ($site === null) {
            abort(404);
        }

        $measured = $this->measure($site);
        $captured = $this->screenshots->capture($site->baseUrl, $measured['destination']);

        return $this->commit($site, $measured['outcome'], $measured['version'], $measured['destination'], $captured);
    }

    /**
     * @return array{outcome: CheckOutcome, version: ?string, destination: string}
     */
    public function measure(Site $site): array
    {
        $version = null;

        $versionUrl = $site->versionUrl();
        if ($versionUrl !== null) {
            $version = $this->versions->read($versionUrl);
        }

        return [
            'outcome' => $this->checker->check($site->baseUrl),
            'version' => $version,
            'destination' => rtrim((string) config('launchpad.screenshots_path'), '/').'/'.$site->id.'.png',
        ];
    }

    public function commit(Site $site, CheckOutcome $outcome, ?string $version, string $destination, bool $captured): Site
    {
        $updated = $site->withCheck(
            status: $outcome->status,
            version: $version,
            screenshotPath: $captured ? $destination : $site->screenshotPath,
            checkedAt: Carbon::now()->toIso8601String(),
            // A down site never answered, so it has no meaningful response time.
            responseMs: $outcome->status === 'down' ? null : $outcome->durationMs,
        );

        $this->sites->save($updated);

        return $updated;
    }
}
