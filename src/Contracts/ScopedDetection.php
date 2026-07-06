<?php

declare(strict_types=1);

namespace Laravel\Roster\Contracts;

interface ScopedDetection
{
    public function configured(): ScopedCollection;

    public function installed(): ScopedCollection;
}
