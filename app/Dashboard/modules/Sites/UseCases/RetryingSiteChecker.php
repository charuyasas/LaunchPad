<?php

namespace App\Dashboard\Modules\Sites\UseCases;

use App\Dashboard\Modules\Sites\IO\Http\HttpTransport;

final class RetryingSiteChecker implements SiteChecker
{
    /**
     * @param  list<int>  $backoffMs
     */
    public function __construct(
        private HttpTransport $transport,
        private SiteStatusClassifier $classifier,
        private Sleeper $sleeper,
        private int $attempts,
        private int $timeoutMs,
        private array $backoffMs,
        private int $samples = 1,
    ) {}

    public function check(string $url): CheckOutcome
    {
        $lastDuration = 0;

        for ($attempt = 1; $attempt <= $this->attempts; $attempt++) {
            if ($attempt > 1) {
                $this->sleeper->sleepMs($this->backoffMs[$attempt - 2] ?? 400);
            }

            $result = $this->transport->get($url, $this->timeoutMs);
            $lastDuration = $result->durationMs;

            if ($result->successful) {
                $durationMs = $this->fastestSample($url, $result->durationMs);

                return new CheckOutcome(
                    status: $this->classifier->classify(true, $durationMs),
                    durationMs: $durationMs,
                    attempts: $attempt,
                );
            }
        }

        return new CheckOutcome(status: 'down', durationMs: $lastDuration, attempts: $this->attempts);
    }

    /**
     * One slow answer is often a cold start or a network blip, so a slow
     * response is measured again and the fastest sample counts. A failed
     * extra sample is ignored: the site already answered.
     */
    private function fastestSample(string $url, int $firstMs): int
    {
        $fastest = $firstMs;

        for ($sample = 2; $sample <= $this->samples; $sample++) {
            if ($this->classifier->classify(true, $fastest) !== 'slow') {
                break;
            }

            $result = $this->transport->get($url, $this->timeoutMs);

            if ($result->successful) {
                $fastest = min($fastest, $result->durationMs);
            }
        }

        return $fastest;
    }
}
