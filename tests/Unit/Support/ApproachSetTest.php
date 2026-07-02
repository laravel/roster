<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Laravel\Roster\ApproachResult;
use Laravel\Roster\Enums\Approach;
use Laravel\Roster\Support\ApproachSet;

it('reports membership and exposes results keyed by approach value', function (): void {
    $result = new ApproachResult(
        approach: Approach::MASS_ASSIGNMENT_FILLABLE,
        confidence: 0.9,
        matched: 9,
        total: 10,
        paths: ['/app/Models/User.php'],
    );

    $set = new ApproachSet([$result]);

    expect($set->uses(Approach::MASS_ASSIGNMENT_FILLABLE))->toBeTrue()
        ->and($set->uses(Approach::MASS_ASSIGNMENT_GUARDED))->toBeFalse()
        ->and($set->all())->toBeInstanceOf(Collection::class)
        ->and($set->all()->get(Approach::MASS_ASSIGNMENT_FILLABLE->value))->toBe($result);
});

it('is empty when nothing was detected', function (): void {
    $set = new ApproachSet([]);

    expect($set->all())->toBeEmpty()
        ->and($set->uses(Approach::ENUM_CASE_SCREAMING_SNAKE))->toBeFalse();
});

it('serializes results to an array shape', function (): void {
    $result = new ApproachResult(
        approach: Approach::VALIDATION_PIPE_SYNTAX,
        confidence: 1.0,
        matched: 5,
        total: 5,
        paths: ['/app/Http/Requests/StorePostRequest.php'],
    );

    expect($result->toArray())->toBe([
        'approach' => 'validation-pipe-syntax',
        'confidence' => 1.0,
        'matched' => 5,
        'total' => 5,
        'paths' => ['/app/Http/Requests/StorePostRequest.php'],
    ]);
});
