<?php

namespace App\Dashboard\Modules\Sites\UseCases;

final class UsleepSleeper implements Sleeper
{
    public function sleepMs(int $milliseconds): void
    {
        usleep($milliseconds * 1000);
    }
}
