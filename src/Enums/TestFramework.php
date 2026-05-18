<?php

namespace Laravel\Roster\Enums;

enum TestFramework: string
{
    case PEST = 'pest';
    case PHPUNIT = 'phpunit';

    /**
     * @param  self|array<int, self>  $value
     */
    public function is(self|array $value): bool
    {
        $needles = is_array($value) ? $value : [$value];

        return in_array($this, $needles, true);
    }
}
