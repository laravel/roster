<?php

namespace Laravel\Roster\Detectors;

use Laravel\Roster\Enums\Approach;
use Laravel\Roster\Support\EnumSet;

class ApproachDetector
{
    public function __construct(protected string $basePath) {}

    /**
     * @return EnumSet<Approach>
     */
    public function detect(): EnumSet
    {
        /** @var array<int, Approach> $found */
        $found = [];

        if (is_dir($this->basePath.'app'.DIRECTORY_SEPARATOR.'Actions')) {
            $found[] = Approach::ACTION;
        }

        if (is_dir($this->basePath.'app'.DIRECTORY_SEPARATOR.'Domains')) {
            $found[] = Approach::DDD;
        }

        if (is_dir($this->basePath.'modules')
            || is_dir($this->basePath.'Modules')
            || is_dir($this->basePath.'app-modules')) {
            $found[] = Approach::MODULAR;
        }

        return new EnumSet($found);
    }
}
