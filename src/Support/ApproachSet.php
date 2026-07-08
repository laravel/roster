<?php

declare(strict_types=1);

namespace Laravel\Roster\Support;

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
     * @param  Approach|array<int, Approach>  $approach
     */
    public function uses(Approach|array $approach): bool
    {
        return $this->approaches()->uses($approach);
    }

    /**
     * @param  array<int, Approach>  $approaches
     */
    public function usesAll(array $approaches): bool
    {
        return $this->approaches()->usesAll($approaches);
    }

    public function result(Approach $approach): ?ApproachResult
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
            if (! $keyed->has($result->approach->value)) {
                $keyed->put($result->approach->value, $result);
            }
        }

        return $keyed;
    }

    /**
     * @return EnumSet<Approach>
     */
    protected function approaches(): EnumSet
    {
        return new EnumSet(array_map(
            fn (ApproachResult $result): Approach => $result->approach,
            $this->results,
        ));
    }
}
