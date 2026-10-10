<?php

namespace App\Enums;

enum GuestRsvpStatus: string
{
    case Pending = 'pending';
    case Attending = 'attending';
    case NotAttending = 'not_attending';
    case Tentative = 'tentative';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu jawaban',
            self::Attending => 'Hadir',
            self::NotAttending => 'Tidak hadir',
            self::Tentative => 'Masih ragu',
        };
    }
}
