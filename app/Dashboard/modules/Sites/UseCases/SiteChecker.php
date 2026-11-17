<?php

namespace App\Dashboard\Modules\Sites\UseCases;

interface SiteChecker
{
    public function check(string $url): CheckOutcome;
}
