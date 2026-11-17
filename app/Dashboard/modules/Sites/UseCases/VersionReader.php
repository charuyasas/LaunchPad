<?php

namespace App\Dashboard\Modules\Sites\UseCases;

interface VersionReader
{
    public function read(string $url): ?string;
}
