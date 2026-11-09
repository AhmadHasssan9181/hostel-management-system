<?php

namespace App\Enums;

use App\Support\EnumValues;

enum RoomStatus: string
{
    use EnumValues;

    case Available = 'available';
    case Full = 'full';
    case Maintenance = 'maintenance';
}
