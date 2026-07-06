<?php

declare(strict_types=1);

namespace Laravel\Roster\Enums;

enum TestFramework: string
{
    case PEST = 'pest';
    case PHPUNIT = 'phpunit';

    public function is(self $value): bool
    {
        return $this === $value;
    }
}
