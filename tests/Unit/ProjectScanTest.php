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
