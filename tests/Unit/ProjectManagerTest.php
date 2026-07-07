<?php

declare(strict_types=1);

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Contracts\Cache\Factory;
use Laravel\Roster\Enums\Agent;
use Laravel\Roster\Enums\Approach;
use Laravel\Roster\ProjectManager;
use Tests\TestCase;

uses(TestCase::class);

it('invalidates the cache when a project marker appears', function (): void {
    config()->set('cache.default', 'array');

    $base = tempBase();

    $manager = new ProjectManager;

    expect($manager->scan($base)->agents()->all())->toBe([]);

    mkdir($base.'.claude');

    expect($manager->scan($base)->agents()->uses(Agent::ClaudeCode))->toBeTrue();

    cleanup($base);
});

it('invalidates the cache when lockfile contents change', function (): void {
    config()->set('cache.default', 'array');

    $base = tempBase();
    file_put_contents($base.'package.json', json_encode(['dependencies' => ['vue' => '^3.0']]));

    $manager = new ProjectManager;

    expect($manager->scan($base)->js()->uses('vue'))->toBeTrue();

    file_put_contents($base.'package.json', json_encode(['dependencies' => ['react' => '^19.0']]));

    $rescanned = $manager->scan($base);
    expect($rescanned->js()->uses('react'))->toBeTrue();
    expect($rescanned->js()->uses('vue'))->toBeFalse();

    cleanup($base);
});

it('returns the cached project for an unchanged directory', function (): void {
    config()->set('cache.default', 'array');

    $base = tempBase();

    $manager = new ProjectManager;

    expect($manager->scan($base))->toBe($manager->scan($base));

    cleanup($base);
});

it('fresh bypasses the cache and forces a re-read', function (): void {
    config()->set('cache.default', 'array');

    $base = tempBase();

    $manager = new ProjectManager;
    $cached = $manager->scan($base);

    expect($manager->fresh($base))->not->toBe($cached);

    cleanup($base);
});

it('scans without a usable cache driver', function (): void {
    config()->set('cache.default', 'null');

    $base = tempBase();
    file_put_contents($base.'package.json', json_encode(['dependencies' => ['vue' => '^3.0']]));

    expect((new ProjectManager)->scan($base)->js()->uses('vue'))->toBeTrue();

    cleanup($base);
});

it('serves projects through a cache store that actually serializes', function (): void {
    config()->set('cache.default', 'array');
    config()->set('cache.stores.array.serialize', true);

    $base = dirname(__DIR__).DIRECTORY_SEPARATOR.'fixtures'.DIRECTORY_SEPARATOR.'approaches'.DIRECTORY_SEPARATOR.'fillable-models-app';

    $scanned = (new ProjectManager)->scan($base);
    $scanned->approaches();

    $restored = (new ProjectManager)->scan($base);

    expect($restored)->not->toBe($scanned)
        ->and($restored->approaches()->uses(Approach::MassAssignmentFillable))->toBeTrue();
});

it('overwrites a corrupt cache entry instead of rescanning forever', function (): void {
    $repository = new class(new ArrayStore) extends Repository
    {
        public bool $corrupt = true;

        public function get($key, $default = null): mixed
        {
            if ($this->corrupt) {
                $this->corrupt = false;

                throw new RuntimeException('unserialize failed');
            }

            return parent::get($key, $default);
        }
    };

    $factory = new class($repository) implements Factory
    {
        public function __construct(private Repository $repository) {}

        public function store($name = null): Repository
        {
            return $this->repository;
        }
    };

    app()->instance('cache', $factory);

    $base = tempBase();

    $manager = new ProjectManager;
    $scanned = $manager->scan($base);

    expect($manager->scan($base))->toBe($scanned);

    app()->forgetInstance('cache');
    cleanup($base);
});

it('still scans when the cache store throws', function (): void {
    $broken = new class implements Factory
    {
        public function store($name = null): void
        {
            throw new RuntimeException('cache is down');
        }
    };

    app()->instance('cache', $broken);

    $base = tempBase();
    file_put_contents($base.'package.json', json_encode(['dependencies' => ['vue' => '^3.0']]));

    expect((new ProjectManager)->scan($base)->js()->uses('vue'))->toBeTrue();

    app()->forgetInstance('cache');
    cleanup($base);
});

it('does not repoint the default instance when scanning another directory', function (): void {
    config()->set('cache.default', 'array');

    $base = tempBase();
    mkdir($base.'.claude');

    $manager = new ProjectManager;
    $default = $manager->instance();

    expect($manager->scan($base)->agents()->uses(Agent::ClaudeCode))->toBeTrue();

    expect($manager->instance())->toBe($default);
    expect($manager->agents()->uses(Agent::ClaudeCode))->toBeFalse();

    cleanup($base);
});

it('memoizes the default instance', function (): void {
    $manager = new ProjectManager;

    expect($manager->instance())->toBe($manager->instance());
});
