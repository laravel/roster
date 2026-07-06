<?php

declare(strict_types=1);

use Laravel\Roster\ApproachResult;
use Laravel\Roster\Detectors\ApproachesDetector;
use Laravel\Roster\Enums\Approach;
use Laravel\Roster\Project;
use Laravel\Roster\Support\ApproachSet;

enum CustomConvention: string
{
    case THIN_MODELS = 'custom.thin-models';
    case FAT_MODELS = 'custom.fat-models';
}

function detectApproaches(string $app): ApproachSet
{
    return new ApproachSet(ApproachesDetector::detect(
        dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'fixtures'.DIRECTORY_SEPARATOR.'approaches'.DIRECTORY_SEPARATOR.$app,
    ));
}

function writeModel(string $base, string $name, string $property): void
{
    touchFile($base.'app/Models/'.$name.'.php');
    file_put_contents(
        $base.'app/Models/'.$name.'.php',
        "<?php\n\nnamespace App\\Models;\n\nclass {$name}\n{\n    protected \${$property} = [];\n}\n",
    );
}

it('detects fillable as the dominant mass-assignment style', function (): void {
    $approaches = detectApproaches('fillable-models-app');

    expect($approaches->uses(Approach::MASS_ASSIGNMENT_FILLABLE))->toBeTrue()
        ->and($approaches->uses(Approach::MASS_ASSIGNMENT_GUARDED))->toBeFalse();

    /** @var ApproachResult $result */
    $result = $approaches->all()->get(Approach::MASS_ASSIGNMENT_FILLABLE->value);

    expect($result->matched)->toBe(6)
        ->and($result->total)->toBe(6)
        ->and($result->confidence)->toBe(1.0)
        ->and($result->paths)->toHaveCount(6)
        ->and($result->paths[0])->toContain('Models');
});

it('detects guarded as the dominant mass-assignment style', function (): void {
    $approaches = detectApproaches('guarded-models-app');

    expect($approaches->uses(Approach::MASS_ASSIGNMENT_GUARDED))->toBeTrue()
        ->and($approaches->uses(Approach::MASS_ASSIGNMENT_FILLABLE))->toBeFalse();

    /** @var ApproachResult $result */
    $result = $approaches->all()->get(Approach::MASS_ASSIGNMENT_GUARDED->value);

    expect($result->matched)->toBe(5)
        ->and($result->total)->toBe(5);
});

it('stays silent when models are split between fillable and guarded', function (): void {
    $approaches = detectApproaches('mixed-models-app');

    expect($approaches->uses(Approach::MASS_ASSIGNMENT_FILLABLE))->toBeFalse()
        ->and($approaches->uses(Approach::MASS_ASSIGNMENT_GUARDED))->toBeFalse()
        ->and($approaches->all())->toBeEmpty();
});

it('detects screaming snake enum case naming with one vote per case', function (): void {
    $approaches = detectApproaches('screaming-enums-app');

    expect($approaches->uses(Approach::ENUM_CASE_SCREAMING_SNAKE))->toBeTrue();

    /** @var ApproachResult $result */
    $result = $approaches->all()->get(Approach::ENUM_CASE_SCREAMING_SNAKE->value);

    expect($result->matched)->toBe(5)
        ->and($result->total)->toBe(5)
        ->and($result->paths)->toHaveCount(2);
});

it('does not count switch case labels as enum cases', function (): void {
    $approaches = detectApproaches('enum-switch-app');

    expect($approaches->uses(Approach::ENUM_CASE_SCREAMING_SNAKE))->toBeTrue();

    /** @var ApproachResult $result */
    $result = $approaches->all()->get(Approach::ENUM_CASE_SCREAMING_SNAKE->value);

    expect($result->matched)->toBe(5)
        ->and($result->total)->toBe(5);
});

it('detects pascal enum case naming', function (): void {
    $approaches = detectApproaches('pascal-enums-app');

    expect($approaches->uses(Approach::ENUM_CASE_PASCAL))->toBeTrue()
        ->and($approaches->uses(Approach::ENUM_CASE_SCREAMING_SNAKE))->toBeFalse();

    /** @var ApproachResult $result */
    $result = $approaches->all()->get(Approach::ENUM_CASE_PASCAL->value);

    expect($result->matched)->toBe(5)
        ->and($result->total)->toBe(5);
});

