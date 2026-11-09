<?php

namespace App\Enums;

use App\Support\EnumValues;

enum FacilityStatus: string
{
    use EnumValues;

    case Operational = 'operational';
    case Maintenance = 'maintenance';
    case Emergency = 'emergency';
    case Closed = 'closed';
}
