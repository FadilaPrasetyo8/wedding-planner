<?php

namespace App\Enums;

enum AttachmentModule: string
{
    case BudgetItem = 'budget_item';
    case PaymentTerm = 'payment_term';
    case ChecklistItem = 'checklist_item';
    case Vendor = 'vendor';
    case Guest = 'guest';
    case Task = 'task';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }

    public function label(): string
    {
        return match ($this) {
            self::BudgetItem => 'Anggaran',
            self::PaymentTerm => 'Termin pembayaran',
            self::ChecklistItem => 'Checklist',
            self::Vendor => 'Vendor',
            self::Guest => 'Tamu',
            self::Task => 'Tugas',
        };
    }

    /**
     * Subject table backing this module, used for wedding-scoped existence validation.
     */
    public function subjectTable(): string
    {
        return match ($this) {
            self::BudgetItem => 'budget_items',
            self::PaymentTerm => 'payment_terms',
            self::ChecklistItem => 'checklist_items',
            self::Vendor => 'vendors',
            self::Guest => 'guests',
            self::Task => 'tasks',
        };
    }

    /**
     * Column on attachments holding the subject id for this module.
     */
    public function subjectColumn(): string
    {
        return match ($this) {
            self::BudgetItem => 'budget_item_id',
            self::PaymentTerm => 'payment_term_id',
            self::ChecklistItem => 'checklist_item_id',
            self::Vendor => 'vendor_id',
            self::Guest => 'guest_id',
            self::Task => 'task_id',
        };
    }
}
