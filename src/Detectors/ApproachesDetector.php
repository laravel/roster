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
use Laravel\Roster\Detectors\Approaches\DirectoryConventions;
use Laravel\Roster\Detectors\Approaches\EnumCasing;
use Laravel\Roster\Detectors\Approaches\HttpClientErrorStyle;
use Laravel\Roster\Detectors\Approaches\MassAssignment;
use Laravel\Roster\Detectors\Approaches\ModelConfigSyntax;
use Laravel\Roster\Detectors\Approaches\ModelKeyStyle;
use Laravel\Roster\Detectors\Approaches\NotificationSendStyle;
use Laravel\Roster\Detectors\Approaches\ValidationStyle;
use Laravel\Roster\Detectors\Approaches\ValidationSyntax;
use Laravel\Roster\Support\SourceFiles;

class ApproachesDetector
{
    /** @var list<array{vote: callable(string, string): (BackedEnum|null), in: string|null}> */
    protected static array $extensions = [];

    protected static int $generation = 0;

    protected string $basePath;

    public function __construct(string $basePath, protected SourceFiles $files)
    {
        $this->basePath = Str::finish($basePath, DIRECTORY_SEPARATOR);
    }

    /**
     * @param  callable(string, string): (BackedEnum|null)  $vote
     */
    public static function extend(callable $vote, ?string $in = null): void
    {
        static::$extensions[] = ['vote' => $vote, 'in' => $in];
        static::$generation++;
    }

    public static function flushExtensions(): void
    {
        static::$extensions = [];
        static::$generation++;
    }

    public static function generation(): int
    {
        return static::$generation;
    }

    /**
     * @return list<ApproachResult>
     */
    public static function detect(string $basePath): array
    {
        return (new self($basePath, new SourceFiles($basePath)))->all();
    }

    /**
     * @return list<Convention>
     */
    protected function conventions(): array
    {
        return [
            new DirectoryConventions,
            new MassAssignment,
            new ModelConfigSyntax,
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
            new CustomConventions(static::$extensions),
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
