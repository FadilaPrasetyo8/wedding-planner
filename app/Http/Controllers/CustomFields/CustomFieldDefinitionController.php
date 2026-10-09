<?php

namespace App\Http\Controllers\CustomFields;

use App\Enums\CustomFieldModule;
use App\Http\Controllers\Controller;
use App\Http\Requests\CustomFields\StoreCustomFieldDefinitionRequest;
use App\Http\Requests\CustomFields\UpdateCustomFieldDefinitionRequest;
use App\Models\CustomFieldDefinition;
use App\Models\Wedding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CustomFieldDefinitionController extends Controller
{
    public function index(Request $request, Wedding $wedding): Response
    {
        $module = $this->resolveModule($request);

        $definitions = $wedding->customFieldDefinitions()
            ->where('module', $module->value)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return Inertia::render('weddings/custom-fields/index', [
            'module' => $module->value,
            'moduleLabel' => $module->label(),
            'definitions' => $definitions->map(fn (CustomFieldDefinition $definition): array => [
                'uuid' => $definition->uuid,
                'label' => $definition->label,
                'fieldKey' => $definition->field_key,
                'fieldType' => $definition->field_type->value,
                'fieldTypeLabel' => $definition->field_type->label(),
                'isRequired' => $definition->is_required,
                'options' => $definition->options ?? [],
                'sortOrder' => $definition->sort_order,
                'isActive' => $definition->is_active,
                'hasValues' => $definition->values()->exists(),
            ])->all(),
            'modules' => collect(CustomFieldModule::cases())->map(fn (CustomFieldModule $case): array => [
                'value' => $case->value,
                'label' => $case->label(),
            ])->all(),
            'canEdit' => $request->user()->can('editOperationalData', $wedding),
        ]);
    }

    public function store(StoreCustomFieldDefinitionRequest $request, Wedding $wedding): RedirectResponse
    {
        $data = $request->definitionData();

        $definition = DB::transaction(function () use ($wedding, $data): CustomFieldDefinition {
            return $wedding->customFieldDefinitions()->create([
                'module' => $data['module'],
                'field_type' => $data['field_type'],
                'label' => $data['label'],
                'field_key' => $this->uniqueFieldKey($wedding, $data['module'], $data['label']),
                'is_required' => $data['is_required'],
                'options' => $data['options'],
                'sort_order' => $data['sort_order'],
                'is_active' => true,
            ]);
        });

        return back()->with('success', 'Field kustom berhasil ditambahkan.');
    }

    public function update(UpdateCustomFieldDefinitionRequest $request, Wedding $wedding, CustomFieldDefinition $custom_field_definition): RedirectResponse
    {
        abort_unless($custom_field_definition->wedding_id === $wedding->id, 404);

        $data = $request->definitionData();

        // D-027: type changes are rejected while values exist; module is immutable.
        abort_if(
            $request->input('field_type') !== null
            && $request->input('field_type') !== $custom_field_definition->field_type->value
            && $custom_field_definition->values()->exists(),
            422,
            'Tipe field tidak dapat diubah selama masih memiliki nilai.'
        );

        $custom_field_definition->update([
            'label' => $data['label'],
            'is_required' => $data['is_required'],
            'options' => $data['options'],
            'sort_order' => $data['sort_order'],
            'is_active' => $data['is_active'],
        ]);

        return back()->with('success', 'Field kustom berhasil diperbarui.');
    }

    public function destroy(Request $request, Wedding $wedding, CustomFieldDefinition $custom_field_definition): RedirectResponse
    {
        abort_unless($custom_field_definition->wedding_id === $wedding->id, 404);
        abort_unless($request->user()->can('delete', $custom_field_definition), 403);

        // D-027: definitions with values are restricted; deactivate instead.
        abort_if($custom_field_definition->values()->exists(), 422, 'Field masih memiliki nilai. Nonaktifkan sebagai gantinya.');

        $custom_field_definition->delete();

        return back()->with('success', 'Field kustom berhasil dihapus.');
    }

    private function resolveModule(Request $request): CustomFieldModule
    {
        $raw = $request->string('module')->toString();

        try {
            return CustomFieldModule::from($raw);
        } catch (\ValueError) {
            return CustomFieldModule::Guest;
        }
    }

    /**
     * Slug the label and de-duplicate within the wedding module (D-027).
     */
    private function uniqueFieldKey(Wedding $wedding, CustomFieldModule $module, string $label): string
    {
        $base = CustomFieldDefinition::buildFieldKey($label);
        $key = $base;
        $suffix = 2;

        while ($wedding->customFieldDefinitions()
            ->where('module', $module->value)
            ->where('field_key', $key)
            ->exists()) {
            $key = $base.'-'.$suffix;
            $suffix++;
        }

        return $key;
    }
}
