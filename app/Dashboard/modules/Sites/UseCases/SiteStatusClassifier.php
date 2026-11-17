<?php

namespace App\Dashboard\Modules\Sites\UseCases;

final class SiteStatusClassifier
{
    public function __construct(private int $slowThresholdMs) {}

    public function classify(bool $successful, int $durationMs): string
    {
        if (! $successful) {
            return 'down';
        }

        if ($durationMs >= $this->slowThresholdMs) {
            return 'slow';
        }

        return 'live';
    }
}
