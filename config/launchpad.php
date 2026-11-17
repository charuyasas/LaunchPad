<?php

return [
    'sites_path' => storage_path('app/sites.json'),
    'screenshots_path' => storage_path('app/screenshots'),
    'slow_threshold_ms' => (int) env('LAUNCHPAD_SLOW_THRESHOLD_MS', 1500),
    'timeout_ms' => (int) env('LAUNCHPAD_TIMEOUT_MS', 8000),
    'retries' => (int) env('LAUNCHPAD_RETRIES', 3),
    // A slow answer is measured up to this many times; the fastest counts.
    'samples' => (int) env('LAUNCHPAD_SAMPLES', 2),
    'backoff_ms' => [200, 400],
    'node_binary' => env('NODE_BINARY', 'node'),

    // Deploying Paylance-based sites through the repository's GitHub Actions workflow.
    'deploy' => [
        'repository' => env('LAUNCHPAD_DEPLOY_REPOSITORY'),
        'workflow' => env('LAUNCHPAD_DEPLOY_WORKFLOW', 'deploy-on-main.yml'),
        'token' => env('LAUNCHPAD_GITHUB_TOKEN'),
    ],
];
