<?php

namespace App\Dashboard\Modules\Sites\IO\Screenshots;

interface SiteScreenshotter
{
    public function capture(string $url, string $destination): bool;
}
