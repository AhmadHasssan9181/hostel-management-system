<?php

namespace App\Enums;

use App\Support\EnumValues;

enum BookingStatus: string
{
    use EnumValues;

    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case CheckedOut = 'checked_out';
}
