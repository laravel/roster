<?php

declare(strict_types=1);

namespace Laravel\Roster\Detectors;

use Laravel\Roster\Ecosystems\JsEcosystem;
use Laravel\Roster\Ecosystems\PhpEcosystem;
use Laravel\Roster\Enums\Stack;

class StackDetector
{
    /** @var list<array{stack: Stack, packages: list<string>}> */
    private const INERTIA_RULES = [
        ['stack' => Stack::INERTIA_REACT, 'packages' => ['@inertiajs/react']],
        ['stack' => Stack::INERTIA_VUE, 'packages' => ['@inertiajs/vue3', '@inertiajs/vue']],
        ['stack' => Stack::INERTIA_SVELTE, 'packages' => ['@inertiajs/svelte']],
    ];

    /**
     * @return list<Stack>
     */
    public static function detect(PhpEcosystem $php, JsEcosystem $js): array
    {
        $stacks = [];

        foreach (self::INERTIA_RULES as $rule) {
            foreach ($rule['packages'] as $package) {
                if ($js->usesDirect($package)) {
                    $stacks[] = $rule['stack'];

                    continue 2;
                }
            }
        }

        if ($php->usesDirect('livewire/livewire')) {
            $stacks[] = Stack::LIVEWIRE;
        }

        $hasApi = $php->usesDirect('laravel/sanctum') || $php->usesDirect('laravel/passport');
        $hasViewLayer = $stacks !== [] || $php->usesDirect('laravel/folio') || $php->usesDirect('livewire/volt');

        if ($hasApi && ! $hasViewLayer) {
            $stacks[] = Stack::API;
        }

        if ($stacks === []) {
            $stacks[] = Stack::BLADE;
        }

        return $stacks;
    }
}
