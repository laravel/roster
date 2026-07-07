<?php

declare(strict_types=1);

namespace Laravel\Roster\Support;

use BackedEnum;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Laravel\Roster\ApproachResult;
use Laravel\Roster\Enums\Approach;

class ApproachSet
{
    /** @var list<ApproachResult> */
    protected array $results;

    /**
     * @param  array<int, ApproachResult>  $results
     */
    public function __construct(array $results)
    {
        $this->results = array_values($results);
    }

    /**
     * @param  BackedEnum|array<int, BackedEnum>  $approach
     */
    public function uses(BackedEnum|array $approach): bool
    {
        foreach (Arr::wrap($approach) as $needle) {
            if ($this->result($needle) instanceof ApproachResult) {
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
            if (! $this->result($needle) instanceof ApproachResult) {
                return false;
            }
        }

        return true;
    }

    public function result(BackedEnum $approach): ?ApproachResult
    {
        foreach ($this->results as $result) {
            if ($result->approach === $approach) {
                return $result;
            }
        }

        return null;
    }

    /**
     * @return Collection<string, ApproachResult>
     */
    public function all(): Collection
    {
        /** @var Collection<string, ApproachResult> $keyed */
        $keyed = new Collection;

        foreach ($this->results as $result) {
            $key = $result->approach instanceof Approach
                ? (string) $result->approach->value
                : $result->approach::class.':'.$result->approach->value;

            if (! $keyed->has($key)) {
                $keyed->put($key, $result);
            }
        }

        return $keyed;
    }
}
