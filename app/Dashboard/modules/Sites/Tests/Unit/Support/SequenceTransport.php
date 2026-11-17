<?php

namespace App\Dashboard\Modules\Sites\Tests\Unit\Support;

use App\Dashboard\Modules\Sites\IO\Http\HttpResult;
use App\Dashboard\Modules\Sites\IO\Http\HttpTransport;

final class SequenceTransport implements HttpTransport
{
    public int $calls = 0;

    /**
     * @param  list<HttpResult>  $results
     */
    public function __construct(private array $results) {}

    public function get(string $url, int $timeoutMs): HttpResult
    {
        $this->calls++;

        return array_shift($this->results);
    }
}
