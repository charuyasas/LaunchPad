<?php

namespace App\Dashboard\Modules\Sites\Entities;

/**
 * @phpstan-type LastDeploy array{ref: string, run_tests: bool, by: string, at: string}
 */
final readonly class Site
{
    /**
     * @param  LastDeploy|null  $lastDeploy
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $baseUrl,
        public ?string $versionPath,
        public string $status,
        public ?string $version,
        public ?string $screenshotPath,
        public ?string $checkedAt,
        /** How long the homepage took to answer on the last check, in ms. */
        public ?int $responseMs = null,
        /** The Paylance client folder on the VPS; only these sites can be deployed. */
        public ?string $client = null,
        /** What was last deployed from LaunchPad, and by whom. */
        public ?array $lastDeploy = null,
    ) {}

    public function withDetails(string $name, string $baseUrl, ?string $versionPath, ?string $client = null): self
    {
        return new self(
            $this->id,
            trim($name),
            rtrim(trim($baseUrl), '/'),
            $versionPath,
            $this->status,
            $this->version,
            $this->screenshotPath,
            $this->checkedAt,
            $this->responseMs,
            $client,
            $this->lastDeploy,
        );
    }

    /**
     * The version path is root-relative, so it resolves from the site's origin
     * the way a browser would: `https://app.example.com/login` + `/api/version`
     * becomes `https://app.example.com/api/version`.
     */
    public function versionUrl(): ?string
    {
        if ($this->versionPath === null) {
            return null;
        }

        $parts = parse_url($this->baseUrl);
        $origin = ($parts['scheme'] ?? 'https').'://'.($parts['host'] ?? '')
            .(isset($parts['port']) ? ':'.$parts['port'] : '');

        return $origin.'/'.ltrim($this->versionPath, '/');
    }

    public function isDeployable(): bool
    {
        return $this->client !== null;
    }

    public function withCheck(string $status, ?string $version, ?string $screenshotPath, string $checkedAt, ?int $responseMs = null): self
    {
        return new self(
            $this->id,
            $this->name,
            $this->baseUrl,
            $this->versionPath,
            $status,
            $version,
            $screenshotPath,
            $checkedAt,
            $responseMs,
            $this->client,
            $this->lastDeploy,
        );
    }

    public function withDeploy(string $ref, bool $runTests, string $by, string $at): self
    {
        return new self(
            $this->id,
            $this->name,
            $this->baseUrl,
            $this->versionPath,
            $this->status,
            $this->version,
            $this->screenshotPath,
            $this->checkedAt,
            $this->responseMs,
            $this->client,
            ['ref' => $ref, 'run_tests' => $runTests, 'by' => $by, 'at' => $at],
        );
    }

    /**
     * @param  array<string, mixed>  $row
     */
    public static function fromStorage(array $row): self
    {
        return new self(
            id: (string) $row['id'],
            name: (string) $row['name'],
            baseUrl: (string) $row['base_url'],
            versionPath: self::text($row, 'version_path'),
            status: (string) ($row['status'] ?? 'down'),
            version: self::text($row, 'version'),
            screenshotPath: self::text($row, 'screenshot_path'),
            checkedAt: self::text($row, 'checked_at'),
            responseMs: isset($row['response_ms']) && is_numeric($row['response_ms']) ? (int) $row['response_ms'] : null,
            client: self::text($row, 'client'),
            lastDeploy: self::lastDeployFrom($row['last_deploy'] ?? null),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toStorage(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'base_url' => $this->baseUrl,
            'version_path' => $this->versionPath,
            'status' => $this->status,
            'version' => $this->version,
            'screenshot_path' => $this->screenshotPath,
            'checked_at' => $this->checkedAt,
            'response_ms' => $this->responseMs,
            'client' => $this->client,
            'last_deploy' => $this->lastDeploy,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function present(): array
    {
        $hasPreview = $this->screenshotPath !== null && is_file($this->screenshotPath);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'base_url' => $this->baseUrl,
            'version_path' => $this->versionPath,
            'status' => $this->status,
            'version' => $this->version,
            'preview_url' => $hasPreview
                ? '/sites/'.$this->id.'/preview?v='.urlencode((string) $this->checkedAt)
                : null,
            'checked_at' => $this->checkedAt,
            'response_ms' => $this->responseMs,
            'client' => $this->client,
            'last_deploy' => $this->lastDeploy,
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private static function text(array $row, string $key): ?string
    {
        return isset($row[$key]) && $row[$key] !== '' ? (string) $row[$key] : null;
    }

    /**
     * @return LastDeploy|null
     */
    private static function lastDeployFrom(mixed $value): ?array
    {
        if (! is_array($value) || ! isset($value['ref'], $value['at'])) {
            return null;
        }

        return [
            'ref' => (string) $value['ref'],
            'run_tests' => (bool) ($value['run_tests'] ?? true),
            'by' => (string) ($value['by'] ?? ''),
            'at' => (string) $value['at'],
        ];
    }
}
