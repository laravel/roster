<?php

declare(strict_types=1);

use Laravel\Roster\Detectors\StarterKitDetector;
use Laravel\Roster\Enums\StarterKit;

it('returns null when no base marker exists', function (): void {
    $base = tempBase();

    $kit = (new StarterKitDetector($base))->detect(phpEcosystem([]));
    expect($kit)->toBeNull();

    cleanup($base);
});

it('detects react starter kit via app.tsx + pages', function (): void {
    $base = tempBase();
    touchFile($base.'routes/settings.php');
    touchFile($base.'resources/js/app.tsx');
    mkdir($base.'resources/js/pages', 0777, true);

    $kit = (new StarterKitDetector($base))->detect(phpEcosystem([]));
    expect($kit?->is(StarterKit::REACT))->toBeTrue();

    cleanup($base);
});

it('detects vue starter kit via app.ts + composables', function (): void {
    $base = tempBase();
    touchFile($base.'routes/settings.php');
    touchFile($base.'resources/js/app.ts');
    mkdir($base.'resources/js/composables', 0777, true);

    $kit = (new StarterKitDetector($base))->detect(phpEcosystem([]));
    expect($kit?->is(StarterKit::VUE))->toBeTrue();

    cleanup($base);
});

it('detects livewire starter kit via flux views + livewire actions', function (): void {
    $base = tempBase();
    touchFile($base.'routes/settings.php');
    mkdir($base.'resources/views/flux', 0777, true);
    mkdir($base.'app/Livewire/Actions', 0777, true);

    $kit = (new StarterKitDetector($base))->detect(phpEcosystem([]));
    expect($kit?->is(StarterKit::LIVEWIRE))->toBeTrue();

    cleanup($base);
});

it('promotes to _WORKOS when workos package + services config present', function (): void {
    $base = tempBase();
    touchFile($base.'routes/settings.php');
    touchFile($base.'resources/js/app.tsx');
    mkdir($base.'resources/js/pages', 0777, true);
    mkdir($base.'config', 0777, true);
    file_put_contents($base.'config'.DIRECTORY_SEPARATOR.'services.php', "<?php return ['workos' => []];");

    $kit = (new StarterKitDetector($base))->detect(phpEcosystem(['workos/workos-php']));
    expect($kit?->is(StarterKit::REACT_WORKOS))->toBeTrue();

    cleanup($base);
});
