<?php

declare(strict_types=1);

use Laravel\Roster\Scanners\Concerns\ParsesManifests;

it('pins normalizeVersion behavior for common constraint shapes', function (): void {
    $subject = new class
    {
        use ParsesManifests;

        public static function normalize(string $version): string
        {
            return self::normalizeVersion($version);
        }
    };

    expect($subject::normalize('1.2.3'))->toBe('1.2.3')
        ->and($subject::normalize('v2.0.5'))->toBe('2.0.5')
        ->and($subject::normalize('^3.4.0'))->toBe('3.4.0')
        ->and($subject::normalize('~1.2'))->toBe('1.2')
        ->and($subject::normalize('1.0.0-beta.1'))->toBe('1.0.0-beta.1')
        ->and($subject::normalize('14.3.0-canary.24'))->toBe('14.3.0')
        ->and($subject::normalize('1.0.0-nightly.1'))->toBe('1.0.0')
        ->and($subject::normalize('2.0.x-dev'))->toBe('2.0')
        ->and($subject::normalize('1.0.0 - 2.0.0'))->toBe('1.0.0')
        ->and($subject::normalize('>=1.2.0 <2.0.0'))->toBe('1.2.0')
        ->and($subject::normalize('workspace:*'))->toBe('')
        ->and($subject::normalize('*'))->toBe('');
});

it('classifies a dependency listed in both sections as production', function (): void {
    $subject = new class
    {
        use ParsesManifests;

        /**
         * @param  array<string, mixed>  $manifest
         * @return array<string, array{constraint: string, isDev: bool}>
         */
        public static function collect(array $manifest): array
        {
            return self::collectManifestDeps($manifest, [
                'devDependencies' => true,
                'dependencies' => false,
            ]);
        }
    };

    $deps = $subject::collect([
        'dependencies' => ['vite' => '^5.0'],
        'devDependencies' => ['vite' => '^5.0', 'eslint' => '^9.0'],
    ]);

    expect($deps['vite']['isDev'])->toBeFalse()
        ->and($deps['eslint']['isDev'])->toBeTrue();
});
