<?php

namespace App\Actions\Weddings;

use App\Enums\ChecklistType;
use App\Models\Wedding;

class SeedWeddingLogisticsDefaultsAction
{
    private const VENDOR_CATEGORIES = [
        ['name' => 'Venue', 'sort_order' => 10],
        ['name' => 'Katering', 'sort_order' => 20],
        ['name' => 'Dokumentasi', 'sort_order' => 30],
        ['name' => 'Dekorasi', 'sort_order' => 40],
        ['name' => 'Musik & Hiburan', 'sort_order' => 50],
        ['name' => 'Pakaian & Rias', 'sort_order' => 60],
        ['name' => 'Cetak & Souvenir', 'sort_order' => 70],
        ['name' => 'Lainnya', 'sort_order' => 80],
    ];

    /** @var list<array{name: string, color: string, is_completed: bool, sort_order: int}> */
    private const SESERAHAN_STATUSES = [
        ['name' => 'Belum Dibeli', 'color' => 'slate', 'is_completed' => false, 'sort_order' => 10],
        ['name' => 'Sudah Dibeli', 'color' => 'info', 'is_completed' => false, 'sort_order' => 20],
        ['name' => 'Dikirim', 'color' => 'clay', 'is_completed' => false, 'sort_order' => 30],
        ['name' => 'Selesai', 'color' => 'sage', 'is_completed' => true, 'sort_order' => 40],
    ];

    /** @var list<array{name: string, color: string, is_completed: bool, sort_order: int}> */
    private const ADMINISTRATION_STATUSES = [
        ['name' => 'Belum Diproses', 'color' => 'slate', 'is_completed' => false, 'sort_order' => 10],
        ['name' => 'Diproses', 'color' => 'info', 'is_completed' => false, 'sort_order' => 20],
        ['name' => 'Selesai', 'color' => 'sage', 'is_completed' => true, 'sort_order' => 30],
    ];

    /** @var list<array{name: string, sort_order: int}> */
    private const SESERAHAN_CATEGORIES = [
        ['name' => 'Perhiasan', 'sort_order' => 10],
        ['name' => 'Pakaian', 'sort_order' => 20],
        ['name' => 'Perlengkapan Ibadah', 'sort_order' => 30],
        ['name' => 'Makanan Ringan', 'sort_order' => 40],
        ['name' => 'Perlengkapan Rumah Tangga', 'sort_order' => 50],
        ['name' => 'Lainnya', 'sort_order' => 60],
    ];

    /** @var list<array{name: string, sort_order: int}> */
    private const GUEST_SIDES = [
        ['name' => 'Mempelai 1', 'sort_order' => 10],
        ['name' => 'Mempelai 2', 'sort_order' => 20],
        ['name' => 'Bersama', 'sort_order' => 30],
    ];

    /** @var list<array{name: string, sort_order: int}> */
    private const GUEST_GROUPS = [
        ['name' => 'Teman', 'sort_order' => 10],
        ['name' => 'Kolega', 'sort_order' => 20],
        ['name' => 'Keluarga', 'sort_order' => 30],
        ['name' => 'Tetangga', 'sort_order' => 40],
        ['name' => 'Teman Orang Tua', 'sort_order' => 50],
        ['name' => 'VIP', 'sort_order' => 60],
    ];

    public function execute(Wedding $wedding): void
    {
        foreach (self::VENDOR_CATEGORIES as $category) {
            $wedding->vendorCategories()->firstOrCreate(
                ['name' => $category['name']],
                $category,
            );
        }

        foreach ([
            ['type' => ChecklistType::Seserahan->value, 'statuses' => self::SESERAHAN_STATUSES],
            ['type' => ChecklistType::Administration->value, 'statuses' => self::ADMINISTRATION_STATUSES],
        ] as $typeStatuses) {
            foreach ($typeStatuses['statuses'] as $status) {
                $wedding->checklistStatuses()->firstOrCreate(
                    ['type' => $typeStatuses['type'], 'name' => $status['name']],
                    ['type' => $typeStatuses['type'], ...$status],
                );
            }
        }

        foreach (self::SESERAHAN_CATEGORIES as $category) {
            $wedding->checklistCategories()->firstOrCreate(
                ['type' => ChecklistType::Seserahan->value, 'name' => $category['name']],
                ['type' => ChecklistType::Seserahan->value, ...$category],
            );
        }

        foreach (self::GUEST_SIDES as $side) {
            $wedding->guestSides()->firstOrCreate(
                ['name' => $side['name']],
                $side,
            );
        }

        foreach (self::GUEST_GROUPS as $group) {
            $wedding->guestGroups()->firstOrCreate(
                ['name' => $group['name']],
                $group,
            );
        }
    }
}
