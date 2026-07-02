<?php

declare(strict_types=1);

namespace Laravel\Roster\Detectors;

use Laravel\Roster\ApproachResult;
use Laravel\Roster\Enums\Approach;
use Laravel\Roster\Support\SourceFiles;

/**
 * Detects the stylistic conventions the application's own source code has
 * adopted. Each detector tallies votes for competing styles and only reports
 * a winner backed by enough evidence: at least MIN_SAMPLE votes and a Wilson
 * score lower bound (95% CI) of at least WILSON_FLOOR for the winner's
 * proportion — so 4/5 is rejected, 90/100 passes, and a split stays silent.
 */
class ApproachesDetector
{
    /** Minimum number of votes before a style is considered at all. */
    protected const MIN_SAMPLE = 5;

    /** Gate on the Wilson lower bound rather than the raw ratio, so a small sample cannot over-claim (9/10 ≠ 90/100). */
    protected const WILSON_FLOOR = 0.5;

    /** z for a 95% confidence interval. */
    protected const Z = 1.96;

    public function __construct(protected SourceFiles $files) {}

    /**
     * @return list<ApproachResult>
     */
    public static function detect(string $basePath): array
    {
        return (new self(new SourceFiles($basePath)))->all();
    }

    /**
     * @return list<ApproachResult>
     */
    public function all(): array
    {
        return array_values(array_filter([
            $this->massAssignment(),
            $this->enumCasing(),
            $this->validationSyntax(),
            $this->queryScopes(),
        ]));
    }

    /**
     * One vote per model: `protected $fillable` vs `protected $guarded`.
     * Models declaring both or neither abstain.
     */
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

    /**
     * One vote per enum case (not per file), classified by name casing.
     */
    protected function enumCasing(): ?ApproachResult
    {
        $tally = [
            Approach::ENUM_CASE_SCREAMING_SNAKE->value => 0,
            Approach::ENUM_CASE_PASCAL->value => 0,
            Approach::ENUM_CASE_CAMEL->value => 0,
        ];
        $paths = [];

        foreach ($this->files->php() as $path) {
            if (! $this->files->contains($path, 'enum')) {
                continue;
            }

            $names = $this->enumCaseNames($this->files->contents($path));

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
     * One vote per Form Request with a rules() method: pipe strings
     * (`'required|max:255'`) vs arrays (`['required', 'max:255']`), the
     * dominant local notation deciding each file's vote.
     */
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

    /**
     * One vote per query scope: the `#[Scope]` attribute vs `scopeXxx()` naming.
     */
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
     * Reduce a style tally to its dominant winner, or null when there is too
     * little evidence or the styles are mixed. Gating uses the Wilson score
     * lower bound; the reported confidence is the raw ratio.
     *
     * @param  array<string, int>  $tally  approach value => votes
     * @param  list<string>  $paths
     */
    protected function dominant(array $tally, array $paths): ?ApproachResult
    {
        $tally = array_filter($tally, fn (int $votes): bool => $votes > 0);
        $total = array_sum($tally);

        if ($total < self::MIN_SAMPLE) {
            return null;
        }

        arsort($tally);
        $winner = (string) array_key_first($tally);
        $votes = $tally[$winner];

        if ($this->wilsonLower($votes, $total) < self::WILSON_FLOOR) {
            return null;
        }

        return new ApproachResult(
            approach: Approach::from($winner),
            confidence: $votes / $total,
            matched: $votes,
            total: $total,
            paths: $paths,
        );
    }

    /**
     * Lower bound of the Wilson score interval for a binomial proportion.
     */
    protected function wilsonLower(int $successes, int $total): float
    {
        if ($total === 0) {
            return 0.0;
        }

        $z = self::Z;
        $z2 = $z * $z;
        $phat = $successes / $total;

        $center = ($phat + $z2 / (2 * $total)) / (1 + $z2 / $total);
        $margin = ($z / (1 + $z2 / $total)) * sqrt($phat * (1 - $phat) / $total + $z2 / (4 * $total * $total));

        return $center - $margin;
    }

    /**
     * Collect enum case names via a token scan that tracks brace depth, so
     * `case` labels inside `switch` bodies and the word "enum" in comments or
     * strings are never mistaken for enum cases.
     *
     * @return list<string>
     */
    protected function enumCaseNames(string $code): array
    {
        $tokens = @token_get_all($code);
        $names = [];
        $depth = 0;
        $pendingEnum = false;
        $enumBodyDepths = [];

        for ($i = 0, $count = count($tokens); $i < $count; $i++) {
            $token = $tokens[$i];

            if (is_array($token)) {
                // String interpolation openers count as `{` — their closing
                // brace arrives as a plain `}` token and must stay balanced.
                if ($token[0] === T_CURLY_OPEN || $token[0] === T_DOLLAR_OPEN_CURLY_BRACES) {
                    $depth++;
                } elseif ($token[0] === T_ENUM) {
                    $pendingEnum = true;
                } elseif ($token[0] === T_CASE && $enumBodyDepths !== [] && end($enumBodyDepths) === $depth) {
                    $name = $this->nextIdentifier($tokens, $i);

                    if ($name !== null) {
                        $names[] = $name;
                    }
                }

                continue;
            }

            if ($token === '{') {
                $depth++;

                if ($pendingEnum) {
                    $enumBodyDepths[] = $depth;
                    $pendingEnum = false;
                }
            } elseif ($token === '}') {
                if ($enumBodyDepths !== [] && end($enumBodyDepths) === $depth) {
                    array_pop($enumBodyDepths);
                }

                $depth--;
            }
        }

        return $names;
    }

    /**
     * @param  array<int, array{0: int, 1: string, 2: int}|string>  $tokens
     */
    protected function nextIdentifier(array $tokens, int $from): ?string
    {
        for ($i = $from + 1, $count = count($tokens); $i < $count; $i++) {
            $token = $tokens[$i];

            if (is_array($token)) {
                if (in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                    continue;
                }

                return $token[0] === T_STRING ? $token[1] : null;
            }

            return null;
        }

        return null;
    }

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
