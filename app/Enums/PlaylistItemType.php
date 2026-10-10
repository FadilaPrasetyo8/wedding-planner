<?php

namespace App\Enums;

enum PlaylistItemType: string
{
    case Song = 'song';
    case Request = 'request';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }

    public function label(): string
    {
        return match ($this) {
            self::Song => 'Lagu',
            self::Request => 'Permintaan',
        };
    }
}
