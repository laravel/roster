<?php

use Laravel\Roster\Detectors\StarterKitDetector;
use Laravel\Roster\Enums\StarterKit;

it('returns empty when no base marker exists', function () {
    $base = tempBase();

    $detection = (new StarterKitDetector($base))->detect(phpEcosystem([]));
    expect($detection->all())->toBe([]);

    cleanup($base);
});

it('detects react starter kit via app.tsx + pages', function () {
    $base = tempBase();
    touchFile($base.'routes/settings.php');
    touchFile($base.'resources/js/app.tsx');
    mkdir($base.'resources/js/pages', 0777, true);

    $detection = (new StarterKitDetector($base))->detect(phpEcosystem([]));
    expect($detection->uses(StarterKit::REACT))->toBeTrue();

    cleanup($base);
});

it('detects vue starter kit via app.ts + composables', function () {
    $base = tempBase();
    touchFile($base.'routes/settings.php');
    touchFile($base.'resources/js/app.ts');
    mkdir($base.'resources/js/composables', 0777, true);

    $detection = (new StarterKitDetector($base))->detect(phpEcosystem([]));
    expect($detection->uses(StarterKit::VUE))->toBeTrue();

    cleanup($base);
});

it('detects livewire starter kit via flux views + livewire actions', function () {
    $base = tempBase();
    touchFile($base.'routes/settings.php');
    mkdir($base.'resources/views/flux', 0777, true);
    mkdir($base.'app/Livewire/Actions', 0777, true);

    $detection = (new StarterKitDetector($base))->detect(phpEcosystem([]));
    expect($detection->uses(StarterKit::LIVEWIRE))->toBeTrue();

    cleanup($base);
});

it('promotes to _WORKOS when workos package + services config present', function () {
    $base = tempBase();
    touchFile($base.'routes/settings.php');
    touchFile($base.'resources/js/app.tsx');
    mkdir($base.'resources/js/pages', 0777, true);
    mkdir($base.'config', 0777, true);
    file_put_contents($base.'config'.DIRECTORY_SEPARATOR.'services.php', "<?php return ['workos' => []];");

    $detection = (new StarterKitDetector($base))->detect(phpEcosystem(['workos/workos-php']));
    expect($detection->uses(StarterKit::REACT_WORKOS))->toBeTrue();

    cleanup($base);
});