it('detects camel enum case naming', function (): void {
    $approaches = detectApproaches('camel-enums-app');

    expect($approaches->uses(Approach::ENUM_CASE_CAMEL))->toBeTrue();

    /** @var ApproachResult $result */
    $result = $approaches->all()->get(Approach::ENUM_CASE_CAMEL->value);

    expect($result->matched)->toBe(5)
        ->and($result->total)->toBe(5);
});

it('counts enum cases declared after a method with string interpolation', function (): void {
    $approaches = detectApproaches('interpolated-enums-app');

    expect($approaches->uses(Approach::ENUM_CASE_SCREAMING_SNAKE))->toBeTrue();

    /** @var ApproachResult $result */
    $result = $approaches->all()->get(Approach::ENUM_CASE_SCREAMING_SNAKE->value);

    expect($result->matched)->toBe(5)
        ->and($result->total)->toBe(5);
});

it('detects pipe-delimited validation rule syntax', function (): void {
    $approaches = detectApproaches('pipe-validation-app');

    expect($approaches->uses(Approach::VALIDATION_PIPE_SYNTAX))->toBeTrue()
        ->and($approaches->uses(Approach::VALIDATION_ARRAY_SYNTAX))->toBeFalse();

    /** @var ApproachResult $result */
    $result = $approaches->all()->get(Approach::VALIDATION_PIPE_SYNTAX->value);

    expect($result->matched)->toBe(5)
        ->and($result->total)->toBe(5)
        ->and($result->paths[0])->toContain('Requests');
});

it('detects array validation rule syntax', function (): void {
    $approaches = detectApproaches('array-validation-app');

    expect($approaches->uses(Approach::VALIDATION_ARRAY_SYNTAX))->toBeTrue()
        ->and($approaches->uses(Approach::VALIDATION_PIPE_SYNTAX))->toBeFalse();

    /** @var ApproachResult $result */
    $result = $approaches->all()->get(Approach::VALIDATION_ARRAY_SYNTAX->value);

    expect($result->matched)->toBe(5)
        ->and($result->total)->toBe(5);
});

it('detects the #[Scope] attribute style with one vote per scope', function (): void {
    $approaches = detectApproaches('attribute-scopes-app');

    expect($approaches->uses(Approach::QUERY_SCOPE_ATTRIBUTE))->toBeTrue()
        ->and($approaches->uses(Approach::QUERY_SCOPE_METHOD))->toBeFalse();

    /** @var ApproachResult $result */
    $result = $approaches->all()->get(Approach::QUERY_SCOPE_ATTRIBUTE->value);

    expect($result->matched)->toBe(5)
        ->and($result->total)->toBe(5);
});

it('detects the scopeXxx() naming style', function (): void {
    $approaches = detectApproaches('naming-scopes-app');

    expect($approaches->uses(Approach::QUERY_SCOPE_METHOD))->toBeTrue()
        ->and($approaches->uses(Approach::QUERY_SCOPE_ATTRIBUTE))->toBeFalse();
});

it('samples Models directories anywhere beneath a PSR-4 root', function (): void {
    $approaches = detectApproaches('nested-models-app');

    expect($approaches->uses(Approach::MASS_ASSIGNMENT_FILLABLE))->toBeTrue();

    /** @var ApproachResult $result */
    $result = $approaches->all()->get(Approach::MASS_ASSIGNMENT_FILLABLE->value);

    expect($result->matched)->toBe(5)
        ->and($result->paths[0])->toContain('src/Domain/Orders/Models');
});

it('stays silent when there are too few votes', function (): void {
    expect(detectApproaches('carbon-app')->all())->toBeEmpty();
});

