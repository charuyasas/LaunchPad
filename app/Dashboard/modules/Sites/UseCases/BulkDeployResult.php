<?php

namespace App\Dashboard\Modules\Sites\UseCases;

use App\Dashboard\Modules\Sites\Entities\Site;

final readonly class BulkDeployResult
{
    /**
     * @param  list<Site>  $started  sites whose workflow run was accepted
     * @param  list<array{name: string, reason: string}>  $failed  sites that were not started, and why
     */
    public function __construct(
        public array $started,
        public array $failed,
    ) {}

    public function total(): int
    {
        return count($this->started) + count($this->failed);
    }
}
