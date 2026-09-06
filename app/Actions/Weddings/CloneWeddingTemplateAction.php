<?php

namespace App\Actions\Weddings;

use App\Models\Template;
use App\Models\TemplateSection;
use App\Models\Wedding;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CloneWeddingTemplateAction
{
    public function execute(Wedding $wedding, ?Template $template, string $initialEventName): void
    {
        if (! $template) {
            return;
        }
        $template->load('sections.items');
        $items = $template->sections->flatMap(
            fn (TemplateSection $section) => $section->items,
        );

        foreach ($items as $item) {
            if ($item->type !== 'event') {
                throw ValidationException::withMessages(['template_id' => 'Template berisi jenis item yang belum didukung.']);
            }
            $payload = Validator::validate($item->payload, [
                'name' => ['required', 'string', 'max:255'],
                'date_offset_days' => ['required', 'integer', 'between:-3650,3650'],
                'is_primary' => ['required', 'boolean'],
                'sort_order' => ['required', 'integer', 'min:0'],
            ]);
            $date = $wedding->wedding_date->copy()->addDays($payload['date_offset_days']);
            if (mb_strtolower($payload['name']) === mb_strtolower($initialEventName) && $date->isSameDay($wedding->wedding_date)) {
                continue;
            }
            $wedding->events()->create(['name' => $payload['name'], 'event_date' => $date, 'is_primary' => false, 'sort_order' => $payload['sort_order']]);
        }
    }
}
