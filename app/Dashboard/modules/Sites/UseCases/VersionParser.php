<?php

namespace App\Dashboard\Modules\Sites\UseCases;

final class VersionParser
{
    public function parse(string $body): ?string
    {
        $trimmed = trim($body);

        if ($trimmed === '') {
            return null;
        }

        $decoded = json_decode($trimmed, true);

        if (is_array($decoded) && isset($decoded['version']) && is_string($decoded['version'])) {
            $version = trim($decoded['version']);

            return $version === '' ? null : $this->limit($version);
        }

        $line = strtok($trimmed, "\n");
        $line = trim(strip_tags((string) $line));

        return $line === '' ? null : $this->limit($line);
    }

    private function limit(string $value): string
    {
        return mb_substr($value, 0, 64);
    }
}
