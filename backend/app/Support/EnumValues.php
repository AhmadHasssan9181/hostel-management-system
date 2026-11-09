<?php

namespace App\Support;

trait EnumValues
{
    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn ($case) => $case->value, self::cases());
    }
}
