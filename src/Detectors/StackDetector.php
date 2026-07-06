<?php

namespace Laravel\Roster\Detectors;

use Laravel\Roster\Ecosystems\JsEcosystem;
use Laravel\Roster\Ecosystems\PhpEcosystem;
use Laravel\Roster\Enums\Stack;
use Laravel\Roster\Support\EnumSet;

class StackDetector
{
    /**
     * @return EnumSet<Stack>
     */
    public function detect(PhpEcosystem $php, JsEcosystem $js): EnumSet
    {
        /** @var array<int, Stack> $stacks */
        $stacks = [];

        if ($js->uses('@inertiajs/react') || $js->uses('inertia-react')) {
            $stacks[] = Stack::INERTIA_REACT;
        }

        if ($js->uses('@inertiajs/vue3') || $js->uses('@inertiajs/vue') || $js->uses('inertia-vue')) {
            $stacks[] = Stack::INERTIA_VUE;
        }

        if ($js->uses('@inertiajs/svelte') || $js->uses('inertia-svelte')) {
            $stacks[] = Stack::INERTIA_SVELTE;
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
