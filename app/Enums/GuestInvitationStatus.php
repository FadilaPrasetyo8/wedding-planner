<?php

namespace App\Enums;

enum GuestInvitationStatus: string
{
    case NotSent = 'not_sent';
    case Sent = 'sent';
    case Delivered = 'delivered';
    case Failed = 'failed';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }

    public function label(): string
    {
        return match ($this) {
            self::NotSent => 'Belum dikirim',
            self::Sent => 'Terkirim',
            self::Delivered => 'Diterima',
            self::Failed => 'Gagal',
        };
    }
}
