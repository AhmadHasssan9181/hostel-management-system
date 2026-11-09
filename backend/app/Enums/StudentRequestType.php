<?php

namespace App\Enums;

use App\Support\EnumValues;

enum StudentRequestType: string
{
    use EnumValues;

    case Leave = 'leave';
    case RoomChange = 'room_change';
    case Facility = 'facility';
    case Other = 'other';
}
