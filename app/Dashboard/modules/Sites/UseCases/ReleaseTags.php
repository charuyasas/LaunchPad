<?php

namespace App\Dashboard\Modules\Sites\UseCases;

final class ReleaseTags
{
    private const VERSION = '/^v?\d+(\.\d+)*([-+.][0-9A-Za-z.-]+)?$/';

    /**
     * Version tags first, highest version on top (`v1.0.10` above `v1.0.2`),
     * then any other tags alphabetically.
     *
     * @param  list<string>  $tags
     * @return list<string>
     */
    public static function newestFirst(array $tags): array
    {
        usort($tags, function (string $a, string $b): int {
            $aIsVersion = preg_match(self::VERSION, $a) === 1;
            $bIsVersion = preg_match(self::VERSION, $b) === 1;

            if ($aIsVersion !== $bIsVersion) {
                return $aIsVersion ? -1 : 1;
            }

            if (! $aIsVersion) {
                return strcmp($a, $b);
            }

            return version_compare(ltrim($b, 'v'), ltrim($a, 'v'));
        });

        return $tags;
    }
}
