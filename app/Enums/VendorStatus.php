<?php

namespace App\Enums;

enum VendorStatus: string
{
    case Candidate = 'candidate';
    case Contacted = 'contacted';
    case Negotiating = 'negotiating';
    case Selected = 'selected';
    case Rejected = 'rejected';
    case Done = 'done';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }

    public function label(): string
    {
        return match ($this) {
            self::Candidate => 'Kandidat',
            self::Contacted => 'Dihubungi',
            self::Negotiating => 'Negosiasi',
            self::Selected => 'Terpilih',
            self::Rejected => 'Ditolak',
            self::Done => 'Selesai',
        };
    }
}
