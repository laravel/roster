<?php

declare(strict_types=1);

namespace Laravel\Roster\Support;

use BackedEnum;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Laravel\Roster\ApproachResult;

class ApproachSet
{
    /** @var Collection<string, ApproachResult> */
    protected Collection $results;

    /**
     * @param  array<int, ApproachResult>  $results
     */
    public function __construct(array $results)
    {
        /** @var Collection<string, ApproachResult> $keyed */
        $keyed = (new Collection($results))
            ->keyBy(fn (ApproachResult $result): string => (string) $result->approach->value);

        $this->results = $keyed;
    }

    /**
     * @param  BackedEnum|array<int, BackedEnum>  $approach
     */
    public function uses(BackedEnum|array $approach): bool
    {
        foreach (Arr::wrap($approach) as $needle) {
            if ($this->results->has((string) $needle->value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, BackedEnum>  $approaches
     */
    public function usesAll(array $approaches): bool
    {
        foreach ($approaches as $needle) {
            if (! $this->results->has((string) $needle->value)) {
                return false;
            }
        }

        return true;
    }

    public function result(BackedEnum $approach): ?ApproachResult
    {
        return $this->results->get((string) $approach->value);
    }

    /**
     * @return Collection<string, ApproachResult>
     */
    public function all(): Collection
    {
        return $this->results;
    }
}
