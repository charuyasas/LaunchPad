<?php

namespace App\Dashboard\Modules\Sites\IO\Http;

use App\Dashboard\Modules\Sites\UseCases\VersionParser;
use App\Dashboard\Modules\Sites\UseCases\VersionReader;

final class HttpVersionReader implements VersionReader
{
    public function __construct(
        private HttpTransport $transport,
        private VersionParser $parser,
        private int $timeoutMs,
    ) {}

    public function read(string $url): ?string
    {
        $result = $this->transport->get($url, $this->timeoutMs);

        if (! $result->successful) {
            return null;
        }

        return $this->parser->parse($result->body);
    }
}
