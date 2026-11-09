<?php

namespace App\Enums;

use App\Support\EnumValues;

enum FeeStatus: string
{
    use EnumValues;

    case Unpaid = 'unpaid';
    case PendingVerification = 'pending_verification';
    case Paid = 'paid';
    case Overdue = 'overdue';
    case Waived = 'waived';
}
