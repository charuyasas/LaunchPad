<?php

namespace App\Dashboard\Modules\Sites\UseCases;

final class BulkDeploySitesInteractor
{
    public function __construct(
        private SiteRepository $sites,
        private DeployPipeline $pipeline,
        private DeploySiteInteractor $deploySite,
    ) {}

    /**
     * Start one deploy run per selected site, all on the same tag. A site
     * that can't be started is reported and the rest still go ahead.
     *
     * @param  list<string>  $siteIds
     *
     * @throws UnknownRelease when `$ref` is not a tag in the repository
     * @throws DeployFailed when the tag list can't be loaded from GitHub
     */
    public function execute(array $siteIds, string $ref, bool $runTests, string $deployedBy): BulkDeployResult
    {
        // Check the tag once for the whole batch.
        if (! in_array($ref, $this->pipeline->tags(), true)) {
            throw new UnknownRelease("{$ref} is not a tag in the repository.");
        }

        $started = [];
        $failed = [];

        foreach ($siteIds as $siteId) {
            $site = $this->sites->find($siteId);

            if ($site === null) {
                $failed[] = ['name' => $siteId, 'reason' => 'site not found'];

                continue;
            }

            if (! $site->isDeployable()) {
                $failed[] = ['name' => $site->name, 'reason' => 'no client folder'];

                continue;
            }

            try {
                $started[] = $this->deploySite->deploy($site, $ref, $runTests, $deployedBy);
            } catch (DeployFailed $failure) {
                $failed[] = ['name' => $site->name, 'reason' => $failure->getMessage()];
            }
        }

        return new BulkDeployResult($started, $failed);
    }
}
