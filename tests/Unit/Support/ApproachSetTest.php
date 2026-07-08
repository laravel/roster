<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Laravel\Roster\ApproachResult;
use Laravel\Roster\Enums\Approach;
use Laravel\Roster\Support\ApproachSet;

it('reports membership and exposes results keyed by approach value', function (): void {
    $result = new ApproachResult(
        approach: Approach::MassAssignmentFillable,
        confidence: 0.9,
        matched: 9,
        total: 10,
        paths: ['/app/Models/User.php'],
    );

    $set = new ApproachSet([$result]);

    expect($set->uses(Approach::MassAssignmentFillable))->toBeTrue()
        ->and($set->uses(Approach::MassAssignmentGuarded))->toBeFalse()
        ->and($set->all())->toBeInstanceOf(Collection::class)
        ->and($set->all()->get(Approach::MassAssignmentFillable->value))->toBe($result);
});

it('retrieves a typed result per approach', function (): void {
    $result = new ApproachResult(
        approach: Approach::MassAssignmentFillable,
        confidence: 0.9,
        matched: 9,
        total: 10,
        paths: ['/app/Models/User.php'],
    );

    $set = new ApproachSet([$result]);

    expect($set->result(Approach::MassAssignmentFillable))->toBe($result)
        ->and($set->result(Approach::MassAssignmentGuarded))->toBeNull();
});

it('checks all-of membership with usesAll', function (): void {
    $fillable = new ApproachResult(Approach::MassAssignmentFillable, 1.0, 5, 5, []);
    $formRequest = new ApproachResult(Approach::ValidationFormRequest, 1.0, 1, 1, []);

    $set = new ApproachSet([$fillable, $formRequest]);

    expect($set->usesAll([Approach::MassAssignmentFillable, Approach::ValidationFormRequest]))->toBeTrue()
        ->and($set->usesAll([Approach::MassAssignmentFillable, Approach::ValidationInline]))->toBeFalse()
        ->and($set->usesAll([]))->toBeTrue();
});

it('serializes results to an array shape', function (): void {
    $result = new ApproachResult(
        approach: Approach::ValidationPipeSyntax,
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

it('keeps distinct approaches side by side', function (): void {
    $set = new ApproachSet([
        new ApproachResult(Approach::MassAssignmentFillable, 1.0, 5, 5, []),
        new ApproachResult(Approach::ValidationFormRequest, 0.9, 9, 10, []),
    ]);

    expect($set->all())->toHaveCount(2)
        ->and($set->uses(Approach::MassAssignmentFillable))->toBeTrue()
        ->and($set->uses(Approach::ValidationFormRequest))->toBeTrue()
        ->and($set->result(Approach::MassAssignmentFillable)?->confidence)->toBe(1.0)
        ->and($set->result(Approach::ValidationFormRequest)?->confidence)->toBe(0.9);
});

it('keeps the first result when the same approach is reported twice', function (): void {
    $set = new ApproachSet([
        new ApproachResult(Approach::MassAssignmentFillable, 1.0, 5, 5, []),
        new ApproachResult(Approach::MassAssignmentFillable, 0.9, 9, 10, []),
    ]);

    expect($set->all())->toHaveCount(1)
        ->and($set->all()->first()?->confidence)->toBe(1.0)
        ->and($set->result(Approach::MassAssignmentFillable)?->confidence)->toBe(1.0);
});
