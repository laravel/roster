<?php

declare(strict_types=1);

namespace Laravel\Roster\Detectors;

use Laravel\Roster\Enums\Approach;

class ApproachDetector
{
    /** @var list<array{approach: Approach, paths: list<string>}> */
    private const RULES = [
        ['approach' => Approach::ACTION, 'paths' => ['app/Actions']],
        ['approach' => Approach::DDD, 'paths' => ['app/Domains']],
        ['approach' => Approach::MODULAR, 'paths' => ['modules', 'Modules', 'app-modules']],
    ];

    /**
     * @return list<Approach>
     */
    public static function detect(string $basePath): array
    {
        $found = [];

        foreach (self::RULES as $rule) {
            foreach ($rule['paths'] as $relative) {
                if (is_dir($basePath.str_replace('/', DIRECTORY_SEPARATOR, $relative))) {
                    $found[] = $rule['approach'];

                    continue 2;
                }
            }
        }

        return $found;
    }

    /**
     * @return list<string>
     */
    public static function markerPaths(): array
    {
        return array_merge(...array_column(self::RULES, 'paths'));
    }
}
