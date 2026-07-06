<?php

declare(strict_types=1);

namespace Laravel\Roster\Detectors\Approaches;

use BackedEnum;
use Laravel\Roster\ApproachResult;
use Laravel\Roster\Support\SourceFiles;

class CustomConventions extends Convention
{
    /**
     * @param  list<array{vote: callable(string, string): (BackedEnum|null), in: string|null}>  $extensions
     */
    public function __construct(protected array $extensions)
    {
        //
    }

    /**
     * @return list<ApproachResult>
     */
    public function detect(string $basePath, SourceFiles $files): array
    {
        $results = [];

        foreach ($this->extensions as $extension) {
            $tally = [];
            $cases = [];
            $paths = [];

            foreach ($files->php($extension['in']) as $path) {
                $style = ($extension['vote'])($files->contents($path), $path);

                if (! $style instanceof BackedEnum) {
                    continue;
                }

                $key = (string) $style->value;
                $tally[$key] = ($tally[$key] ?? 0) + 1;
                $cases[$key] = $style;
                $paths[] = $path;
            }

            $results[] = $this->dominant($tally, $paths, $cases);
        }

        return array_values(array_filter($results));
    }
}
