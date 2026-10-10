<?php

namespace App\Models;

use App\Enums\CustomFieldModule;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int $wedding_id
 * @property CustomFieldModule $module
 * @property int $custom_field_definition_id
 * @property int $subject_id
 * @property array<string, mixed>|null $value
 * @property-read CustomFieldDefinition $definition
 * @property-read Wedding $wedding
 */
#[Fillable(['wedding_id', 'custom_field_definition_id', 'module', 'subject_id', 'value'])]
class CustomFieldValue extends Model
{
    protected static function booted(): void
    {
        static::creating(fn (CustomFieldValue $value) => $value->uuid ??= (string) Str::uuid());
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'module' => CustomFieldModule::class,
            'value' => 'array',
        ];
    }

    /** @return BelongsTo<CustomFieldDefinition, $this> */
    public function definition(): BelongsTo
    {
        return $this->belongsTo(CustomFieldDefinition::class, 'custom_field_definition_id');
    }

    /** @return BelongsTo<Wedding, $this> */
    public function wedding(): BelongsTo
    {
        return $this->belongsTo(Wedding::class);
    }
}
