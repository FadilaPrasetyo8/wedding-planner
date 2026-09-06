<?php

namespace App\Enums;

enum WeddingMemberRole: string
{
    case Owner = 'owner';
    case Editor = 'editor';
    case Viewer = 'viewer';
}
