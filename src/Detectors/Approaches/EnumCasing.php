<?php

declare(strict_types=1);

namespace Laravel\Roster\Detectors\Approaches;

use Laravel\Roster\ApproachResult;
use Laravel\Roster\Enums\Approach;
use Laravel\Roster\Support\SourceFiles;

class EnumCasing extends Convention
{
    protected function result(string $basePath, SourceFiles $files): ?ApproachResult
    {
        $tally = [
            Approach::ENUM_CASE_SCREAMING_SNAKE->value => 0,
            Approach::ENUM_CASE_PASCAL->value => 0,
            Approach::ENUM_CASE_CAMEL->value => 0,
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

            $paths[] = $path;

            foreach ($names as $name) {
                $style = $this->classifyCase($name);

                if ($style instanceof Approach) {
                    $tally[$style->value]++;
                }
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
     * @return Approach::ENUM_CASE_SCREAMING_SNAKE|Approach::ENUM_CASE_PASCAL|Approach::ENUM_CASE_CAMEL|null
     */
    protected function classifyCase(string $name): ?Approach
    {
        if (preg_match('/^[A-Z0-9]+(_[A-Z0-9]+)*$/', $name) === 1 && preg_match('/[A-Z]/', $name) === 1) {
            return Approach::ENUM_CASE_SCREAMING_SNAKE;
        }

        if (preg_match('/^[A-Z][a-zA-Z0-9]*$/', $name) === 1) {
            return Approach::ENUM_CASE_PASCAL;
        }

        if (preg_match('/^[a-z][a-zA-Z0-9]*$/', $name) === 1) {
            return Approach::ENUM_CASE_CAMEL;
        }

        return null;
    }
}
