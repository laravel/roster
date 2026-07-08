<?php

declare(strict_types=1);

namespace Laravel\Roster\Detectors;

use BackedEnum;
use Illuminate\Support\Str;
use Laravel\Roster\ApproachResult;
use Laravel\Roster\Detectors\Approaches\AuthorizationStyle;
use Laravel\Roster\Detectors\Approaches\AuthRetrievalStyle;
use Laravel\Roster\Detectors\Approaches\CommandSignatureSyntax;
use Laravel\Roster\Detectors\Approaches\ControllerStyle;
use Laravel\Roster\Detectors\Approaches\Convention;
use Laravel\Roster\Detectors\Approaches\CustomConventions;
use Laravel\Roster\Detectors\Approaches\EnumCasing;
use Laravel\Roster\Detectors\Approaches\HttpClientErrorStyle;
use Laravel\Roster\Detectors\Approaches\MassAssignment;
use Laravel\Roster\Detectors\Approaches\ModelKeyStyle;
use Laravel\Roster\Detectors\Approaches\NotificationSendStyle;
use Laravel\Roster\Detectors\Approaches\ValidationStyle;
use Laravel\Roster\Detectors\Approaches\ValidationSyntax;
use Laravel\Roster\Support\SourceFiles;

class ApproachesDetector
{
    protected string $basePath;

    /**
     * @param  list<array{vote: callable(string, string): (BackedEnum|null), in: string|null}>  $extensions
     */
    public function __construct(string $basePath, protected SourceFiles $files, protected array $extensions = [])
    {
        $this->basePath = Str::finish($basePath, DIRECTORY_SEPARATOR);
    }

    /**
     * @param  list<array{vote: callable(string, string): (BackedEnum|null), in: string|null}>  $extensions
     * @return list<ApproachResult>
     */
    public static function detect(string $basePath, array $extensions = []): array
    {
        return (new self($basePath, new SourceFiles($basePath), $extensions))->all();
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
            new ControllerStyle,
            new CommandSignatureSyntax,
            new HttpClientErrorStyle,
            new NotificationSendStyle,
            new AuthorizationStyle,
            new AuthRetrievalStyle,
            new ModelKeyStyle,
            new CustomConventions($this->extensions),
        ];
    }

    /**
     * @return list<ApproachResult>
     */
    public function all(): array
    {
        $results = [];

        foreach ($this->conventions() as $convention) {
            $results = [...$results, ...$convention->detect($this->basePath, $this->files)];
        }

        return $results;
    }
}
