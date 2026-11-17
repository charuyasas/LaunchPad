<?php

namespace App\Dashboard\Modules\Sites\IO\Storage;

use App\Dashboard\Modules\Sites\Entities\Site;
use App\Dashboard\Modules\Sites\UseCases\SiteRepository;

final class JsonSiteRepository implements SiteRepository
{
    public function __construct(private string $path) {}

    public function all(): array
    {
        return array_map(
            fn (array $row) => Site::fromStorage($row),
            $this->read(),
        );
    }

    public function find(string $id): ?Site
    {
        foreach ($this->all() as $site) {
            if ($site->id === $id) {
                return $site;
            }
        }

        return null;
    }

    public function save(Site $site): void
    {
        $rows = $this->read();
        $stored = false;

        foreach ($rows as $index => $row) {
            if (($row['id'] ?? null) === $site->id) {
                $rows[$index] = $site->toStorage();
                $stored = true;
                break;
            }
        }

        if (! $stored) {
            $rows[] = $site->toStorage();
        }

        $this->write($rows);
    }

    public function delete(string $id): void
    {
        $rows = array_values(array_filter(
            $this->read(),
            fn (array $row): bool => ($row['id'] ?? null) !== $id,
        ));

        $this->write($rows);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function write(array $rows): void
    {
        $directory = dirname($this->path);

        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents(
            $this->path,
            json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            LOCK_EX,
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function read(): array
    {
        if (! is_file($this->path)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($this->path), true);

        if (! is_array($decoded)) {
            return [];
        }

        return array_values(array_filter($decoded, 'is_array'));
    }
}
