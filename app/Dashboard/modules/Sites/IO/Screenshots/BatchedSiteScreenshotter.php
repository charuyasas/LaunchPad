<?php

namespace App\Dashboard\Modules\Sites\IO\Screenshots;

interface BatchedSiteScreenshotter extends SiteScreenshotter
{
    /**
     * @param  list<array{url: string, destination: string}>  $jobs
     * @return array<string, bool>
     */
    public function captureMany(array $jobs): array;
}
