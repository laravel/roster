<?php

declare(strict_types=1);

namespace Laravel\Roster\Detectors\Approaches;

use Laravel\Roster\ApproachResult;
use Laravel\Roster\Enums\Approach;
use Laravel\Roster\Support\SourceFiles;

abstract class Convention
{
    protected const MIN_SAMPLE = 3;

    protected const CONFIDENCE_FLOOR = 0.8;

    /**
     * @return list<ApproachResult>
     */
    public function detect(SourceFiles $files): array
    {
        $result = $this->result($files);

        return $result instanceof ApproachResult ? [$result] : [];
    }

    protected function result(SourceFiles $files): ?ApproachResult
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
     */
    protected function dominant(array $tally, array $paths): ?ApproachResult
    {
        $tally = array_filter($tally, fn (int $votes): bool => $votes > 0);
        $total = array_sum($tally);

        if ($total < static::MIN_SAMPLE) {
            return null;
        }

        arsort($tally);
        $winner = (string) array_key_first($tally);
        $votes = $tally[$winner];

        if ($votes / $total < static::CONFIDENCE_FLOOR) {
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
}
