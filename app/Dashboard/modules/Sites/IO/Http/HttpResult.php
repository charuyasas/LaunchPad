<?php

namespace App\Dashboard\Modules\Sites\IO\Http;

final readonly class HttpResult
{
    public function __construct(
        public bool $successful,
        public int $statusCode,
        public int $durationMs,
        public string $body = '',
    ) {}
}
