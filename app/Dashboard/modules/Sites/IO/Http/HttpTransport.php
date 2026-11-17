<?php

namespace App\Dashboard\Modules\Sites\IO\Http;

interface HttpTransport
{
    public function get(string $url, int $timeoutMs): HttpResult;
}
