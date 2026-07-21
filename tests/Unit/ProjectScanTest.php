<?php

declare(strict_types=1);

use Illuminate\Container\Container;
use Illuminate\Support\Str;
use Laravel\Roster\ProjectScan;

it('finishes an explicit base path with a directory separator', function (): void {
    expect(ProjectScan::normalizeBasePath('/tmp/project'))
        ->toBe('/tmp/project'.DIRECTORY_SEPARATOR);
});

it('falls back to the working directory when base_path() has no bound application', function (): void {
    $original = Container::getInstance();

    Container::setInstance(new Container);

    try {
        expect(ProjectScan::normalizeBasePath(null))
            ->toBe(Str::finish((string) getcwd(), DIRECTORY_SEPARATOR));
    } finally {
        Container::setInstance($original);
    }
});

it('reports the minimum PHP version supported by the project', function (): void {
    $base = tempBase();

    file_put_contents($base.'composer.json', json_encode([
        'require' => ['php' => '^8.2'],
    ]));

    $project = ProjectScan::scan($base);

    expect($project->minimumPhpVersion())->toBe('8.2')
        ->and($project->toArray()['minimumPhpVersion'])->toBe('8.2');

    cleanup($base);
});

it('falls back to the running PHP version without a usable project requirement', function (): void {
    $base = tempBase();

    expect(ProjectScan::scan($base)->minimumPhpVersion())
        ->toBe(PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION);

    cleanup($base);
});
