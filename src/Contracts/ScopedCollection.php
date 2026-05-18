<?php

declare(strict_types=1);

namespace Laravel\Roster\Contracts;

interface ScopedCollection
{
    /**
     * @param  mixed  $value  single case or array of cases
     */
    public function uses(mixed $value): bool;

    /**
     * @return array<int, mixed>
     */
    public function all(): array;
}
