<?php

declare(strict_types=1);

namespace Laravel\Roster\Detectors\Approaches;

use Laravel\Roster\ApproachResult;
use Laravel\Roster\Enums\Approach;
use Laravel\Roster\Support\SourceFiles;

class DirectoryConventions extends Convention
{
    /** @var list<array{approach: Approach, paths: list<string>}> */
    private const DIRECTORY_RULES = [
        ['approach' => Approach::ACTION, 'paths' => ['app/Actions']],
        ['approach' => Approach::DDD, 'paths' => ['app/Domains']],
        ['approach' => Approach::MODULAR, 'paths' => ['modules', 'Modules', 'app-modules']],
    ];

    /**
     * @return list<ApproachResult>
     */
    public function detect(string $basePath, SourceFiles $files): array
    {
        $results = [];

        foreach (self::DIRECTORY_RULES as $rule) {
            foreach ($rule['paths'] as $relative) {
                $path = $basePath.str_replace('/', DIRECTORY_SEPARATOR, $relative);

                if (is_dir($path)) {
                    $results[] = new ApproachResult($rule['approach'], 1.0, 1, 1, [$path]);

                    break;
                }
            }
        }

        return $results;
    }
}
