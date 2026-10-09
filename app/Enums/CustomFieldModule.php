<?php

namespace App\Enums;

enum CustomFieldModule: string
{
    case Guest = 'guest';
    case Task = 'task';
    case ChecklistItem = 'checklist_item';
    case BudgetItem = 'budget_item';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }

    public function label(): string
    {
        return match ($this) {
            self::Guest => 'Tamu',
            self::Task => 'Tugas',
            self::ChecklistItem => 'Checklist',
            self::BudgetItem => 'Anggaran',
        };
    }

    /**
     * Subject table backing this module, used for wedding-scoped existence validation.
     */
    public function subjectTable(): string
    {
        return match ($this) {
            self::Guest => 'guests',
            self::Task => 'tasks',
            self::ChecklistItem => 'checklist_items',
            self::BudgetItem => 'budget_items',
        };
    }
}
