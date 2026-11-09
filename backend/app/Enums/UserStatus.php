<?php

namespace App\Enums;

use App\Support\EnumValues;

enum UserStatus: string
{
    use EnumValues;

    case Active = 'active';
    case Suspended = 'suspended';
    case Pending = 'pending';
}
