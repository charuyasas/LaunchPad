<?php

namespace App\Dashboard\Modules\Sites\IO\Screenshots;

use Illuminate\Support\Facades\Process;

final class PlaywrightScreenshotter implements BatchedSiteScreenshotter
{
    public function capture(string $url, string $destination): bool
    {
        return $this->captureMany([
            ['url' => $url, 'destination' => $destination],
        ])[$destination] ?? false;
    }

    public function captureMany(array $jobs): array
    {
        $results = [];

        foreach ($jobs as $job) {
            $directory = dirname($job['destination']);

            if (! is_dir($directory)) {
                mkdir($directory, 0777, true);
            }

            $results[$job['destination']] = false;
        }

        if ($jobs === []) {
            return [];
        }

        $node = (string) config('launchpad.node_binary', 'node');
        $script = base_path('scripts/capture-screenshots.mjs');

        $result = Process::timeout(90)
            ->input((string) json_encode($jobs))
            ->run([$node, $script]);

        $saved = json_decode($result->output(), true);

        if (! is_array($saved)) {
            return $results;
        }

        foreach ($saved as $destination) {
            if (is_string($destination) && isset($results[$destination]) && is_file($destination)) {
                $results[$destination] = true;
            }
        }

        return $results;
    }
}
