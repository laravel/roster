<?php

declare(strict_types=1);

namespace Laravel\Roster\Detectors;

use BackedEnum;
use Illuminate\Support\Str;
use Laravel\Roster\ApproachResult;
use Laravel\Roster\Enums\Approach;
use Laravel\Roster\Support\SourceFiles;

class ApproachesDetector
{
    private const MIN_SAMPLE = 5;

    private const CONFIDENCE_FLOOR = 0.8;

    /** @var list<array{approach: Approach, paths: list<string>}> */
    private const DIRECTORY_RULES = [
        ['approach' => Approach::ACTION, 'paths' => ['app/Actions']],
        ['approach' => Approach::DDD, 'paths' => ['app/Domains']],
        ['approach' => Approach::MODULAR, 'paths' => ['modules', 'Modules', 'app-modules']],
    ];

    /** @var list<array{vote: callable(string, string): (BackedEnum|null), in: string|null}> */
    protected static array $extensions = [];

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
    }

    public static function flushExtensions(): void
    {
        static::$extensions = [];
    }

    /**
     * @return list<ApproachResult>
     */
    public static function detect(string $basePath): array
    {
        return (new self($basePath, new SourceFiles($basePath)))->all();
    }

    /**
     * @return list<ApproachResult>
     */
    public function all(): array
    {
        return array_values(array_filter([
            ...$this->directoryConventions(),
            $this->massAssignment(),
            $this->enumCasing(),
            $this->validationSyntax(),
            $this->queryScopes(),
            ...$this->customConventions(),
        ]));
    }

    /**
     * @return list<ApproachResult>
     */
    protected function customConventions(): array
    {
        $results = [];

        foreach (static::$extensions as $extension) {
            $tally = [];
            $cases = [];
            $paths = [];

            foreach ($this->files->php($extension['in']) as $path) {
                $style = ($extension['vote'])($this->files->contents($path), $path);

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

    /**
     * @return list<ApproachResult>
     */
    protected function directoryConventions(): array
    {
        $results = [];

        foreach (self::DIRECTORY_RULES as $rule) {
            foreach ($rule['paths'] as $relative) {
                $path = $this->basePath.str_replace('/', DIRECTORY_SEPARATOR, $relative);

                if (is_dir($path)) {
                    $results[] = new ApproachResult($rule['approach'], 1.0, 1, 1, [$path]);

                    break;
                }
            }
        }

        return $results;
    }

    protected function massAssignment(): ?ApproachResult
    {
        $tally = [
            Approach::MASS_ASSIGNMENT_FILLABLE->value => 0,
            Approach::MASS_ASSIGNMENT_GUARDED->value => 0,
        ];
        $paths = [];

        foreach ($this->files->php('Models') as $path) {
            $contents = $this->files->contents($path);

            $fillable = preg_match('/protected\s+\$fillable\b/', $contents) === 1;
            $guarded = preg_match('/protected\s+\$guarded\b/', $contents) === 1;

            if ($fillable === $guarded) {
                continue;
            }

            $tally[$fillable ? Approach::MASS_ASSIGNMENT_FILLABLE->value : Approach::MASS_ASSIGNMENT_GUARDED->value]++;
            $paths[] = $path;
        }

        return $this->dominant($tally, $paths);
    }

    protected function enumCasing(): ?ApproachResult
    {
        $tally = [
            Approach::ENUM_CASE_SCREAMING_SNAKE->value => 0,
            Approach::ENUM_CASE_PASCAL->value => 0,
            Approach::ENUM_CASE_CAMEL->value => 0,
        ];
        $paths = [];

        foreach ($this->files->php() as $path) {
            $contents = $this->files->contents($path);

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

    protected function validationSyntax(): ?ApproachResult
    {
        $tally = [
            Approach::VALIDATION_PIPE_SYNTAX->value => 0,
            Approach::VALIDATION_ARRAY_SYNTAX->value => 0,
        ];
        $paths = [];

        foreach ($this->files->php('Http/Requests') as $path) {
            $contents = $this->files->contents($path);

            if (! str_contains($contents, 'function rules')) {
                continue;
            }

            $pipe = (int) preg_match_all("/=>\s*'[^']*\|[^']*'/", $contents);
            $array = (int) preg_match_all("/=>\s*\[\s*'/", $contents);

            if ($pipe === $array) {
                continue;
            }

            $tally[$pipe > $array ? Approach::VALIDATION_PIPE_SYNTAX->value : Approach::VALIDATION_ARRAY_SYNTAX->value]++;
            $paths[] = $path;
        }

        return $this->dominant($tally, $paths);
    }

    protected function queryScopes(): ?ApproachResult
    {
        $tally = [
            Approach::QUERY_SCOPE_ATTRIBUTE->value => 0,
            Approach::QUERY_SCOPE_METHOD->value => 0,
        ];
        $paths = [];

        foreach ($this->files->php('Models') as $path) {
            $contents = $this->files->contents($path);

            $attribute = (int) preg_match_all('/#\[\s*Scope\s*\]/', $contents);
            $method = (int) preg_match_all('/function\s+scope[A-Z]\w*\s*\(/', $contents);

            if ($attribute === 0 && $method === 0) {
                continue;
            }

            $tally[Approach::QUERY_SCOPE_ATTRIBUTE->value] += $attribute;
            $tally[Approach::QUERY_SCOPE_METHOD->value] += $method;
            $paths[] = $path;
        }

        return $this->dominant($tally, $paths);
    }

    /**
     * @param  array<string, int>  $tally  approach value => votes
     * @param  list<string>  $paths
     * @param  array<string, BackedEnum>  $cases  approach value => case, for non-built-in enums
     */
    protected function dominant(array $tally, array $paths, array $cases = []): ?ApproachResult
    {
        $tally = array_filter($tally, fn (int $votes): bool => $votes > 0);
        $total = array_sum($tally);

        if ($total < self::MIN_SAMPLE) {
            return null;
        }

        arsort($tally);
        $winner = (string) array_key_first($tally);
        $votes = $tally[$winner];

        if ($votes / $total <= self::CONFIDENCE_FLOOR) {
            return null;
        }

        return new ApproachResult(
            approach: $cases[$winner] ?? Approach::from($winner),
            confidence: $votes / $total,
            matched: $votes,
            total: $total,
            paths: $paths,
        );
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
