<?php

namespace Laravel\Using;

use Laravel\Using\Enums\Approaches;

class Approach
{
    public function __construct(protected Approaches $approach) {}

    public function name(): string
    {
        return $this->approach->name;
    }

    public function approach(): Approaches
    {
        return $this->approach;
    }
}
