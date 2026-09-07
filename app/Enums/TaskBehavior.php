<?php

namespace App\Enums;

enum TaskBehavior: string
{
    case Todo = 'todo';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
