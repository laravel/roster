<?php

declare(strict_types=1);

namespace Laravel\Roster\Support;

use BackedEnum;
use Laravel\Roster\Contracts\ScopedCollection;

/**
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
     * @param  T  $value
     */
    public function is(BackedEnum $value): bool
    {
        return in_array($value, $this->cases, true);
    }

    /**
     * @return array<int, T>
     */
    public function all(): array
    {
        return $this->cases;
    }

    /**
     * @return array<int, string|int>
     */
    public function values(): array
    {
        return array_map(fn (BackedEnum $c): int|string => $c->value, $this->cases);
    }
}
