<?php

declare(strict_types=1);

namespace Laravel\Roster\Detectors;

use Laravel\Roster\Enums\Approach;
use Laravel\Roster\Support\EnumSet;

class ApproachDetector
{
    /** @var list<array{approach: Approach, paths: list<string>}> */
    private const RULES = [
        ['approach' => Approach::ACTION, 'paths' => ['app/Actions']],
        ['approach' => Approach::DDD, 'paths' => ['app/Domains']],
        ['approach' => Approach::MODULAR, 'paths' => ['modules', 'Modules', 'app-modules']],
    ];

    public function __construct(protected string $basePath) {}

    /**
     * @return EnumSet<Approach>
     */
    public function detect(): EnumSet
    {
        /** @var array<int, Approach> $found */
        $found = [];

        foreach (self::RULES as $rule) {
            foreach ($rule['paths'] as $relative) {
                if (is_dir($this->basePath.str_replace('/', DIRECTORY_SEPARATOR, $relative))) {
                    $found[] = $rule['approach'];

                    continue 2;
                }
            }
        }

        return new EnumSet($found);
    }
}
