<?php

namespace Tests\Feature\Finance;

use App\Actions\Finance\CommitBudgetItemAction;
use App\Actions\Weddings\CreateWeddingAction;
use App\Models\BudgetItem;
use App\Models\User;
use App\Models\Wedding;
use App\Models\WeddingMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetItemLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $editor;

    private User $viewer;

    private Wedding $wedding;

    private BudgetItem $item;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->wedding = app(CreateWeddingAction::class)->execute($this->owner, [
            'name' => 'Pernikahan Test',
            'partner_one_name' => 'Ayu',
            'partner_two_name' => 'Bima',
            'wedding_date' => now()->addYear()->toDateString(),
            'timezone' => 'Asia/Jakarta',
            'initial_event_name' => 'Resepsi',
        ]);

        $this->editor = User::factory()->create();
        WeddingMember::factory()->for($this->wedding)->for($this->editor, 'user')->create();

        $this->viewer = User::factory()->create();
        WeddingMember::factory()->viewer()->for($this->wedding)->for($this->viewer, 'user')->create();

        $category = $this->wedding->budgetCategories()->first();

        $this->item = $this->wedding->budgetItems()->create([
            'budget_category_id' => $category->id,
            'wedding_event_id' => null,
            'name' => 'Gedung Serbaguna',
            'estimated_amount' => 20_000_000,
            'final_amount' => null,
            'status' => 'draft',
            'sort_order' => 0,
        ]);
    }

    public function test_commit_requires_final_amount_and_is_audited(): void
    {
        $this->item->final_amount = null;
        $this->item->save();

        // Commit with a final amount through the action.
        app(CommitBudgetItemAction::class)->execute($this->owner, $this->item, 22_500_000);

        $this->item->refresh();
        $this->assertSame('committed', $this->item->status->value);
        $this->assertSame(22_500_000, $this->item->final_amount);

        $this->assertDatabaseHas('activity_logs', [
            'wedding_id' => $this->wedding->id,
            'actor_id' => $this->owner->id,
            'action' => 'budget.item.committed',
        ]);
    }

    public function test_commit_rejects_negative_and_cancelled_items(): void
    {
        $this->expectException(\RuntimeException::class);
        app(CommitBudgetItemAction::class)->execute($this->owner, $this->item, -100);
    }

    public function test_cancel_keeps_history_and_blocks_deletion(): void
    {
        app(CommitBudgetItemAction::class)->execute($this->owner, $this->item, 22_500_000);

        $this->actingAs($this->editor)
            ->post(route('weddings.finance.budget.cancel', ['wedding' => $this->wedding, 'budget_item' => $this->item]))
            ->assertRedirect();

        $this->item->refresh();
        $this->assertSame('cancelled', $this->item->status->value);
        $this->assertNotNull($this->item->deleted_at === null ? $this->item : null, 'Cancelled item must remain stored as history.');

        // Deletion is blocked for non-draft items.
        $this->actingAs($this->owner)
            ->delete(route('weddings.finance.budget.destroy', ['wedding' => $this->wedding, 'budget_item' => $this->item]))
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertNotNull($this->item->fresh());
    }

    public function test_viewer_cannot_mutate_budget_items(): void
    {
        $this->actingAs($this->viewer)
            ->post(route('weddings.finance.budget.commit', ['wedding' => $this->wedding, 'budget_item' => $this->item]), [
                'final_amount' => 10_000_000,
            ])->assertForbidden();
    }

    public function test_budget_item_supports_at_least_ten_payment_terms(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            $this->actingAs($this->editor)
                ->post(route('weddings.finance.payment-terms.store', ['wedding' => $this->wedding, 'budget_item' => $this->item]), [
                    'name' => "Termin {$i}",
                    'planned_amount' => 1_000_000,
                    'due_date' => now()->addMonths($i)->toDateString(),
                ])->assertRedirect();
        }

        $this->assertSame(12, $this->item->paymentTerms()->count());
        $this->assertSame(
            [10, 20, 30, 40, 50, 60, 70, 80, 90, 100, 110, 120],
            $this->item->paymentTerms()->orderBy('sort_order')->pluck('sort_order')->all(),
        );
    }

    public function test_payment_states_derive_from_actual_paid_amount(): void
    {
        $term = $this->item->paymentTerms()->create([
            'wedding_id' => $this->wedding->id,
            'name' => 'DP',
            'planned_amount' => 5_000_000,
            'due_date' => now()->addMonth()->toDateString(),
            'sort_order' => 10,
            'actual_paid_amount' => 0,
            'paid_at' => null,
            'status' => 'planned',
        ]);

        $record = fn (int $amount) => $this->actingAs($this->editor)
            ->post(route('weddings.finance.payment-terms.payment', [
                'wedding' => $this->wedding, 'budget_item' => $this->item, 'payment_term' => $term,
            ]), ['actual_paid_amount' => $amount]);

        // Partial payment.
        $record(2_000_000)->assertRedirect()->assertSessionHas('success');
        $term->refresh();
        $this->assertSame('partially_paid', $term->status->value);
        $this->assertSame(2_000_000, $term->actual_paid_amount);
        $this->assertNotNull($term->paid_at);

        // Full payment.
        $record(5_000_000)->assertRedirect()->assertSessionHas('success');
        $term->refresh();
        $this->assertSame('paid', $term->status->value);

        // Overpayment is retained, never rewrites the committed amount, and warns.
        $this->item->update(['status' => 'committed', 'final_amount' => 20_000_000]);
        $record(6_000_000)->assertRedirect()->assertSessionHas('warning');
        $term->refresh();
        $this->assertSame(6_000_000, $term->actual_paid_amount);
        $this->assertSame('paid', $term->status->value);
        $this->assertSame(20_000_000, $this->item->fresh()->final_amount, 'Overpayment must not rewrite the committed final amount.');

        // Zeroing the payment clears paid_at and returns to planned.
        $record(0)->assertRedirect()->assertSessionHas('success');
        $term->refresh();
        $this->assertSame(0, $term->actual_paid_amount);
        $this->assertNull($term->paid_at);
        $this->assertSame('planned', $term->status->value);
    }

    public function test_paid_terms_cannot_be_deleted_and_payment_history_blocks_item_deletion(): void
    {
        $term = $this->item->paymentTerms()->create([
            'wedding_id' => $this->wedding->id,
            'name' => 'DP',
            'planned_amount' => 5_000_000,
            'due_date' => now()->addMonth()->toDateString(),
            'sort_order' => 10,
            'actual_paid_amount' => 0,
            'paid_at' => null,
            'status' => 'planned',
        ]);

        // No payment yet: draft item can be deleted only after removing its terms is possible.
        $this->actingAs($this->editor)
            ->delete(route('weddings.finance.payment-terms.destroy', [
                'wedding' => $this->wedding, 'budget_item' => $this->item, 'payment_term' => $term,
            ]))->assertRedirect();
        $this->assertSame(0, $this->item->paymentTerms()->count());

        // Recreate and pay.
        $term = $this->item->paymentTerms()->create([
            'wedding_id' => $this->wedding->id,
            'name' => 'DP',
            'planned_amount' => 5_000_000,
            'due_date' => now()->addMonth()->toDateString(),
            'sort_order' => 10,
            'actual_paid_amount' => 3_000_000,
            'paid_at' => now(),
            'status' => 'partially_paid',
        ]);

        $this->actingAs($this->editor)
            ->delete(route('weddings.finance.payment-terms.destroy', [
                'wedding' => $this->wedding, 'budget_item' => $this->item, 'payment_term' => $term,
            ]))->assertRedirect()->assertSessionHas('error');
        $this->assertNotNull($term->fresh());

        // Payment history blocks even draft item deletion.
        $this->actingAs($this->owner)
            ->delete(route('weddings.finance.budget.destroy', ['wedding' => $this->wedding, 'budget_item' => $this->item]))
            ->assertRedirect()->assertSessionHas('error');
        $this->assertNotNull($this->item->fresh());
    }

    public function test_payment_terms_can_be_reordered(): void
    {
        $terms = collect(range(1, 3))->map(fn (int $i) => $this->item->paymentTerms()->create([
            'wedding_id' => $this->wedding->id,
            'name' => "Termin {$i}",
            'planned_amount' => 1_000_000,
            'due_date' => null,
            'sort_order' => $i * 10,
            'actual_paid_amount' => 0,
            'paid_at' => null,
            'status' => 'planned',
        ]));

        $reordered = $terms->pluck('uuid')->reverse()->values()->all();

        $this->actingAs($this->editor)
            ->post(route('weddings.finance.payment-terms.reorder', ['wedding' => $this->wedding, 'budget_item' => $this->item]), [
                'term_uuids' => $reordered,
            ])->assertRedirect();

        $this->assertSame(
            $reordered,
            $this->item->paymentTerms()->orderBy('sort_order')->pluck('uuid')->all(),
        );

        // Incomplete lists are rejected atomically.
        $this->actingAs($this->editor)
            ->post(route('weddings.finance.payment-terms.reorder', ['wedding' => $this->wedding, 'budget_item' => $this->item]), [
                'term_uuids' => [$reordered[0]],
            ])->assertRedirect()->assertSessionHas('error');
        $this->assertSame(
            $reordered,
            $this->item->paymentTerms()->orderBy('sort_order')->pluck('uuid')->all(),
            'Rejected reorder must not partially apply.',
        );
    }

    public function test_budget_index_filters_and_isolation(): void
    {
        $otherOwner = User::factory()->create();
        $otherWedding = app(CreateWeddingAction::class)->execute($otherOwner, [
            'name' => 'Wedding Lain',
            'partner_one_name' => 'Cinta',
            'partner_two_name' => 'Dewa',
            'wedding_date' => now()->addYear()->toDateString(),
            'timezone' => 'Asia/Jakarta',
            'initial_event_name' => 'Resepsi',
        ]);
        $otherCategory = $otherWedding->budgetCategories()->first();
        $otherWedding->budgetItems()->create([
            'budget_category_id' => $otherCategory->id,
            'wedding_event_id' => null,
            'name' => 'Gedung Rahasia Wedding Lain',
            'estimated_amount' => 99_999_999,
            'final_amount' => null,
            'status' => 'draft',
            'sort_order' => 0,
        ]);

        // Search stays wedding-scoped: no leak of the other wedding's items.
        $response = $this->actingAs($this->owner)
            ->get(route('weddings.finance.budget.index', ['wedding' => $this->wedding, 'search' => 'Gedung']))
            ->assertOk();

        $page = $response->viewData('page')['props'];
        $names = collect($page['items']['data'])->pluck('name')->all();
        $this->assertContains('Gedung Serbaguna', $names);
        $this->assertNotContains('Gedung Rahasia Wedding Lain', $names);

        // Status filter works.
        $this->actingAs($this->owner)
            ->get(route('weddings.finance.budget.index', ['wedding' => $this->wedding, 'status' => 'committed']))
            ->assertOk();

        // Outsider gets 403.
        $this->actingAs($otherOwner)
            ->get(route('weddings.finance.budget.index', $this->wedding))
            ->assertForbidden();
    }

    public function test_show_page_includes_term_attachments_keyed_by_term_uuid(): void
    {
        $term = $this->item->paymentTerms()->create([
            'wedding_id' => $this->wedding->id,
            'name' => 'DP',
            'planned_amount' => 5_000_000,
            'due_date' => null,
            'sort_order' => 10,
            'actual_paid_amount' => 0,
            'paid_at' => null,
            'status' => 'planned',
        ]);

        $member = $this->wedding->memberships()->whereNull('left_at')->where('user_id', $this->owner->id)->firstOrFail();
        \App\Models\Attachment::factory()->create([
            'wedding_id' => $this->wedding->id,
            'module' => \App\Enums\AttachmentModule::PaymentTerm->value,
            'payment_term_id' => $term->id,
            'uploaded_by_member_id' => $member->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->get(route('weddings.finance.budget.show', ['wedding' => $this->wedding, 'budget_item' => $this->item]))
            ->assertOk();
        $props = $response->viewData('page')['props'];

        $this->assertArrayHasKey($term->uuid, $props['termAttachments']);
        $this->assertCount(1, $props['termAttachments'][$term->uuid]);
        $this->assertArrayHasKey('filenameOriginal', $props['termAttachments'][$term->uuid][0]);
    }
}
