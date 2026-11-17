<?php

namespace App\Dashboard\Modules\Sites\Tests\Unit\Support;

use App\Dashboard\Modules\Sites\UseCases\Sleeper;

final class RecordingSleeper implements Sleeper
{
    /** @var list<int> */
    public array $slept = [];

    public function sleepMs(int $milliseconds): void
    {
        $this->slept[] = $milliseconds;
    }
}
