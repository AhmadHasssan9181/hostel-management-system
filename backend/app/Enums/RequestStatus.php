<?php

namespace App\Enums;

use App\Support\EnumValues;

enum RequestStatus: string
{
    use EnumValues;

    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
}
