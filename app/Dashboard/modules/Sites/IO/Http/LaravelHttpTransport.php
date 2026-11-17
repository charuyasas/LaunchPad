<?php

namespace App\Dashboard\Modules\Sites\IO\Http;

use Illuminate\Support\Facades\Http;
use Throwable;

final class LaravelHttpTransport implements HttpTransport
{
    public function get(string $url, int $timeoutMs): HttpResult
    {
        $started = hrtime(true);

        try {
            $response = Http::timeout(max(1, $timeoutMs) / 1000)
                ->withHeaders(['User-Agent' => 'LaunchPad'])
                ->get($url);

            return new HttpResult(
                successful: $response->successful(),
                statusCode: $response->status(),
                durationMs: $this->elapsed($started),
                body: $response->body(),
            );
        } catch (Throwable) {
            return new HttpResult(
                successful: false,
                statusCode: 0,
                durationMs: $this->elapsed($started),
            );
        }
    }

    private function elapsed(int $started): int
    {
        return (int) ((hrtime(true) - $started) / 1_000_000);
    }
}
