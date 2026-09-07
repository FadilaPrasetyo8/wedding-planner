<?php

namespace App\Actions\Weddings;

use App\Enums\TaskBehavior;
use App\Enums\TaskDateMode;
use App\Enums\TaskPriority;
use App\Models\Template;
use App\Models\TemplateItem;
use App\Models\TemplateSection;
use App\Models\Wedding;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
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

        foreach ($items->where('type', 'event') as $item) {
            $this->cloneEvent($wedding, $item, $initialEventName);
        }

        foreach ($items->where('type', 'task') as $item) {
            $this->cloneTask($wedding, $item);
        }

        if ($items->contains(fn (TemplateItem $item): bool => ! in_array($item->type, ['event', 'task'], true))) {
            throw ValidationException::withMessages([
                'template_id' => 'Template berisi jenis item yang belum didukung.',
            ]);
        }
    }

    private function cloneEvent(Wedding $wedding, TemplateItem $item, string $initialEventName): void
    {
        $payload = Validator::validate($item->payload, [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['sometimes', Rule::in(['engagement', 'akad', 'reception', 'custom'])],
            'date_offset_days' => ['required', 'integer', 'between:-3650,3650'],
            'is_primary' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ]);
        $date = $wedding->wedding_date->copy()->addDays($payload['date_offset_days']);

        if (mb_strtolower($payload['name']) === mb_strtolower($initialEventName) && $date->isSameDay($wedding->wedding_date)) {
            return;
        }

        $wedding->events()->create([
            'name' => $payload['name'],
            'type' => $payload['type'] ?? $this->eventType($payload['name']),
            'event_date' => $date,
            'is_primary' => false,
            'sort_order' => $payload['sort_order'],
        ]);
    }

    private function cloneTask(Wedding $wedding, TemplateItem $item): void
    {
        $validator = Validator::make($item->payload, [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['required', 'string', 'max:255'],
            'status_behavior' => ['required', Rule::enum(TaskBehavior::class)],
            'priority' => ['required', Rule::enum(TaskPriority::class)],
            'start_offset_days' => ['required', 'integer', 'between:-3650,3650'],
            'due_offset_days' => ['required', 'integer', 'gte:start_offset_days', 'between:-3650,3650'],
            'event_name' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages([
                'template_id' => 'Template berisi konfigurasi tugas yang tidak valid.',
            ]);
        }

        $payload = $validator->validated();

        $category = $wedding->taskCategories()->whereRaw('lower(name) = lower(?)', [$payload['category']])->first();
        $status = $wedding->taskStatuses()->where('behavior', $payload['status_behavior'])->first();

        if (! $category || ! $status) {
            throw ValidationException::withMessages([
                'template_id' => 'Template merujuk konfigurasi tugas yang tidak tersedia.',
            ]);
        }

        $event = isset($payload['event_name'])
            ? $wedding->events()->whereRaw('lower(name) = lower(?)', [$payload['event_name']])->first()
            : null;
        if (isset($payload['event_name']) && ! $event) {
            throw ValidationException::withMessages([
                'template_id' => 'Template merujuk acara yang tidak tersedia.',
            ]);
        }

        $anchor = $this->taskAnchor($wedding);
        $wedding->tasks()->create([
            'title' => $payload['title'],
            'description' => $payload['description'] ?? null,
            'task_category_id' => $category->id,
            'task_status_id' => $status->id,
            'priority' => $payload['priority'],
            'wedding_event_id' => $event?->id,
            'date_mode' => TaskDateMode::TemplateRelative,
            'start_date' => $anchor->copy()->addDays($payload['start_offset_days']),
            'due_date' => $anchor->copy()->addDays($payload['due_offset_days']),
            'relative_start_offset' => $payload['start_offset_days'],
            'relative_due_offset' => $payload['due_offset_days'],
            'notes' => $payload['notes'] ?? null,
        ]);
    }

    private function taskAnchor(Wedding $wedding): CarbonInterface
    {
        $primaryEvent = $wedding->events()->where('is_primary', true)->first();

        return $primaryEvent?->event_date->copy() ?? $wedding->wedding_date->copy();
    }

    private function eventType(string $name): string
    {
        return match (mb_strtolower($name)) {
            'akad' => 'akad',
            'resepsi' => 'reception',
            'lamaran' => 'engagement',
            default => 'custom',
        };
    }
}
