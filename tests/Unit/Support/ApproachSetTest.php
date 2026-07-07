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
    $ddd = new ApproachResult(Approach::Ddd, 1.0, 1, 1, []);

    $set = new ApproachSet([$fillable, $ddd]);

    expect($set->usesAll([Approach::MassAssignmentFillable, Approach::Ddd]))->toBeTrue()
        ->and($set->usesAll([Approach::MassAssignmentFillable, Approach::Action]))->toBeFalse()
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

enum SetStyleA: string
{
    case STRICT = 'strict';
}

enum SetStyleB: string
{
    case STRICT = 'strict';
}

it('does not match a different enum sharing the same backing value', function (): void {
    $set = new ApproachSet([
        new ApproachResult(SetStyleB::STRICT, 1.0, 5, 5, []),
    ]);

    expect($set->uses(SetStyleB::STRICT))->toBeTrue()
        ->and($set->uses(SetStyleA::STRICT))->toBeFalse()
        ->and($set->result(SetStyleA::STRICT))->toBeNull()
        ->and($set->result(SetStyleB::STRICT)?->approach)->toBe(SetStyleB::STRICT);
});

it('keeps both results when enums share a backing value', function (): void {
    $set = new ApproachSet([
        new ApproachResult(SetStyleA::STRICT, 1.0, 5, 5, []),
        new ApproachResult(SetStyleB::STRICT, 0.9, 9, 10, []),
    ]);

    expect($set->all())->toHaveCount(2)
        ->and($set->uses(SetStyleA::STRICT))->toBeTrue()
        ->and($set->uses(SetStyleB::STRICT))->toBeTrue()
        ->and($set->result(SetStyleA::STRICT)?->confidence)->toBe(1.0)
        ->and($set->result(SetStyleB::STRICT)?->confidence)->toBe(0.9);
});
