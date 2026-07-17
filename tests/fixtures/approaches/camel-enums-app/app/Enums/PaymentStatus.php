<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case authorized = 'authorized';
    case captured = 'captured';
}
