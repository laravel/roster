<?php

namespace Laravel\Roster\Contracts;

interface ScopedDetection
{
    public function configured(): ScopedCollection;

    public function installed(): ScopedCollection;
}
