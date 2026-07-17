<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Authorized = 'authorized';
    case Captured = 'captured';
}