it('detects directory conventions with full confidence', function (): void {
    $base = tempBase();
    mkdir($base.'app'.DIRECTORY_SEPARATOR.'Actions', 0777, true);

    $approaches = new ApproachSet(ApproachesDetector::detect(rtrim($base, DIRECTORY_SEPARATOR)));

    expect($approaches->uses(Approach::ACTION))->toBeTrue()
        ->and($approaches->uses(Approach::DDD))->toBeFalse();

    /** @var ApproachResult $result */
    $result = $approaches->all()->get(Approach::ACTION->value);

    expect($result->confidence)->toBe(1.0)
        ->and($result->paths[0])->toContain('Actions');

    cleanup($base);
});

it('detects the modular convention from any module directory', function (): void {
    foreach (['modules', 'Modules', 'app-modules'] as $dir) {
        $base = tempBase();
        mkdir($base.$dir);

        $approaches = new ApproachSet(ApproachesDetector::detect($base));

        expect($approaches->uses(Approach::MODULAR))->toBeTrue();

        cleanup($base);
    }
});

it('reports no directory conventions on a bare directory', function (): void {
    $base = tempBase();

    expect((new ApproachSet(ApproachesDetector::detect($base)))->all())->toBeEmpty();

    cleanup($base);
});

it('rejects a 4/5 majority as insufficient evidence', function (): void {
    $base = tempBase();

    foreach (['Alpha', 'Bravo', 'Charlie', 'Delta'] as $name) {
        writeModel($base, $name, 'fillable');
    }

    writeModel($base, 'Hotel', 'guarded');

    $approaches = new ApproachSet(ApproachesDetector::detect($base));

    expect($approaches->uses(Approach::MASS_ASSIGNMENT_FILLABLE))->toBeFalse()
        ->and($approaches->uses(Approach::MASS_ASSIGNMENT_GUARDED))->toBeFalse();

    cleanup($base);
});

it('accepts a 90/100 majority', function (): void {
    $base = tempBase();

    for ($i = 1; $i <= 90; $i++) {
        writeModel($base, 'Fillable'.$i, 'fillable');
    }

    for ($i = 1; $i <= 10; $i++) {
        writeModel($base, 'Guarded'.$i, 'guarded');
    }

    $approaches = new ApproachSet(ApproachesDetector::detect($base));

    expect($approaches->uses(Approach::MASS_ASSIGNMENT_FILLABLE))->toBeTrue();

    /** @var ApproachResult $result */
    $result = $approaches->all()->get(Approach::MASS_ASSIGNMENT_FILLABLE->value);

    expect($result->matched)->toBe(90)
        ->and($result->total)->toBe(100)
        ->and($result->confidence)->toBe(0.9)
        ->and($result->paths)->toHaveCount(100);

    cleanup($base);
});

it('skips models declaring both fillable and guarded', function (): void {
    $base = tempBase();

    foreach (['Alpha', 'Bravo', 'Charlie', 'Delta', 'Hotel'] as $name) {
        writeModel($base, $name, 'fillable');
    }

    touchFile($base.'app/Models/Both.php');
    file_put_contents(
        $base.'app/Models/Both.php',
        "<?php\n\nnamespace App\\Models;\n\nclass Both\n{\n    protected \$fillable = [];\n\n    protected \$guarded = [];\n}\n",
    );

    $approaches = new ApproachSet(ApproachesDetector::detect($base));

    /** @var ApproachResult $result */
    $result = $approaches->all()->get(Approach::MASS_ASSIGNMENT_FILLABLE->value);

    expect($result->matched)->toBe(5)
        ->and($result->total)->toBe(5)
        ->and($result->paths)->toHaveCount(5);

    cleanup($base);
});

it('never lets vendor or node_modules code vote', function (): void {
    $base = tempBase();

    file_put_contents($base.'composer.json', json_encode([
        'autoload' => ['psr-4' => ['App\\' => '.']],
    ]));

    foreach (['vendor/acme/pkg/src', 'node_modules/pkg'] as $dir) {
        for ($i = 1; $i <= 5; $i++) {
            touchFile($base.$dir.'/Models/Dep'.$i.'.php');
            file_put_contents(
                $base.$dir.'/Models/Dep'.$i.'.php',
                "<?php\n\nclass Dep{$i}\n{\n    protected \$guarded = [];\n}\n",
            );
        }
    }

    $approaches = new ApproachSet(ApproachesDetector::detect($base));

    expect($approaches->all())->toBeEmpty();

    cleanup($base);
});

