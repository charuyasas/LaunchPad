<?php

namespace App\Dashboard\Modules\Sites\IO\Http\Controllers;

use App\Dashboard\Modules\Sites\IO\Http\Requests\BulkDeployRequest;
use App\Dashboard\Modules\Sites\IO\Http\Requests\DeploySiteRequest;
use App\Dashboard\Modules\Sites\UseCases\BulkDeploySitesInteractor;
use App\Dashboard\Modules\Sites\UseCases\DeployFailed;
use App\Dashboard\Modules\Sites\UseCases\DeployPipeline;
use App\Dashboard\Modules\Sites\UseCases\DeploySiteInteractor;
use App\Dashboard\Modules\Sites\UseCases\ListReleaseTagsInteractor;
use App\Dashboard\Modules\Sites\UseCases\SiteNotDeployable;
use App\Dashboard\Modules\Sites\UseCases\UnknownRelease;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class DeployController extends Controller
{
    public function __construct(private DeployPipeline $pipeline) {}

    public function tags(ListReleaseTagsInteractor $listTags): JsonResponse
    {
        $this->ensureConfigured();

        try {
            return response()->json(['tags' => $listTags->execute()]);
        } catch (DeployFailed $failure) {
            return response()->json(['message' => "Couldn't load tags from GitHub: {$failure->getMessage()}"], 502);
        }
    }

    public function store(DeploySiteRequest $request, string $site, DeploySiteInteractor $deploy): RedirectResponse
    {
        $this->ensureConfigured();

        // The deploy doesn't change anything LaunchPad can see yet, so skip the page-load re-check.
        session()->flash('launchpad.checked', true);

        try {
            $deployed = $deploy->execute(
                $site,
                $request->string('ref')->toString(),
                $request->boolean('run_tests', true),
                (string) $request->user()?->name,
            );
        } catch (UnknownRelease $unknown) {
            throw ValidationException::withMessages(['ref' => $unknown->getMessage()]);
        } catch (SiteNotDeployable $notDeployable) {
            return $this->toast('error', $notDeployable->getMessage());
        } catch (DeployFailed $failure) {
            return $this->toast('error', "GitHub refused the deploy: {$failure->getMessage()}");
        }

        return $this->toast(
            'success',
            "Deploying {$request->string('ref')} to {$deployed->client}.",
            ['label' => 'View on GitHub', 'url' => $this->pipeline->runsUrl()],
        );
    }

    public function bulk(BulkDeployRequest $request, BulkDeploySitesInteractor $bulkDeploy): RedirectResponse
    {
        $this->ensureConfigured();
        session()->flash('launchpad.checked', true);

        $ref = $request->string('ref')->toString();

        try {
            $result = $bulkDeploy->execute(
                $request->siteIds(),
                $ref,
                $request->boolean('run_tests', true),
                (string) $request->user()?->name,
            );
        } catch (UnknownRelease $unknown) {
            throw ValidationException::withMessages(['ref' => $unknown->getMessage()]);
        } catch (DeployFailed $failure) {
            return $this->toast('error', "Couldn't load tags from GitHub: {$failure->getMessage()}");
        }

        $link = ['label' => 'View on GitHub', 'url' => $this->pipeline->runsUrl()];
        $notStarted = implode(', ', array_map(
            fn (array $failure) => "{$failure['name']} ({$failure['reason']})",
            $result->failed,
        ));

        if ($result->failed === []) {
            $count = count($result->started);
            $noun = $count === 1 ? 'site' : 'sites';

            return $this->toast('success', "Deploying {$ref} to {$count} {$noun}.", $link);
        }

        if ($result->started === []) {
            return $this->toast('error', "No deploys started. {$notStarted}.");
        }

        return $this->toast(
            'warning',
            sprintf('Started %d of %d deploys of %s. Not started: %s.', count($result->started), $result->total(), $ref, $notStarted),
            $link,
        );
    }

    private function ensureConfigured(): void
    {
        abort_unless($this->pipeline->isConfigured(), 503, 'Deploying is not configured.');
    }

    /**
     * @param  array{label: string, url: string}|null  $link
     */
    private function toast(string $type, string $message, ?array $link = null): RedirectResponse
    {
        Inertia::flash('toast', array_filter(
            ['type' => $type, 'message' => $message, 'link' => $link],
            fn ($value) => $value !== null,
        ));

        return redirect()->route('dashboard');
    }
}
