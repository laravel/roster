<?php

declare(strict_types=1);

namespace Laravel\Roster\Detectors;

use Laravel\Roster\ApproachResult;
use Laravel\Roster\Detectors\Approaches\AuthorizationStyle;
use Laravel\Roster\Detectors\Approaches\AuthRetrievalStyle;
use Laravel\Roster\Detectors\Approaches\CommandSignatureSyntax;
use Laravel\Roster\Detectors\Approaches\Convention;
use Laravel\Roster\Detectors\Approaches\EnumCasing;
use Laravel\Roster\Detectors\Approaches\MassAssignment;
use Laravel\Roster\Detectors\Approaches\ModelKeyStyle;
use Laravel\Roster\Detectors\Approaches\NotificationSendStyle;
use Laravel\Roster\Detectors\Approaches\ValidationStyle;
use Laravel\Roster\Detectors\Approaches\ValidationSyntax;
use Laravel\Roster\Support\SourceFiles;

class ApproachDetector
{
    public function __construct(protected SourceFiles $files)
    {
        //
    }

    /**
     * @return list<ApproachResult>
     */
    public static function detect(string $basePath): array
    {
        return (new self(new SourceFiles($basePath)))->all();
    }

    /**
     * @return list<Convention>
     */
    protected function conventions(): array
    {
        return [
            new MassAssignment,
            new EnumCasing,
            new ValidationSyntax,
            new ValidationStyle,
            new CommandSignatureSyntax,
            new NotificationSendStyle,
            new AuthorizationStyle,
            new AuthRetrievalStyle,
            new ModelKeyStyle,
        ];
    }

    /**
     * @return list<ApproachResult>
     */
    public function all(): array
    {
        $results = [];

        foreach ($this->conventions() as $convention) {
            $results = [...$results, ...$convention->detect($this->files)];
        }

        return $results;
    }
}
