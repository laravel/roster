<?php

declare(strict_types=1);

namespace Laravel\Roster\Enums;

enum Approach: string
{
    case ACTION = 'action';
    case DDD = 'ddd';
    case MODULAR = 'modular';
    case MASS_ASSIGNMENT_FILLABLE = 'mass-assignment-fillable';
    case MASS_ASSIGNMENT_GUARDED = 'mass-assignment-guarded';
    case ENUM_CASE_SCREAMING_SNAKE = 'enum-case-screaming-snake';
    case ENUM_CASE_PASCAL = 'enum-case-pascal';
    case ENUM_CASE_CAMEL = 'enum-case-camel';
    case VALIDATION_PIPE_SYNTAX = 'validation-pipe-syntax';
    case VALIDATION_ARRAY_SYNTAX = 'validation-array-syntax';
    case QUERY_SCOPE_ATTRIBUTE = 'query-scope-attribute';
    case QUERY_SCOPE_METHOD = 'query-scope-method';
}
