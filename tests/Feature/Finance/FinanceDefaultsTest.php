<?php

namespace Tests\Feature\Finance;

use App\Actions\Weddings\CreateWeddingAction;
use App\Actions\Weddings\SeedWeddingFinanceDefaultsAction;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceDefaultsTest extends TestCase
{
    use RefreshDatabase;

    private const EXPECTED_TOTALS = [
        'Hemat Rp10 Juta' => 10_000_000,
        'Nyaman Rp20 Juta' => 20_000_000,
        'Ideal Rp30 Juta' => 30_000_000,
        'Elegan Rp50 Juta' => 50_000_000,
        'Premium Rp75 Juta' => 75_000_000,
        'Mewah Rp100 Juta' => 100_000_000,
    ];

    private CreateWeddingAction $creator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->creator = app(CreateWeddingAction::class);
    }

    public function test_new_wedding_receives_curated_categories_and_exact_total_scenarios(): void
    {
        $user = User::factory()->create();
        $template = Template::query()->where('is_active', true)->first();

        $wedding = $this->creator->execute($user, [
            'name' => 'Pernikahan Test',
            'partner_one_name' => 'Ayu',
            'partner_two_name' => 'Bima',
            'wedding_date' => now()->addYear()->toDateString(),
            'timezone' => 'Asia/Jakarta',
            'initial_event_name' => 'Resepsi',
            'template_id' => $template?->id,
        ]);

        $this->assertSame(5, $wedding->budgetCategories()->count());
        $this->assertSame(6, $wedding->budgetScenarios()->count());

        foreach (self::EXPECTED_TOTALS as $scenarioName => $total) {
            $scenario = $wedding->budgetScenarios()->where('name', $scenarioName)->firstOrFail();

            $this->assertTrue($scenario->is_active);
            $this->assertSame(
                $total,
                (int) $scenario->scenarioItems()->where('is_active', true)->sum('amount'),
                "Scenario {$scenarioName} must sum exactly to {$total}.",
            );
        }
    }

    public function test_seeding_is_idempotent_for_existing_weddings(): void
    {
        $user = User::factory()->create();

        $wedding = $this->creator->execute($user, [
            'name' => 'Pernikahan Test',
            'partner_one_name' => 'Ayu',
            'partner_two_name' => 'Bima',
            'wedding_date' => now()->addYear()->toDateString(),
            'timezone' => 'Asia/Jakarta',
            'initial_event_name' => 'Resepsi',
        ]);

        app(SeedWeddingFinanceDefaultsAction::class)->execute($wedding);

        $this->assertSame(5, $wedding->budgetCategories()->count());
        $this->assertSame(6, $wedding->budgetScenarios()->count());
        $this->assertSame(30, $wedding->scenarioItems()->count());
    }

    public function test_two_weddings_receive_independent_finance_defaults(): void
    {
        $user = User::factory()->create();
        $data = [
            'name' => 'Pernikahan Test',
            'partner_one_name' => 'Ayu',
            'partner_two_name' => 'Bima',
            'wedding_date' => now()->addYear()->toDateString(),
            'timezone' => 'Asia/Jakarta',
            'initial_event_name' => 'Resepsi',
        ];

        $first = $this->creator->execute($user, $data);
        $second = $this->creator->execute($user, [...$data, 'name' => 'Pernikahan Kedua']);

        $this->assertSame($first->budgetCategories()->count(), $second->budgetCategories()->count());
        $this->assertNotSame($first->budgetCategories()->first()->id, $second->budgetCategories()->first()->id);
        $this->assertSame(
            $first->budgetScenarios()->first()->id !== $second->budgetScenarios()->first()->id,
            true,
        );
        $this->assertSame(30, $first->scenarioItems()->count());
        $this->assertSame(30, $second->scenarioItems()->count());
    }
}
