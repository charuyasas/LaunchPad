<?php

namespace App\Dashboard\Modules\Sites\IO\GitHub;

use App\Dashboard\Modules\Sites\UseCases\DeployFailed;
use App\Dashboard\Modules\Sites\UseCases\DeployPipeline;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Talks to the GitHub REST API with a fine-grained token that can read the
 * repository and run its workflows (Actions: read and write, Contents: read).
 */
final class GitHubDeployPipeline implements DeployPipeline
{
    private const API = 'https://api.github.com';

    /** Safety stop for tag pagination: 20 pages of 100 tags. */
    private const MAX_PAGES = 20;

    public function __construct(
        private ?string $repository,
        private string $workflow,
        private ?string $token,
    ) {}

    public function isConfigured(): bool
    {
        return filled($this->repository) && filled($this->token);
    }

    public function tags(): array
    {
        $tags = [];
        $url = self::API."/repos/{$this->repository}/tags?per_page=100";

        for ($page = 0; $url !== null && $page < self::MAX_PAGES; $page++) {
            $response = $this->send(fn () => $this->client()->get($url));

            foreach ((array) $response->json() as $tag) {
                if (is_array($tag) && isset($tag['name']) && is_string($tag['name'])) {
                    $tags[] = $tag['name'];
                }
            }

            $url = $this->nextPage($response->header('Link'));
        }

        return $tags;
    }

    public function dispatch(string $ref, array $inputs): void
    {
        $this->send(fn () => $this->client()->post(
            self::API."/repos/{$this->repository}/actions/workflows/{$this->workflow}/dispatches",
            ['ref' => $ref, 'inputs' => $inputs],
        ));
    }

    public function runsUrl(): string
    {
        return "https://github.com/{$this->repository}/actions/workflows/{$this->workflow}";
    }

    private function client(): PendingRequest
    {
        return Http::withToken((string) $this->token)
            ->acceptJson()
            ->withHeaders(['X-GitHub-Api-Version' => '2022-11-28'])
            ->timeout(15);
    }

    /**
     * @param  callable(): Response  $request
     */
    private function send(callable $request): Response
    {
        try {
            $response = $request();
        } catch (ConnectionException) {
            throw new DeployFailed('Could not reach GitHub.');
        }

        if ($response->failed()) {
            $message = $response->json('message');

            throw new DeployFailed(is_string($message) && $message !== ''
                ? $message
                : "GitHub answered with HTTP {$response->status()}.");
        }

        return $response;
    }

    /** Follows GitHub's `Link: <…>; rel="next"` pagination header. */
    private function nextPage(string $link): ?string
    {
        return preg_match('/<([^>]+)>;\s*rel="next"/', $link, $match) === 1 ? $match[1] : null;
    }
}
