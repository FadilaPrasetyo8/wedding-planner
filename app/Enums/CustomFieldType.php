<?php

namespace App\Enums;

enum CustomFieldType: string
{
    case Text = 'text';
    case Textarea = 'textarea';
    case Number = 'number';
    case Currency = 'currency';
    case Date = 'date';
    case Select = 'select';
    case MultiSelect = 'multi_select';
    case Checkbox = 'checkbox';
    case Url = 'url';
    case FileReference = 'file_reference';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }

    public function label(): string
    {
        return match ($this) {
            self::Text => 'Teks pendek',
            self::Textarea => 'Teks panjang',
            self::Number => 'Angka',
            self::Currency => 'Mata uang',
            self::Date => 'Tanggal',
            self::Select => 'Pilihan',
            self::MultiSelect => 'Pilihan ganda',
            self::Checkbox => 'Centang',
            self::Url => 'URL',
            self::FileReference => 'Referensi lampiran',
        };
    }
}
