<?php

namespace Tests\Feature\Logistics;

use App\Actions\Weddings\CreateWeddingAction;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Wedding;
use App\Models\WeddingMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $editor;

    private User $viewer;

    private Wedding $wedding;

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
    }

    private function vendor(array $overrides = []): Vendor
    {
        $category = $this->wedding->vendorCategories()->orderBy('sort_order')->first();

        return $this->wedding->vendors()->create(array_merge([
            'vendor_category_id' => $category->id,
            'name' => 'Dekorasi Melati',
            'status' => 'candidate',
            'internal_rating' => null,
            'address' => null,
            'website' => null,
            'social_media' => null,
            'notes' => null,
        ], $overrides));
    }

    public function test_index_requires_membership_and_lists_contacts(): void
    {
        $vendor = $this->vendor();
        $vendor->contacts()->create([
            'vendor_id' => $vendor->id,
            'wedding_id' => $this->wedding->id,
            'name' => 'Bu Melati',
            'phone' => '081234567890',
            'email' => null,
            'role' => null,
            'is_primary' => true,
        ]);

        $response = $this->actingAs($this->viewer)
            ->get(route('weddings.vendors.index', $this->wedding))
            ->assertOk();

        $page = $response->viewData('page')['props'];
        $names = collect($page['vendors']['data'])->pluck('name')->all();
        $this->assertContains('Dekorasi Melati', $names);
        $this->assertSame('Bu Melati', $page['vendors']['data'][0]['contacts'][0]['name']);
        $this->assertFalse($page['canEdit']);

        $outsider = User::factory()->create();
        $this->actingAs($outsider)
            ->get(route('weddings.vendors.index', $this->wedding))
            ->assertForbidden();
    }

    public function test_editor_can_create_vendor_with_uuid_category(): void
    {
        $category = $this->wedding->vendorCategories()->where('name', 'Katering')->first();

        $this->actingAs($this->editor)
            ->post(route('weddings.vendors.store', $this->wedding), [
                'name' => 'Katering Rasa',
                'vendor_category_uuid' => $category->uuid,
                'status' => 'contacted',
                'internal_rating' => 4,
                'website' => 'https://katering.example',
            ])->assertRedirect()->assertSessionHas('success');

        $vendor = $this->wedding->vendors()->where('name', 'Katering Rasa')->first();
        $this->assertNotNull($vendor);
        $this->assertSame($category->id, $vendor->vendor_category_id);
        $this->assertSame('contacted', $vendor->status->value);
        $this->assertSame(4, $vendor->internal_rating);
    }

    public function test_category_uuid_from_another_wedding_is_rejected(): void
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
        $otherCategory = $otherWedding->vendorCategories()->first();

        $this->actingAs($this->editor)
            ->post(route('weddings.vendors.store', $this->wedding), [
                'name' => 'Vendor curang',
                'vendor_category_uuid' => $otherCategory->uuid,
                'status' => 'candidate',
            ])->assertRedirect()->assertSessionHasErrors('vendor_category_uuid');
    }

    public function test_only_one_primary_contact_per_vendor(): void
    {
        $vendor = $this->vendor();
        $first = $vendor->contacts()->create([
            'vendor_id' => $vendor->id,
            'wedding_id' => $this->wedding->id,
            'name' => 'Bu Melati',
            'phone' => '081234567890',
            'email' => null,
            'role' => null,
            'is_primary' => true,
        ]);

        // Adding a new primary contact demotes the old one.
        $this->actingAs($this->editor)
            ->post(route('weddings.vendor-contacts.store', ['wedding' => $this->wedding, 'vendor' => $vendor]), [
                'name' => 'Pak Sugi',
                'phone' => '089876543210',
                'is_primary' => '1',
            ])->assertRedirect()->assertSessionHas('success');

        $this->assertSame(
            [false, true],
            $vendor->contacts()->orderBy('id')->pluck('is_primary')->all(),
        );

        // Promoting the first back demotes the second.
        $this->actingAs($this->editor)
            ->patch(route('weddings.vendor-contacts.update', [
                'wedding' => $this->wedding, 'vendor' => $vendor, 'contact' => $first,
            ]), [
                'name' => 'Bu Melati',
                'phone' => '081234567890',
                'is_primary' => '1',
            ])->assertRedirect()->assertSessionHas('success');

        $this->assertSame(
            [true, false],
            $vendor->contacts()->orderBy('id')->pluck('is_primary')->all(),
        );
    }

    public function test_contact_scoping_enforces_vendor_and_wedding_match(): void
    {
        $vendor = $this->vendor();
        $contact = $vendor->contacts()->create([
            'vendor_id' => $vendor->id,
            'wedding_id' => $this->wedding->id,
            'name' => 'Bu Melati',
            'phone' => null,
            'email' => null,
            'role' => null,
            'is_primary' => false,
        ]);

        // A different vendor in the same wedding cannot touch this contact.
        $otherVendor = $this->vendor(['name' => 'Fotografi Ceria']);
        $this->actingAs($this->editor)
            ->delete(route('weddings.vendor-contacts.destroy', [
                'wedding' => $this->wedding, 'vendor' => $otherVendor, 'contact' => $contact,
            ]))->assertNotFound();
        $this->assertNotNull($contact->fresh());

        // Editor can delete through the correct vendor.
        $this->actingAs($this->editor)
            ->delete(route('weddings.vendor-contacts.destroy', [
                'wedding' => $this->wedding, 'vendor' => $vendor, 'contact' => $contact,
            ]))->assertRedirect()->assertSessionHas('success');
        $this->assertNull($contact->fresh());

        // Viewer cannot create contacts.
        $this->actingAs($this->viewer)
            ->post(route('weddings.vendor-contacts.store', ['wedding' => $this->wedding, 'vendor' => $vendor]), [
                'name' => 'Dilarang',
            ])->assertForbidden();
    }

    public function test_category_in_use_cannot_be_deleted(): void
    {
        $category = $this->wedding->vendorCategories()->where('name', 'Venue')->first();
        $this->vendor(['name' => 'Gedung Anggrek'], ['vendor_category_id' => $category->id]);

        $this->actingAs($this->editor)
            ->delete(route('weddings.vendor-categories.destroy', [
                'wedding' => $this->wedding, 'vendorCategory' => $category,
            ]))->assertRedirect()->assertSessionHasErrors('reference');
        $this->assertNotNull($category->fresh());

        $unused = $this->wedding->vendorCategories()->create([
            'name' => 'Kategori Sisa',
            'sort_order' => 90,
        ]);
        $this->actingAs($this->editor)
            ->delete(route('weddings.vendor-categories.destroy', [
                'wedding' => $this->wedding, 'vendorCategory' => $unused,
            ]))->assertRedirect()->assertSessionHas('success');
        $this->assertNull($unused->fresh());
    }

    public function test_vendor_is_isolated_per_wedding(): void
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
        $otherCategory = $otherWedding->vendorCategories()->first();
        $otherVendor = $otherWedding->vendors()->create([
            'vendor_category_id' => $otherCategory->id,
            'name' => 'Vendor Wedding Lain',
            'status' => 'candidate',
            'internal_rating' => null,
            'address' => null,
            'website' => null,
            'social_media' => null,
            'notes' => null,
        ]);

        $this->actingAs($this->editor)
            ->patch(route('weddings.vendors.update', ['wedding' => $this->wedding, 'vendor' => $otherVendor]), [
                'name' => 'Peretas',
                'vendor_category_uuid' => $this->wedding->vendorCategories()->first()->uuid,
                'status' => 'candidate',
            ])->assertNotFound();

        $this->actingAs($this->editor)
            ->delete(route('weddings.vendors.destroy', ['wedding' => $this->wedding, 'vendor' => $otherVendor]))
            ->assertNotFound();
        $this->assertNotNull($otherVendor->fresh());
    }

    public function test_index_includes_vendor_attachments_keyed_by_vendor_uuid(): void
    {
        $vendor = $this->vendor(['name' => 'Vendor berlampiran']);
        $member = $this->wedding->memberships()->whereNull('left_at')->where('user_id', $this->owner->id)->firstOrFail();
        \App\Models\Attachment::factory()->create([
            'wedding_id' => $this->wedding->id,
            'module' => \App\Enums\AttachmentModule::Vendor->value,
            'vendor_id' => $vendor->id,
            'uploaded_by_member_id' => $member->id,
        ]);

        $response = $this->actingAs($this->viewer)
            ->get(route('weddings.vendors.index', $this->wedding))
            ->assertOk();
        $props = $response->viewData('page')['props'];

        $this->assertArrayHasKey($vendor->uuid, $props['vendorAttachments']);
        $this->assertCount(1, $props['vendorAttachments'][$vendor->uuid]);
        $this->assertArrayHasKey('filenameOriginal', $props['vendorAttachments'][$vendor->uuid][0]);
    }
}
