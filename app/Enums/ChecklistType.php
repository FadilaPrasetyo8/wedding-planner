<?php

namespace App\Enums;

enum ChecklistType: string
{
    case Seserahan = 'seserahan';
    case Administration = 'administration';

    public function label(): string
    {
        return match ($this) {
            self::Seserahan => 'Seserahan',
            self::Administration => 'Administrasi',
        };
    }
}
