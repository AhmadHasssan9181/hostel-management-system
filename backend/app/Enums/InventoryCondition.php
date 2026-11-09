<?php

namespace App\Enums;

use App\Support\EnumValues;

enum InventoryCondition: string
{
    use EnumValues;

    case Good = 'good';
    case Damaged = 'damaged';
    case Broken = 'broken';
    case Lost = 'lost';
}
