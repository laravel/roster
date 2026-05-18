<?php

namespace Laravel\Roster;

use Laravel\Roster\Detectors\AgentsDetection;
use Laravel\Roster\Ecosystems\JsEcosystem;
use Laravel\Roster\Ecosystems\PhpEcosystem;
use Laravel\Roster\Enums\Approach;
use Laravel\Roster\Enums\BrowserTestFramework;
use Laravel\Roster\Enums\Frontend;
use Laravel\Roster\Enums\Stack;
use Laravel\Roster\Enums\StarterKit;
use Laravel\Roster\Enums\TestFramework;
use Laravel\Roster\Support\EnumSet;

class RosterManager
{
    protected ?Roster $cached = null;

    public function __construct(protected Registry $registry) {}

    /**
     * Register custom aliases against the shared Registry.
     *
     * @param  callable(Registry): void  $callback
     */
    public function extend(callable $callback): self
    {
        $callback($this->registry);
        $this->cached = null;

        return $this;
    }

    public function registry(): Registry
    {
        return $this->registry;
    }

    public function scan(?string $basePath = null, bool $detectSystem = true): Roster
    {
        return $this->cached = Roster::scan($basePath, $detectSystem, $this->registry);
    }

    public function fresh(): self
    {
        $this->cached = null;

        return $this;
    }

    public function instance(): Roster
    {
        return $this->cached ??= $this->scan();
    }

    public function php(): PhpEcosystem
    {
        return $this->instance()->php();
    }

    public function js(): JsEcosystem
    {
        return $this->instance()->js();
    }

    /** @return EnumSet<Stack> */
    public function stack(): EnumSet
    {
        return $this->instance()->stack();
    }

    public function testFramework(): ?TestFramework
    {
        return $this->instance()->testFramework();
    }

    /** @return EnumSet<BrowserTestFramework> */
    public function browserTestFrameworks(): EnumSet
    {
        return $this->instance()->browserTestFrameworks();
    }

    /** @return EnumSet<Frontend> */
    public function frontend(): EnumSet
    {
        return $this->instance()->frontend();
    }

    /** @return EnumSet<StarterKit> */
    public function starterKit(): EnumSet
    {
        return $this->instance()->starterKit();
    }

    public function agents(): AgentsDetection
    {
        return $this->instance()->agents();
    }

    /** @return EnumSet<Approach> */
    public function approach(): EnumSet
    {
        return $this->instance()->approach();
    }

    public function json(): string
    {
        return $this->instance()->json();
    }
}
