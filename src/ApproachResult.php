<?php

declare(strict_types=1);

namespace Laravel\Roster;

use Laravel\Roster\Enums\Approach;

class ApproachResult
{
    /**
     * @param  float  $confidence  Raw winning ratio (matched / total).
     * @param  int  $matched  Votes cast for the winning style.
     * @param  int  $total  Votes cast across all competing styles.
     * @param  list<string>  $paths  Absolute paths of the files that voted.
     */
    public function __construct(
        public readonly Approach $approach,
        public readonly float $confidence,
        public readonly int $matched,
        public readonly int $total,
        public readonly array $paths,
    ) {
        //
    }

    /**
     * @return array{
     *     approach: string,
     *     confidence: float,
     *     matched: int,
     *     total: int,
     *     paths: list<string>,
     * }
     */
    public function toArray(): array
    {
        return [
            'approach' => $this->approach->value,
            'confidence' => $this->confidence,
            'matched' => $this->matched,
            'total' => $this->total,
            'paths' => $this->paths,
        ];
    }
}
