<?php

namespace App\Enums;

enum Status: string
{
    case ACTIVE = 'active';
    case PAUSED = 'paused';

    public function label(): string
    {
        return "State: {$this->value}";
    }

    case ARCHIVED = 'archived';
    case DELETED = 'deleted';
    case PENDING_REVIEW = 'pending';
}
