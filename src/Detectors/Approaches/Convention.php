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
    public function detect(string $basePath, SourceFiles $files): array
    {
        $result = $this->result($basePath, $files);

        return $result instanceof ApproachResult ? [$result] : [];
    }

    protected function result(string $basePath, SourceFiles $files): ?ApproachResult
    {
        return null;
    }

    /**
     * @param  callable(string): (array<string, int>|null)  $counts  file contents => per-style occurrence counts, null to skip the file
     */
    protected function electByFile(SourceFiles $files, ?string $in, callable $counts): ?ApproachResult
    {
        $tally = [];
        $paths = [];

        foreach ($files->php($in) as $path) {
            $fileCounts = $counts($files->contents($path));

            if ($fileCounts === null) {
                continue;
            }

            $winner = $this->fileVote($fileCounts);

            if ($winner === null) {
                continue;
            }

            $tally[$winner] = ($tally[$winner] ?? 0) + 1;
            $paths[] = $path;
        }

        return $this->dominant($tally, $paths);
    }

    /**
     * @param  array<string, int>  $counts  approach value => occurrences within one file
     */
    protected function fileVote(array $counts): ?string
    {
        $counts = array_filter($counts, fn (int $count): bool => $count > 0);

        if ($counts === []) {
            return null;
        }

        arsort($counts);
        $ranked = array_values($counts);

        if (isset($ranked[1]) && $ranked[1] === $ranked[0]) {
            return null;
        }

        return array_key_first($counts);
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
}
