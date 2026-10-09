<?php

namespace App\Enums;

enum BudgetItemStatus: string
{
    case Draft = 'draft';
    case Committed = 'committed';
    case Cancelled = 'cancelled';
}
