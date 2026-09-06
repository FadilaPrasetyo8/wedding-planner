<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $template_section_id
 * @property string $type
 * @property array{name: string, date_offset_days: int, is_primary: bool, sort_order: int} $payload
 * @property int $sort_order
 * @property-read TemplateSection $section
 */
#[Fillable(['template_section_id', 'type', 'payload', 'sort_order'])]
class TemplateItem extends Model
{
    /** @return BelongsTo<TemplateSection, $this> */
    public function section(): BelongsTo
    {
        return $this->belongsTo(TemplateSection::class, 'template_section_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['payload' => 'array'];
    }
}
