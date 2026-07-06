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
    case VALIDATION_INLINE = 'validation-inline';
    case VALIDATION_FORM_REQUEST = 'validation-form-request';
    case MODEL_ATTRIBUTE_SYNTAX = 'model-attribute-syntax';
    case MODEL_PROPERTY_SYNTAX = 'model-property-syntax';
    case CONTROLLER_INVOKABLE = 'controller-invokable';
    case CONTROLLER_MULTI_ACTION = 'controller-multi-action';
    case COMMAND_ATTRIBUTE_SYNTAX = 'command-attribute-syntax';
    case COMMAND_PROPERTY_SYNTAX = 'command-property-syntax';
    case HTTP_CLIENT_THROW = 'http-client-throw';
    case HTTP_CLIENT_STATUS_CHECK = 'http-client-status-check';
    case NOTIFICATION_NOTIFY = 'notification-notify';
    case NOTIFICATION_FACADE = 'notification-facade';
    case AUTHORIZATION_GATE = 'authorization-gate';
    case AUTHORIZATION_USER_CAN = 'authorization-user-can';
    case AUTHORIZATION_ATTRIBUTE = 'authorization-attribute';
    case AUTHORIZATION_TRAIT = 'authorization-trait';
    case AUTH_FACADE = 'auth-facade';
    case AUTH_REQUEST = 'auth-request';
    case AUTH_HELPER = 'auth-helper';
    case MODEL_UUID_KEYS = 'model-uuid-keys';
    case MODEL_ULID_KEYS = 'model-ulid-keys';
}
