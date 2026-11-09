<?php

namespace App\Enums;

use App\Support\EnumValues;

enum Role: string
{
    use EnumValues;

    case Student = 'student';
    case Employee = 'employee';
    case Admin = 'admin';

    public function isStaff(): bool
    {
        return $this === self::Employee || $this === self::Admin;
    }
}
