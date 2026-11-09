<?php

namespace App\Enums;

use App\Support\EnumValues;

enum ComplaintStatus: string
{
    use EnumValues;

    case Open = 'open';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
    case Closed = 'closed';
    case Escalated = 'escalated';
}
