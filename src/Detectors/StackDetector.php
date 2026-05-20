<?php

declare(strict_types=1);

namespace Laravel\Roster\Detectors;

use Laravel\Roster\Ecosystems\JsEcosystem;
use Laravel\Roster\Ecosystems\PhpEcosystem;
use Laravel\Roster\Enums\Stack;
use Laravel\Roster\Support\EnumSet;

class StackDetector
{
    /** @var list<array{stack: Stack, packages: list<string>}> */
    private const INERTIA_RULES = [
        ['stack' => Stack::INERTIA_REACT, 'packages' => ['@inertiajs/react']],
        ['stack' => Stack::INERTIA_VUE, 'packages' => ['@inertiajs/vue3', '@inertiajs/vue']],
        ['stack' => Stack::INERTIA_SVELTE, 'packages' => ['@inertiajs/svelte']],
    ];

    /**
     * @return EnumSet<Stack>
     */
    public function detect(PhpEcosystem $php, JsEcosystem $js): EnumSet
    {
        /** @var array<int, Stack> $stacks */
        $stacks = [];

        foreach (self::INERTIA_RULES as $rule) {
            foreach ($rule['packages'] as $package) {
                if ($js->uses($package)) {
                    $stacks[] = $rule['stack'];

                    continue 2;
                }
            }
        }

        if ($php->uses('livewire/livewire')) {
            $stacks[] = Stack::LIVEWIRE;
        }

        $hasApi = $php->uses('laravel/sanctum') || $php->uses('laravel/passport');
        $hasViewLayer = $stacks !== [] || $php->uses('laravel/folio') || $php->uses('livewire/volt');

        if ($hasApi && ! $hasViewLayer) {
            $stacks[] = Stack::API;
        }

        if ($stacks === []) {
            $stacks[] = Stack::BLADE;
        }

        return new EnumSet($stacks);
    }
}