it('accepts an array of approaches with any-of semantics', function (): void {
    $approaches = detectApproaches('fillable-models-app');

    expect($approaches->uses([Approach::MASS_ASSIGNMENT_FILLABLE, Approach::MASS_ASSIGNMENT_GUARDED]))->toBeTrue()
        ->and($approaches->uses([Approach::MASS_ASSIGNMENT_GUARDED, Approach::ENUM_CASE_CAMEL]))->toBeFalse();
});

it('detects a registered custom convention with its own enum', function (): void {
    ApproachesDetector::extend(
        fn (string $contents, string $path): ?CustomConvention => str_contains($contents, '$fillable')
            ? CustomConvention::THIN_MODELS
            : null,
        in: 'Models',
    );

    $approaches = detectApproaches('fillable-models-app');

    expect($approaches->uses(CustomConvention::THIN_MODELS))->toBeTrue()
        ->and($approaches->uses(CustomConvention::FAT_MODELS))->toBeFalse();

    /** @var ApproachResult $result */
    $result = $approaches->result(CustomConvention::THIN_MODELS);

    expect($result->approach)->toBe(CustomConvention::THIN_MODELS)
        ->and($result->matched)->toBe(6)
        ->and($result->total)->toBe(6)
        ->and($result->confidence)->toBe(1.0)
        ->and($result->paths[0])->toContain('Models');

    ApproachesDetector::flushExtensions();
});

it('applies the evidence thresholds to custom conventions', function (): void {
    ApproachesDetector::extend(
        fn (string $contents): ?CustomConvention => match (true) {
            str_contains($contents, '$fillable') => CustomConvention::THIN_MODELS,
            str_contains($contents, '$guarded') => CustomConvention::FAT_MODELS,
            default => null,
        },
        in: 'Models',
    );

    $base = tempBase();

    foreach (['Alpha', 'Bravo', 'Charlie'] as $name) {
        writeModel($base, $name, 'fillable');
    }

    foreach (['Delta', 'Hotel'] as $name) {
        writeModel($base, $name, 'guarded');
    }

    $approaches = new ApproachSet(ApproachesDetector::detect($base));

    expect($approaches->uses(CustomConvention::THIN_MODELS))->toBeFalse()
        ->and($approaches->uses(CustomConvention::FAT_MODELS))->toBeFalse();

    ApproachesDetector::flushExtensions();
    cleanup($base);
});

it('dedupes files reachable through overlapping source roots', function (): void {
    $base = tempBase();

    file_put_contents($base.'composer.json', json_encode([
        'autoload' => ['psr-4' => ['App\\' => 'app/', 'App\\Models\\' => 'app/Models/']],
    ]));

    foreach (['Alpha', 'Bravo', 'Charlie', 'Delta', 'Hotel'] as $name) {
        writeModel($base, $name, 'fillable');
    }

    $approaches = new ApproachSet(ApproachesDetector::detect($base));

    /** @var ApproachResult $result */
    $result = $approaches->all()->get(Approach::MASS_ASSIGNMENT_FILLABLE->value);

    expect($result->total)->toBe(5);

    cleanup($base);
});

it('recomputes memoized approaches when a convention is registered later', function (): void {
    $project = Project::scan(
        dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'fixtures'.DIRECTORY_SEPARATOR.'approaches'.DIRECTORY_SEPARATOR.'fillable-models-app',
    );

    expect($project->approaches()->uses(CustomConvention::THIN_MODELS))->toBeFalse();

    ApproachesDetector::extend(
        fn (string $contents): ?CustomConvention => str_contains($contents, '$fillable')
            ? CustomConvention::THIN_MODELS
            : null,
        in: 'Models',
    );

    expect($project->approaches()->uses(CustomConvention::THIN_MODELS))->toBeTrue();

    ApproachesDetector::flushExtensions();
});
