<?php

namespace Tests\Feature\CustomFields;

use App\Enums\CustomFieldModule;
use App\Enums\CustomFieldType;
use App\Models\BudgetItem;
use App\Models\CustomFieldDefinition;
use App\Models\CustomFieldValue;
use App\Models\Guest;
use App\Models\User;
use App\Models\Wedding;
use App\Models\WeddingMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomFieldTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_and_editor_manage_definitions_while_viewer_cannot(): void
    {
        [$wedding, $owner] = $this->workspace();
        $editor = $this->member($wedding, 'editor');
        $viewer = $this->member($wedding, 'viewer');

        $payload = [
            'module' => 'guest',
            'field_type' => 'text',
            'label' => 'Nomor meja',
            'is_required' => false,
        ];

        $this->actingAs($owner)->post(route('weddings.custom-fields.store', $wedding), $payload)->assertRedirect();
        $this->actingAs($editor)->post(route('weddings.custom-fields.store', $wedding), [...$payload, 'label' => 'Kebangsaan'])->assertRedirect();
        $this->actingAs($viewer)->post(route('weddings.custom-fields.store', $wedding), [...$payload, 'label' => 'Tidak boleh'])->assertForbidden();

        $this->assertSame(2, $wedding->customFieldDefinitions()->count());
    }

    public function test_definition_requires_valid_module_type_and_unique_label(): void
    {
        [$wedding, $owner] = $this->workspace();

        $this->actingAs($owner)->post(route('weddings.custom-fields.store', $wedding), [
            'module' => 'vendor',
            'field_type' => 'text',
            'label' => 'Field vendor',
        ])->assertSessionHasErrors(['module']);

        $this->actingAs($owner)->post(route('weddings.custom-fields.store', $wedding), [
            'module' => 'guest',
            'field_type' => 'formula',
            'label' => 'Field asing',
        ])->assertSessionHasErrors(['field_type']);

        $this->actingAs($owner)->post(route('weddings.custom-fields.store', $wedding), [
            'module' => 'guest',
            'field_type' => 'text',
            'label' => 'Nomor meja',
        ])->assertRedirect();

        $this->actingAs($owner)->post(route('weddings.custom-fields.store', $wedding), [
            'module' => 'guest',
            'field_type' => 'text',
            'label' => 'Nomor meja',
        ])->assertSessionHasErrors(['label']);

        // Same label on a different module is allowed.
        $this->actingAs($owner)->post(route('weddings.custom-fields.store', $wedding), [
            'module' => 'task',
            'field_type' => 'text',
            'label' => 'Nomor meja',
        ])->assertRedirect();
    }

    public function test_select_definitions_require_options_and_others_reject_them(): void
    {
        [$wedding, $owner] = $this->workspace();

        $this->actingAs($owner)->post(route('weddings.custom-fields.store', $wedding), [
            'module' => 'guest',
            'field_type' => 'select',
            'label' => 'Kategori undangan',
            'options' => [],
        ])->assertSessionHasErrors(['options']);

        $this->actingAs($owner)->post(route('weddings.custom-fields.store', $wedding), [
            'module' => 'guest',
            'field_type' => 'select',
            'label' => 'Kategori undangan',
            'options' => ['Keluarga dekat', 'Keluarga jauh'],
        ])->assertRedirect();

        $definition = $wedding->customFieldDefinitions()->where('label', 'Kategori undangan')->firstOrFail();
        $this->assertSame(['Keluarga dekat', 'Keluarga jauh'], $definition->options);

        // Non-select type: options are dropped server-side.
        $this->actingAs($owner)->post(route('weddings.custom-fields.store', $wedding), [
            'module' => 'guest',
            'field_type' => 'text',
            'label' => 'Catatan',
            'options' => ['diabaikan'],
        ])->assertRedirect();

        $text = $wedding->customFieldDefinitions()->where('label', 'Catatan')->firstOrFail();
        $this->assertNull($text->options);
    }

    public function test_field_keys_are_slugged_and_deduplicated(): void
    {
        [$wedding, $owner] = $this->workspace();

        $this->actingAs($owner)->post(route('weddings.custom-fields.store', $wedding), [
            'module' => 'guest', 'field_type' => 'text', 'label' => 'Nomor Meja Utama',
        ])->assertRedirect();
        $this->actingAs($owner)->post(route('weddings.custom-fields.store', $wedding), [
            'module' => 'guest', 'field_type' => 'textarea', 'label' => 'nomor meja utama!',
        ])->assertRedirect();

        $keys = $wedding->customFieldDefinitions()->where('module', 'guest')->orderBy('id')->pluck('field_key');

        $this->assertSame('nomor-meja-utama', $keys[0]);
        $this->assertSame('nomor-meja-utama-2', $keys[1]);
    }

    public function test_values_are_validated_per_type_against_same_wedding_definitions(): void
    {
        [$wedding, $owner] = $this->workspace();
        $guest = $this->guest($wedding);

        $number = $this->definition($wedding, 'guest', 'number', 'Jumlah enteng');
        $currency = $this->definition($wedding, 'guest', 'currency', 'Ampiran');
        $date = $this->definition($wedding, 'guest', 'date', 'Tanggal kirim terima kasih');
        $select = $this->definition($wedding, 'guest', 'select', 'Kategori undangan', ['Keluarga dekat', 'Keluarga jauh']);
        $multi = $this->definition($wedding, 'guest', 'multi_select', 'Kesibukan', ['Kerja', 'Kuliah']);
        $checkbox = $this->definition($wedding, 'guest', 'checkbox', 'Sudah dikenal');
        $url = $this->definition($wedding, 'guest', 'url', 'Instagram');
        $text = $this->definition($wedding, 'guest', 'text', 'Nomor meja');

        $valid = [
            $number->field_key => 2,
            $currency->field_key => 50000,
            $date->field_key => '2027-06-01',
            $select->field_key => 'Keluarga dekat',
            $multi->field_key => ['Kerja', 'Kuliah'],
            $checkbox->field_key => true,
            $url->field_key => 'https://instagram.com/tamu',
            $text->field_key => 'Meja 12',
        ];

        $this->actingAs($owner)->patch(route('weddings.custom-field-values.update', $wedding), [
            'module' => 'guest',
            'subject_uuid' => $guest->uuid,
            'values' => $valid,
        ])->assertRedirect();

        $this->assertSame(8, $wedding->customFieldValues()->where('subject_id', $guest->id)->count());

        // Invalid payloads per type.
        $invalid = [
            $number->field_key => 2.5,
            $currency->field_key => -1000,
            $date->field_key => 'besok',
            $select->field_key => 'Tetangga',
            $multi->field_key => 'Kerja',
            $checkbox->field_key => 'mungkin',
            $url->field_key => 'bukan-url',
            $text->field_key => str_repeat('x', 201),
        ];

        $response = $this->actingAs($owner)->patch(route('weddings.custom-field-values.update', $wedding), [
            'module' => 'guest',
            'subject_uuid' => $guest->uuid,
            'values' => $invalid,
        ]);
        $response->assertSessionHasErrors([
            $number->field_key,
            $currency->field_key,
            $date->field_key,
            $select->field_key,
            $multi->field_key,
            $checkbox->field_key,
            $url->field_key,
            $text->field_key,
        ]);
    }

    public function test_required_active_definition_rejects_missing_value(): void
    {
        [$wedding, $owner] = $this->workspace();
        $guest = $this->guest($wedding);
        $required = $this->definition($wedding, 'guest', 'text', 'Nomor meja', null, true);

        $this->actingAs($owner)->patch(route('weddings.custom-field-values.update', $wedding), [
            'module' => 'guest',
            'subject_uuid' => $guest->uuid,
            'values' => [$required->field_key => ''],
        ])->assertSessionHasErrors([$required->field_key]);

        // Deactivated definitions stop enforcing required-ness (PRD FR-16).
        $required->update(['is_active' => false]);

        $this->actingAs($owner)->patch(route('weddings.custom-field-values.update', $wedding), [
            'module' => 'guest',
            'subject_uuid' => $guest->uuid,
            'values' => [$required->field_key => ''],
        ])->assertRedirect();
    }

    public function test_values_do_not_cross_weddings(): void
    {
        [$wedding, $owner] = $this->workspace();
        [$other] = $this->workspace();
        $foreignGuest = $this->guest($other);
        $definition = $this->definition($wedding, 'guest', 'text', 'Nomor meja');

        // Subject from another wedding is rejected.
        $this->actingAs($owner)->patch(route('weddings.custom-field-values.update', $wedding), [
            'module' => 'guest',
            'subject_uuid' => $foreignGuest->uuid,
            'values' => [$definition->field_key => 'Meja 1'],
        ])->assertNotFound();

        // Values written for wedding A are invisible to wedding B.
        $ownGuest = $this->guest($wedding);
        $this->actingAs($owner)->patch(route('weddings.custom-field-values.update', $wedding), [
            'module' => 'guest',
            'subject_uuid' => $ownGuest->uuid,
            'values' => [$definition->field_key => 'Meja 1'],
        ])->assertRedirect();

        $this->assertSame(1, $wedding->customFieldValues()->count());
        $this->assertSame(0, $other->customFieldValues()->count());
        $this->assertSame(0, $other->customFieldDefinitions()->count());
    }

    public function test_type_change_is_rejected_while_values_exist(): void
    {
        [$wedding, $owner] = $this->workspace();
        $guest = $this->guest($wedding);
        $definition = $this->definition($wedding, 'guest', 'text', 'Nomor meja');

        // No values yet: definition can be updated.
        $this->actingAs($owner)->patch(route('weddings.custom-fields.update', [$wedding, $definition]), [
            'label' => 'Nomor meja duduk',
            'is_active' => true,
        ])->assertRedirect();

        $this->actingAs($owner)->patch(route('weddings.custom-field-values.update', $wedding), [
            'module' => 'guest',
            'subject_uuid' => $guest->uuid,
            'values' => [$definition->field_key => 'Meja 1'],
        ])->assertRedirect();

        $this->actingAs($owner)->patch(route('weddings.custom-fields.update', [$wedding, $definition]), [
            'label' => 'Nomor meja duduk',
            'field_type' => 'number',
            'is_active' => true,
        ])->assertStatus(422);
    }

    public function test_definition_with_values_cannot_be_deleted_but_can_be_deactivated(): void
    {
        [$wedding, $owner] = $this->workspace();
        $guest = $this->guest($wedding);
        $definition = $this->definition($wedding, 'guest', 'text', 'Nomor meja');

        // Without values: delete works.
        $empty = $this->definition($wedding, 'guest', 'text', 'Catatan samping');
        $this->actingAs($owner)->delete(route('weddings.custom-fields.destroy', [$wedding, $empty]))->assertRedirect();
        $this->assertNull($wedding->customFieldDefinitions()->where('label', 'Catatan samping')->first());

        $this->actingAs($owner)->patch(route('weddings.custom-field-values.update', $wedding), [
            'module' => 'guest',
            'subject_uuid' => $guest->uuid,
            'values' => [$definition->field_key => 'Meja 1'],
        ])->assertRedirect();

        // With values: delete is restricted; deactivate keeps the value.
        $this->actingAs($owner)->delete(route('weddings.custom-fields.destroy', [$wedding, $definition]))->assertStatus(422);

        $this->actingAs($owner)->patch(route('weddings.custom-fields.update', [$wedding, $definition]), [
            'label' => 'Nomor meja',
            'is_active' => false,
        ])->assertRedirect();

        $definition->refresh();
        $this->assertFalse($definition->is_active);
        $this->assertSame(1, $definition->values()->count());
    }

    public function test_cross_wedding_definition_returns_not_found(): void
    {
        [$wedding, $owner] = $this->workspace();
        [$other] = $this->workspace();
        $foreign = $this->definition($other, 'guest', 'text', 'Nomor meja');

        $this->actingAs($owner)->patch(route('weddings.custom-fields.update', [$wedding, $foreign]), [
            'label' => 'Perubahan asing',
        ])->assertNotFound();
        $this->actingAs($owner)->delete(route('weddings.custom-fields.destroy', [$wedding, $foreign]))->assertNotFound();
    }

    public function test_file_reference_value_must_reference_same_wedding_attachment(): void
    {
        [$wedding, $owner] = $this->workspace();
        [$other] = $this->workspace();
        $guest = $this->guest($wedding);
        $definition = $this->definition($wedding, 'guest', 'file_reference', 'Kartu ucapan');

        $foreignAttachment = \App\Models\Attachment::factory()->create([
            'wedding_id' => $other->id,
            'module' => 'guest',
            'guest_id' => $this->guest($other)->id,
            'uploaded_by_member_id' => $other->memberships()->whereNull('left_at')->firstOrFail()->id,
        ]);

        $this->actingAs($owner)->patch(route('weddings.custom-field-values.update', $wedding), [
            'module' => 'guest',
            'subject_uuid' => $guest->uuid,
            'values' => [$definition->field_key => $foreignAttachment->uuid],
        ])->assertSessionHasErrors([$definition->field_key]);
    }

    /** @return array{Wedding, User} */
    private function workspace(): array
    {
        $wedding = Wedding::factory()->create(['wedding_date' => '2027-06-20']);
        $owner = User::factory()->create();
        WeddingMember::factory()->create(['wedding_id' => $wedding->id, 'user_id' => $owner->id, 'role' => 'owner', 'joined_at' => now()]);

        return [$wedding, $owner];
    }

    private function member(Wedding $wedding, string $role): User
    {
        $user = User::factory()->create();
        WeddingMember::factory()->create(['wedding_id' => $wedding->id, 'user_id' => $user->id, 'role' => $role, 'joined_at' => now()]);

        return $user;
    }

    private function guest(Wedding $wedding): Guest
    {
        return Guest::factory()->create(['wedding_id' => $wedding->id]);
    }

    private function definition(
        Wedding $wedding,
        string $module,
        string $type,
        string $label,
        ?array $options = null,
        bool $isRequired = false,
    ): CustomFieldDefinition {
        $definition = CustomFieldDefinition::create([
            'wedding_id' => $wedding->id,
            'module' => CustomFieldModule::from($module),
            'field_type' => CustomFieldType::from($type),
            'label' => $label,
            'field_key' => CustomFieldDefinition::buildFieldKey($label).'-'.uniqid(),
            'is_required' => $isRequired,
            'options' => $options,
        ]);

        return $definition;
    }
}
