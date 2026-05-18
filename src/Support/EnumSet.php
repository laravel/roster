<?php

namespace Laravel\Roster\Support;

use BackedEnum;
use Laravel\Roster\Contracts\ScopedCollection;

/**
 * Simple ScopedCollection over a typed list of enum cases.
 *
 * @template T of BackedEnum
 */
class EnumSet implements ScopedCollection
{
    /**
     * @param  array<int, T>  $cases
     */
    public function __construct(protected array $cases) {}

    /**
     * @param  T|array<int, T>  $value
     */
    public function uses(mixed $value): bool
    {
        $needles = is_array($value) ? $value : [$value];

        foreach ($needles as $needle) {
            if (in_array($needle, $this->cases, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  T|array<int, T>  $value
     */
    public function is(mixed $value): bool
    {
        return $this->uses($value);
    }

    /**
     * @return array<int, T>
     */
    public function all(): array
    {
        return $this->cases;
    }
}
