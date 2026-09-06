<?php

namespace App\Enums;

enum WeddingStatus: string
{
    case Planning = 'planning';
    case Completed = 'completed';
    case Archived = 'archived';
}
