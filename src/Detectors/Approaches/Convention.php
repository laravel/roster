<?php

declare(strict_types=1);

namespace Laravel\Roster\Detectors\Approaches;

use BackedEnum;
use Laravel\Roster\ApproachResult;
use Laravel\Roster\Enums\Approach;
use Laravel\Roster\Support\SourceFiles;

abstract class Convention
{
    protected const MIN_SAMPLE = 5;

    protected const CONFIDENCE_FLOOR = 0.8;

    /**
     * @return list<ApproachResult>
     */
    abstract public function detect(string $basePath, SourceFiles $files): array;

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
}
