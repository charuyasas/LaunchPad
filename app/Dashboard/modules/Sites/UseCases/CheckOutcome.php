<?php

namespace App\Dashboard\Modules\Sites\UseCases;

final readonly class CheckOutcome
{
    public function __construct(
        public string $status,
        public int $durationMs,
        public int $attempts,
    ) {}
}
