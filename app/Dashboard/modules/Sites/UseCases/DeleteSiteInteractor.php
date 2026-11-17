<?php

namespace App\Dashboard\Modules\Sites\UseCases;

final class DeleteSiteInteractor
{
    public function __construct(private SiteRepository $sites) {}

    public function execute(string $id): void
    {
        $site = $this->sites->find($id);

        if ($site === null) {
            abort(404);
        }

        if ($site->screenshotPath !== null && is_file($site->screenshotPath)) {
            unlink($site->screenshotPath);
        }

        $this->sites->delete($id);
    }
}
