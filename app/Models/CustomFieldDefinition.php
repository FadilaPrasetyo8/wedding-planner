<?php

namespace App\Models;

use App\Enums\CustomFieldModule;
use App\Enums\CustomFieldType;
use Database\Factories\CustomFieldDefinitionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int $wedding_id
 * @property CustomFieldModule $module
 * @property CustomFieldType $field_type
 * @property string $label
 * @property string $field_key
 * @property bool $is_required
 * @property array<int, string>|null $options
 * @property int $sort_order
 * @property bool $is_active
 * @property-read Wedding $wedding
 */
#[Fillable(['wedding_id', 'module', 'field_type', 'label', 'field_key', 'is_required', 'options', 'sort_order', 'is_active'])]
class CustomFieldDefinition extends Model
{
    /** @use HasFactory<CustomFieldDefinitionFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (CustomFieldDefinition $definition): void {
            $definition->uuid ??= (string) Str::uuid();
            $definition->field_key ??= $definition->buildFieldKey($definition->label);
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'module' => CustomFieldModule::class,
            'field_type' => CustomFieldType::class,
            'options' => 'array',
            'is_required' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Build a slug field key from a label, de-duplicated per wedding module by the caller.
     */
    public static function buildFieldKey(string $label): string
    {
        $slug = Str::slug($label);

        return $slug !== '' ? $slug : 'field';
    }

    /** @return BelongsTo<Wedding, $this> */
    public function wedding(): BelongsTo
    {
        return $this->belongsTo(Wedding::class);
    }

    /** @return HasMany<CustomFieldValue, $this> */
    public function values(): HasMany
    {
        return $this->hasMany(CustomFieldValue::class);
    }
}
