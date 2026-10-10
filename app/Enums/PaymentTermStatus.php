<?php

namespace App\Enums;

enum PaymentTermStatus: string
{
    case Planned = 'planned';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';
    case Cancelled = 'cancelled';
}
