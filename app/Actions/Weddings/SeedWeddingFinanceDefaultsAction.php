<?php

namespace App\Actions\Weddings;

use App\Models\Wedding;

class SeedWeddingFinanceDefaultsAction
{
    /** @var list<array{name: string, color: string, sort_order: int}> */
    private const CATEGORIES = [
        ['name' => 'Venue & Dekorasi', 'color' => 'burgundy', 'sort_order' => 10],
        ['name' => 'Katering & Kue', 'color' => 'clay', 'sort_order' => 20],
        ['name' => 'Dokumentasi & Hiburan', 'color' => 'rose', 'sort_order' => 30],
        ['name' => 'Pakaian & Perlengkapan', 'color' => 'sage', 'sort_order' => 40],
        ['name' => 'Administrasi & Lainnya', 'color' => 'slate', 'sort_order' => 50],
    ];

    /**
     * Curated scenarios whose active allocations sum exactly to their advertised totals.
     *
     * @var list<array{name: string, description: string, sort_order: int, total: int, allocations: list<array{category: string, amount: int}>}>
     */
    private const SCENARIOS = [
        [
            'name' => 'Hemat Rp10 Juta',
            'description' => 'Pernikahan sederhana dengan fokus pada kebutuhan inti.',
            'sort_order' => 10,
            'total' => 10_000_000,
            'allocations' => [
                ['category' => 'Venue & Dekorasi', 'amount' => 3_500_000],
                ['category' => 'Katering & Kue', 'amount' => 3_000_000],
                ['category' => 'Dokumentasi & Hiburan', 'amount' => 1_500_000],
                ['category' => 'Pakaian & Perlengkapan', 'amount' => 1_500_000],
                ['category' => 'Administrasi & Lainnya', 'amount' => 500_000],
            ],
        ],
        [
            'name' => 'Nyaman Rp20 Juta',
            'description' => 'Keseimbangan antara kenyamanan dan biaya.',
            'sort_order' => 20,
            'total' => 20_000_000,
            'allocations' => [
                ['category' => 'Venue & Dekorasi', 'amount' => 7_000_000],
                ['category' => 'Katering & Kue', 'amount' => 6_000_000],
                ['category' => 'Dokumentasi & Hiburan', 'amount' => 3_000_000],
                ['category' => 'Pakaian & Perlengkapan', 'amount' => 3_000_000],
                ['category' => 'Administrasi & Lainnya', 'amount' => 1_000_000],
            ],
        ],
        [
            'name' => 'Ideal Rp30 Juta',
            'description' => 'Pernikahan ideal untuk resepsi standar.',
            'sort_order' => 30,
            'total' => 30_000_000,
            'allocations' => [
                ['category' => 'Venue & Dekorasi', 'amount' => 10_000_000],
                ['category' => 'Katering & Kue', 'amount' => 9_000_000],
                ['category' => 'Dokumentasi & Hiburan', 'amount' => 5_000_000],
                ['category' => 'Pakaian & Perlengkapan', 'amount' => 4_500_000],
                ['category' => 'Administrasi & Lainnya', 'amount' => 1_500_000],
            ],
        ],
        [
            'name' => 'Elegan Rp50 Juta',
            'description' => 'Resepsi elegan dengan vendor pilihan.',
            'sort_order' => 40,
            'total' => 50_000_000,
            'allocations' => [
                ['category' => 'Venue & Dekorasi', 'amount' => 18_000_000],
                ['category' => 'Katering & Kue', 'amount' => 14_000_000],
                ['category' => 'Dokumentasi & Hiburan', 'amount' => 8_000_000],
                ['category' => 'Pakaian & Perlengkapan', 'amount' => 7_000_000],
                ['category' => 'Administrasi & Lainnya', 'amount' => 3_000_000],
            ],
        ],
        [
            'name' => 'Premium Rp75 Juta',
            'description' => 'Pernikahan premium dengan pengalaman terbaik untuk tamu.',
            'sort_order' => 50,
            'total' => 75_000_000,
            'allocations' => [
                ['category' => 'Venue & Dekorasi', 'amount' => 28_000_000],
                ['category' => 'Katering & Kue', 'amount' => 20_000_000],
                ['category' => 'Dokumentasi & Hiburan', 'amount' => 12_000_000],
                ['category' => 'Pakaian & Perlengkapan', 'amount' => 10_000_000],
                ['category' => 'Administrasi & Lainnya', 'amount' => 5_000_000],
            ],
        ],
        [
            'name' => 'Mewah Rp100 Juta',
            'description' => 'Pernikahan mewah dengan standar tertinggi.',
            'sort_order' => 60,
            'total' => 100_000_000,
            'allocations' => [
                ['category' => 'Venue & Dekorasi', 'amount' => 38_000_000],
                ['category' => 'Katering & Kue', 'amount' => 27_000_000],
                ['category' => 'Dokumentasi & Hiburan', 'amount' => 16_000_000],
                ['category' => 'Pakaian & Perlengkapan', 'amount' => 13_000_000],
                ['category' => 'Administrasi & Lainnya', 'amount' => 6_000_000],
            ],
        ],
    ];

    public function execute(Wedding $wedding): void
    {
        $categories = [];

        foreach (self::CATEGORIES as $category) {
            $categories[$category['name']] = $wedding->budgetCategories()->firstOrCreate(
                ['name' => $category['name']],
                $category,
            )->id;
        }

        foreach (self::SCENARIOS as $scenario) {
            $existing = $wedding->budgetScenarios()->where('name', $scenario['name'])->first();

            if ($existing !== null) {
                continue;
            }

            $created = $wedding->budgetScenarios()->create([
                'name' => $scenario['name'],
                'description' => $scenario['description'],
                'sort_order' => $scenario['sort_order'],
                'is_active' => true,
            ]);

            foreach ($scenario['allocations'] as $index => $allocation) {
                $created->scenarioItems()->create([
                    'wedding_id' => $wedding->id,
                    'budget_category_id' => $categories[$allocation['category']],
                    'name' => 'Alokasi '.$allocation['category'],
                    'amount' => $allocation['amount'],
                    'sort_order' => ($index + 1) * 10,
                    'is_active' => true,
                ]);
            }
        }
    }
}
