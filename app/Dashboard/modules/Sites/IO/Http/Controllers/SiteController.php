<?php

namespace App\Dashboard\Modules\Sites\IO\Http\Controllers;

use App\Dashboard\Modules\Sites\Entities\Site;
use App\Dashboard\Modules\Sites\IO\Http\Requests\StoreSiteRequest;
use App\Dashboard\Modules\Sites\UseCases\DeleteSiteInteractor;
use App\Dashboard\Modules\Sites\UseCases\DeployPipeline;
use App\Dashboard\Modules\Sites\UseCases\RefreshAllSitesInteractor;
use App\Dashboard\Modules\Sites\UseCases\RefreshSiteInteractor;
use App\Dashboard\Modules\Sites\UseCases\RegisterSiteInteractor;
use App\Dashboard\Modules\Sites\UseCases\SiteRepository;
use App\Dashboard\Modules\Sites\UseCases\UpdateSiteInteractor;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SiteController extends Controller
{
    private const TOAST_TYPES = ['live' => 'success', 'slow' => 'warning', 'down' => 'error'];

    public function index(Request $request, SiteRepository $sites, DeployPipeline $deploys): Response
    {
        $records = $sites->all();
        $session = $request->session();

        // Opening the dashboard checks every site. Redirects back from our own
        // actions don't: they either just ran a check or only need one site checked.
        $alreadyChecked = (bool) $session->pull('launchpad.checked', false);
        $checkSite = $session->pull('launchpad.check_site');
        $checkSite = is_string($checkSite) ? $checkSite : null;
        $rejectedForm = $session->has('errors');

        return Inertia::render('Dashboard', [
            'sites' => array_map(fn (Site $site) => $site->present(), $records),
            'refresh' => $records !== [] && ! $alreadyChecked && ! $rejectedForm && $checkSite === null,
            'checkSite' => $checkSite,
            'deployEnabled' => $deploys->isConfigured(),
        ]);
    }

    public function store(StoreSiteRequest $request, RegisterSiteInteractor $register): RedirectResponse
    {
        $site = $register->execute(
            $request->string('name')->toString(),
            $request->string('base_url')->toString(),
            $request->versionPath(),
            $request->client(),
        );

        $this->toast('success', "{$site->name} added.");
        session()->flash('launchpad.check_site', $site->id);

        return redirect()->route('dashboard');
    }

    public function update(StoreSiteRequest $request, string $site, UpdateSiteInteractor $updateSite): RedirectResponse
    {
        $updated = $updateSite->execute(
            $site,
            $request->string('name')->toString(),
            $request->string('base_url')->toString(),
            $request->versionPath(),
            $request->client(),
        );

        $this->toast('success', "{$updated->name} updated.");
        session()->flash('launchpad.check_site', $updated->id);

        return redirect()->route('dashboard');
    }

    public function destroy(string $site, DeleteSiteInteractor $deleteSite, SiteRepository $sites): RedirectResponse
    {
        $name = $sites->find($site)?->name;

        $deleteSite->execute($site);

        if ($name !== null) {
            $this->toast('success', "{$name} removed.");
        }

        session()->flash('launchpad.checked', true);

        return redirect()->route('dashboard');
    }

    public function refresh(string $site, RefreshSiteInteractor $refreshSite): RedirectResponse
    {
        set_time_limit(120);
        $refreshed = $refreshSite->execute($site);

        $this->toast(self::TOAST_TYPES[$refreshed->status] ?? 'info', "{$refreshed->name} is {$refreshed->status}.");

        session()->flash('launchpad.checked', true);

        return redirect()->route('dashboard');
    }

    public function refreshAll(Request $request, RefreshAllSitesInteractor $refreshAll): RedirectResponse
    {
        set_time_limit(120);
        $statuses = array_column($refreshAll->execute(), 'status');

        // The automatic check on page load only speaks up when a site is down.
        if ($request->boolean('manual') || in_array('down', $statuses, true)) {
            $this->summarise($statuses);
        }

        session()->flash('launchpad.checked', true);

        return redirect()->route('dashboard');
    }

    /**
     * @param  list<string>  $statuses
     */
    private function summarise(array $statuses): void
    {
        if ($statuses === []) {
            return;
        }

        $counts = array_count_values($statuses);
        $total = count($statuses);

        if (($counts['live'] ?? 0) === $total) {
            $this->toast('success', $total === 1 ? 'The site is live.' : "All {$total} sites are live.");

            return;
        }

        $parts = [];
        foreach (['live', 'slow', 'down'] as $status) {
            if (($counts[$status] ?? 0) > 0) {
                $parts[] = "{$counts[$status]} {$status}";
            }
        }

        $type = isset($counts['down']) ? 'error' : 'warning';
        $noun = $total === 1 ? 'site' : 'sites';

        $this->toast($type, "Checked {$total} {$noun}: ".implode(', ', $parts).'.');
    }

    private function toast(string $type, string $message): void
    {
        Inertia::flash('toast', ['type' => $type, 'message' => $message]);
    }

    public function preview(string $site, SiteRepository $sites): BinaryFileResponse
    {
        $record = $sites->find($site);

        abort_unless(
            $record !== null && $record->screenshotPath !== null && is_file($record->screenshotPath),
            404,
        );

        return response()->file($record->screenshotPath, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'private, max-age=0, must-revalidate',
        ]);
    }
}
