<?php

namespace Laravel\Roster\Detectors;

use Laravel\Roster\Ecosystems\PhpEcosystem;
use Laravel\Roster\Enums\TestFramework;

class TestFrameworkDetector
{
    public function detect(PhpEcosystem $php): ?TestFramework
    {
        if ($php->uses('pestphp/pest')) {
            return TestFramework::PEST;
        }

        if ($php->uses('phpunit/phpunit')) {
            return TestFramework::PHPUNIT;
        }

        return null;
    }
}
