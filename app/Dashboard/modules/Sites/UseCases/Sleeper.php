<?php

namespace App\Dashboard\Modules\Sites\UseCases;

interface Sleeper
{
    public function sleepMs(int $milliseconds): void;
}
