<?php

declare(strict_types=1);

namespace Laravel\Roster\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Laravel\Roster\ApproachResult;
use Laravel\Roster\Enums\Approach;

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
            ->keyBy(fn (ApproachResult $result): string => $result->approach->value);

        $this->results = $keyed;
    }

    /**
     * @param  Approach|array<int, Approach>  $approach
     */
    public function uses(Approach|array $approach): bool
    {
        foreach (Arr::wrap($approach) as $needle) {
            if ($this->results->has($needle->value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, Approach>  $approaches
     */
    public function usesAll(array $approaches): bool
    {
        foreach ($approaches as $needle) {
            if (! $this->results->has($needle->value)) {
                return false;
            }
        }

        return true;
    }

    public function result(Approach $approach): ?ApproachResult
    {
        return $this->results->get($approach->value);
    }

    /**
     * @return Collection<string, ApproachResult>
     */
    public function all(): Collection
    {
        return $this->results;
    }
}
