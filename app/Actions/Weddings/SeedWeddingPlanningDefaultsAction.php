<?php

namespace App\Actions\Weddings;

use App\Enums\TaskBehavior;
use App\Models\Wedding;

class SeedWeddingPlanningDefaultsAction
{
    /** @var list<array{name: string, color: string, sort_order: int}> */
    private const CATEGORIES = [
        ['name' => 'Persiapan Utama', 'color' => 'burgundy', 'sort_order' => 10],
        ['name' => 'Dokumen & Administrasi', 'color' => 'clay', 'sort_order' => 20],
        ['name' => 'Vendor & Lokasi', 'color' => 'rose', 'sort_order' => 30],
        ['name' => 'Koordinasi Keluarga', 'color' => 'sage', 'sort_order' => 40],
    ];

    /** @var list<array{name: string, color: string, behavior: TaskBehavior, sort_order: int}> */
    private const STATUSES = [
        ['name' => 'Belum Mulai', 'color' => 'slate', 'behavior' => TaskBehavior::Todo, 'sort_order' => 10],
        ['name' => 'Proses', 'color' => 'clay', 'behavior' => TaskBehavior::InProgress, 'sort_order' => 20],
        ['name' => 'Selesai', 'color' => 'sage', 'behavior' => TaskBehavior::Completed, 'sort_order' => 30],
        ['name' => 'Dibatalkan', 'color' => 'rose', 'behavior' => TaskBehavior::Cancelled, 'sort_order' => 40],
    ];

    public function execute(Wedding $wedding): void
    {
        foreach (self::CATEGORIES as $category) {
            $wedding->taskCategories()->firstOrCreate(
                ['name' => $category['name']],
                $category,
            );
        }

        foreach (self::STATUSES as $status) {
            $wedding->taskStatuses()->firstOrCreate(
                ['behavior' => $status['behavior']],
                $status,
            );
        }
    }
}
