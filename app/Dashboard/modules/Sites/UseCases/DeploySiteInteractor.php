<?php

namespace App\Dashboard\Modules\Sites\UseCases;

use App\Dashboard\Modules\Sites\Entities\Site;
use Illuminate\Support\Carbon;

final class DeploySiteInteractor
{
    public function __construct(
        private SiteRepository $sites,
        private DeployPipeline $pipeline,
    ) {}

    /**
     * Run the Paylance deploy workflow for one site's client folder.
     *
     * @throws SiteNotDeployable when the site has no client folder
     * @throws UnknownRelease when `$ref` is not a tag in the repository
     * @throws DeployFailed when GitHub refuses the run
     */
    public function execute(string $siteId, string $ref, bool $runTests, string $deployedBy): Site
    {
        $site = $this->sites->find($siteId);

        if ($site === null) {
            abort(404);
        }

        if (! $site->isDeployable()) {
            throw new SiteNotDeployable("{$site->name} has no client folder, so it can't be deployed.");
        }

        // Only real tags can be deployed, never an arbitrary branch or commit.
        if (! in_array($ref, $this->pipeline->tags(), true)) {
            throw new UnknownRelease("{$ref} is not a tag in the repository.");
        }

        return $this->deploy($site, $ref, $runTests, $deployedBy);
    }

    /**
     * Start the workflow for a site whose client folder and tag are already
     * known to be valid, then record the deploy on the site.
     *
     * @throws DeployFailed when GitHub refuses the run
     */
    public function deploy(Site $site, string $ref, bool $runTests, string $deployedBy): Site
    {
        $this->pipeline->dispatch($ref, [
            'client' => (string) $site->client,
            'run_tests' => $runTests ? 'yes' : 'no',
        ]);

        $deployed = $site->withDeploy($ref, $runTests, $deployedBy, Carbon::now()->toIso8601String());
        $this->sites->save($deployed);

        return $deployed;
    }
}
