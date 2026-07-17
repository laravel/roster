<?php

declare(strict_types=1);

namespace Laravel\Roster\Detectors\Approaches;

use Laravel\Roster\ApproachResult;
use Laravel\Roster\Enums\Approach;
use Laravel\Roster\Support\SourceFiles;

class EnumCasing extends Convention
{
    protected function result(SourceFiles $files): ?ApproachResult
    {
        $tally = [
            Approach::EnumCaseScreamingSnake->value => 0,
            Approach::EnumCasePascal->value => 0,
            Approach::EnumCaseCamel->value => 0,
        ];

        $paths = [];

        foreach ($files->php() as $path) {
            $contents = $files->contents($path);

            if (stripos($contents, 'enum') === false) {
                continue;
            }

            $names = $this->enumCaseNames($contents);

            if ($names === []) {
                continue;
            }

            $votes = 0;

            foreach ($names as $name) {
                $style = $this->classifyCase($name);

                if ($style instanceof Approach) {
                    $tally[$style->value]++;
                    $votes++;
                }
            }

            if ($votes > 0) {
                $paths[] = $path;
            }
        }

        return $this->dominant($tally, $paths);
    }

    /**
     * @return list<string>
     */
    protected function enumCaseNames(string $code): array
    {
        preg_match_all('/^\s*case\s+(\w+)\s*[;=]/m', $code, $matches);

        return $matches[1];
    }

    /**
     * @return Approach::EnumCaseScreamingSnake|Approach::EnumCasePascal|Approach::EnumCaseCamel|null
     */
    protected function classifyCase(string $name): ?Approach
    {
        if (preg_match('/^[A-Z0-9]+(_[A-Z0-9]+)*$/', $name) === 1 && preg_match('/[A-Z]/', $name) === 1) {
            return Approach::EnumCaseScreamingSnake;
        }

        if (preg_match('/^[A-Z][a-zA-Z0-9]*$/', $name) === 1) {
            return Approach::EnumCasePascal;
        }

        if (preg_match('/^[a-z][a-zA-Z0-9]*$/', $name) === 1) {
            return Approach::EnumCaseCamel;
        }

        return null;
    }
}
