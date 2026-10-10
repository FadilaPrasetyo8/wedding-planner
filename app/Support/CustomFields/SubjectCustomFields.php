<?php

namespace App\Support\CustomFields;

use App\Enums\AttachmentModule;
use App\Enums\CustomFieldModule;
use App\Models\CustomFieldDefinition;
use App\Models\Wedding;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Shared payload builder for subject detail pages: active custom-field
 * definitions with their stored values, plus the subject's attachments.
 * Keeps the Inertia props contract identical across task, checklist item,
 * budget item, and guest pages (D-027/D-028).
 */
class SubjectCustomFields
{
    /**
     * @return array{
     *     definitions: list<array{uuid: string, fieldKey: string, label: string, fieldType: string, isRequired: bool, options: list<string>}>,
     *     values: array<string, mixed>,
     * }
     */
    public static function payload(Wedding $wedding, CustomFieldModule $module, Model $subject): array
    {
        $definitions = $wedding->customFieldDefinitions()
            ->where('module', $module->value)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $values = $wedding->customFieldValues()
            ->whereIn('custom_field_definition_id', $definitions->modelKeys())
            ->where('module', $module->value)
            ->where('subject_id', $subject->getKey())
            ->get()
            ->mapWithKeys(fn ($value): array => [
                $definitions->firstWhere('id', $value->custom_field_definition_id)?->field_key ?? (string) $value->custom_field_definition_id => $value->value['v'] ?? null,
            ]);

        return [
            'definitions' => $definitions->map(fn (CustomFieldDefinition $definition): array => [
                'uuid' => $definition->uuid,
                'fieldKey' => $definition->field_key,
                'label' => $definition->label,
                'fieldType' => $definition->field_type->value,
                'isRequired' => $definition->is_required,
                'options' => $definition->options ?? [],
            ])->all(),
            'values' => $values->all(),
        ];
    }

    /**
     * Subject attachments serialized for the AttachmentsPanel.
     *
     * @param  \Illuminate\Support\Collection<array-key, Model>  $subjects
     * @return array<int|string, list<array{uuid: string, filenameOriginal: string, mimeType: string, sizeBytes: int, createdAt: string}>>
     */
    public static function attachmentsForSubjects(Wedding $wedding, AttachmentModule $module, $subjects): array
    {
        return $wedding->attachments()
            ->where('module', $module->value)
            ->whereIn($module->subjectColumn(), $subjects->map(fn ($subject) => $subject->getKey()))
            ->orderByDesc('id')
            ->get()
            ->groupBy($module->subjectColumn())
            ->map(fn ($group) => $group->map(fn ($attachment): array => [
                'uuid' => $attachment->uuid,
                'filenameOriginal' => $attachment->filename_original,
                'mimeType' => $attachment->mime_type,
                'sizeBytes' => (int) $attachment->size_bytes,
                'createdAt' => $attachment->created_at->toIso8601String(),
            ])->all())
            ->all();
    }

    /**
     * Batch variant for list pages: values for many subjects of one module,
     * keyed by subject id → field_key → stored value.
     *
     * @param  \Illuminate\Support\Collection<array-key, Model>  $subjects
     * @return array<int|string, array<string, mixed>>
     */
    public static function valuesForSubjects(Wedding $wedding, CustomFieldModule $module, $subjects): array
    {
        $definitions = $wedding->customFieldDefinitions()
            ->where('module', $module->value)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return $wedding->customFieldValues()
            ->whereIn('custom_field_definition_id', $definitions->modelKeys())
            ->where('module', $module->value)
            ->whereIn('subject_id', $subjects->map(fn ($subject) => $subject->getKey()))
            ->get()
            ->groupBy('subject_id')
            ->map(function ($group) use ($definitions) {
                return $group->mapWithKeys(function ($value) use ($definitions) {
                    $fieldKey = $definitions->firstWhere('id', $value->custom_field_definition_id)?->field_key
                        ?? (string) $value->custom_field_definition_id;

                    return [$fieldKey => $value->value['v'] ?? null];
                })->all();
            })
            ->all();
    }

    /**
     * Active custom-field definitions serialized for list pages, so every
     * row can render the same inputs without a per-subject prop contract.
     *
     * @return list<array{uuid: string, fieldKey: string, label: string, fieldType: string, isRequired: bool, options: list<string>}>
     */
    public static function definitions(Wedding $wedding, CustomFieldModule $module): array
    {
        return $wedding->customFieldDefinitions()
            ->where('module', $module->value)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (CustomFieldDefinition $definition): array => [
                'uuid' => $definition->uuid,
                'fieldKey' => $definition->field_key,
                'label' => $definition->label,
                'fieldType' => $definition->field_type->value,
                'isRequired' => $definition->is_required,
                'options' => $definition->options ?? [],
            ])->all();
    }

    /**
     * Subject attachments serialized for the AttachmentsPanel.
     *
     * @return list<array{uuid: string, filenameOriginal: string, mimeType: string, sizeBytes: int, createdAt: string}>
     */
    public static function attachments(Wedding $wedding, AttachmentModule $module, Model $subject): array
    {
        return $wedding->attachments()
            ->where('module', $module->value)
            ->where($module->subjectColumn(), $subject->getKey())
            ->orderByDesc('id')
            ->get()
            ->map(fn ($attachment): array => [
                'uuid' => $attachment->uuid,
                'filenameOriginal' => $attachment->filename_original,
                'mimeType' => $attachment->mime_type,
                'sizeBytes' => (int) $attachment->size_bytes,
                'createdAt' => $attachment->created_at->toIso8601String(),
            ])->all();
    }

    /**
     * Generic scope helper for controllers that need to filter a subject
     * relation by the wedding's active definitions.
     *
     * @param  Builder<CustomFieldDefinition>  $query
     * @return Builder<CustomFieldDefinition>
     */
    public static function activeDefinitions(Builder $query, string $module): Builder
    {
        return $query->where('module', $module)->where('is_active', true);
    }
}
