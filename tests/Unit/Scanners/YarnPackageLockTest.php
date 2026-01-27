<?php

use Laravel\Roster\Enums\Packages;
use Laravel\Roster\Package;
use Laravel\Roster\Scanners\YarnPackageLock;

use function Pest\testDirectory;

it('detects scoped npm packages in yarn.lock', function () {
    $path = testDirectory('fixtures/yarn-scoped-quoted/');
    $scanner = new YarnPackageLock($path);
    $items = $scanner->scan();

    $inertiaReact = $items->first(
        fn ($item) => $item instanceof Package && $item->package() === Packages::INERTIA_REACT
    );

    $tailwind = $items->first(
        fn ($item) => $item instanceof Package && $item->package() === Packages::TAILWINDCSS
    );

    expect($inertiaReact)->not->toBeNull('Expected @inertiajs/react to be detected')
        ->and($inertiaReact->version())->toEqual('2.0.12')
        ->and($tailwind)->not->toBeNull('Expected tailwindcss to be detected')
        ->and($tailwind->version())->toEqual('3.4.16');
});

it('detects unquoted scoped packages in yarn.lock', function () {
    $path = testDirectory('fixtures/yarn-scoped-unquoted/');
    $scanner = new YarnPackageLock($path);
    $items = $scanner->scan();

    $inertiaVue = $items->first(
        fn ($item) => $item instanceof Package && $item->package() === Packages::INERTIA_VUE
    );

    $alpine = $items->first(
        fn ($item) => $item instanceof Package && $item->package() === Packages::ALPINEJS
    );

    expect($inertiaVue)->not->toBeNull('Expected @inertiajs/vue3 to be detected')
        ->and($inertiaVue->version())->toEqual('2.0.5')
        ->and($alpine)->not->toBeNull('Expected alpinejs to be detected')
        ->and($alpine->version())->toEqual('3.4.4');
});
