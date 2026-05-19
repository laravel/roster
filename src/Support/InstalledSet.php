<?php

declare(strict_types=1);

namespace Laravel\Roster\Support;

use BackedEnum;

/**
 * @template T of BackedEnum
 *
 * @extends EnumSet<T>
 */
class InstalledSet extends EnumSet
{
    /**
     * @param  T  $value
     */
    public function isInstalled(BackedEnum $value): bool
    {
        return $this->is($value);
    }
}
