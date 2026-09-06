<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $template_id
 * @property string $name
 * @property int $sort_order
 * @property-read Template $template
 * @property-read Collection<int, TemplateItem> $items
 */
#[Fillable(['template_id', 'name', 'sort_order'])]
class TemplateSection extends Model
{
    /** @return BelongsTo<Template, $this> */
    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    /** @return HasMany<TemplateItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(TemplateItem::class)->orderBy('sort_order');
    }
}
