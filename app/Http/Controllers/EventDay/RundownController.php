<?php

namespace App\Http\Controllers\EventDay;

use App\Enums\RundownStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\EventDay\StoreRundownItemRequest;
use App\Http\Requests\EventDay\UpdateRundownItemRequest;
use App\Models\RundownItem;
use App\Models\Wedding;
use App\Models\WeddingEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class RundownController extends Controller
{
    public function index(Request $request, Wedding $wedding): Response
    {
        $eventUuid = $request->string('event')->toString();

        $events = $wedding->events()
            ->orderBy('event_date')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'uuid', 'name', 'event_date', 'is_primary']);

        $selectedEvent = $events->first(
            fn (WeddingEvent $event): bool => $eventUuid === '' ? $event->is_primary : $event->uuid === $eventUuid,
        ) ?? $events->first();

        return Inertia::render('weddings/event-day/rundown', [
            'events' => $this->eventOptions($events),
            'selectedEvent' => $this->selectedEvent($selectedEvent),
            'items' => $this->rundownRows($wedding, $selectedEvent),
            'statuses' => array_map(
                fn (RundownStatus $status): array => ['value' => $status->value, 'label' => $status->label()],
                RundownStatus::cases(),
            ),
            'filters' => ['event' => $selectedEvent?->uuid ?? ''],
            'canEdit' => $request->user()->can('editOperationalData', $wedding),
            'canExport' => $request->user()->can('export', $wedding),
        ]);
    }

    public function print(Request $request, Wedding $wedding): Response
    {
        $eventUuid = $request->string('event')->toString();

        $events = $wedding->events()
            ->orderBy('event_date')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'uuid', 'name', 'event_date', 'is_primary']);

        $selectedEvent = $events->first(
            fn (WeddingEvent $event): bool => $eventUuid === '' ? $event->is_primary : $event->uuid === $eventUuid,
        ) ?? $events->first();

        return Inertia::render('weddings/event-day/rundown-print', [
            'events' => $this->eventOptions($events),
            'selectedEvent' => $this->selectedEvent($selectedEvent),
            'items' => $this->rundownRows($wedding, $selectedEvent),
            'filters' => ['event' => $selectedEvent?->uuid ?? ''],
        ]);
    }

    public function store(StoreRundownItemRequest $request, Wedding $wedding): RedirectResponse
    {
        $data = $request->itemData();
        $event = $this->resolveEvent($wedding, $data['event_uuid']);

        $item = DB::transaction(function () use ($wedding, $event, $data): RundownItem {
            $maxOrder = $wedding->rundownItems()
                ->where('wedding_event_id', $event->id)
                ->max('sort_order') ?? 0;

            return $wedding->rundownItems()->create([
                'wedding_event_id' => $event->id,
                'title' => $data['title'],
                'pic' => $data['pic'],
                'notes' => $data['notes'],
                'highlights' => $data['highlights'],
                'status' => $data['status'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'sort_order' => $maxOrder + 10,
            ]);
        });

        return back()->with('success', 'Item rundown '.$item->title.' berhasil ditambahkan.');
    }

    public function update(UpdateRundownItemRequest $request, Wedding $wedding, RundownItem $rundown_item): RedirectResponse
    {
        abort_unless($rundown_item->wedding_id === $wedding->id, 404);

        $data = $request->itemData();
        $event = $this->resolveEvent($wedding, $data['event_uuid']);

        $rundown_item->update([
            'wedding_event_id' => $event->id,
            'title' => $data['title'],
            'pic' => $data['pic'],
            'notes' => $data['notes'],
            'highlights' => $data['highlights'],
            'status' => $data['status'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
        ]);

        return back()->with('success', 'Item rundown '.$rundown_item->title.' berhasil diperbarui.');
    }

    public function reorder(Request $request, Wedding $wedding): RedirectResponse
    {
        $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.uuid' => ['required', 'uuid'],
        ]);

        $uuids = array_map(
            fn (array $item): string => (string) $item['uuid'],
            $request->array('items'),
        );

        $items = $wedding->rundownItems()
            ->whereIn('uuid', $uuids)
            ->get(['id', 'uuid', 'wedding_event_id', 'sort_order']);

        abort_unless($items->count() === count($uuids), 404);

        $eventId = $items->first()->wedding_event_id;
        abort_unless($items->every(fn (RundownItem $item): bool => $item->wedding_event_id === $eventId), 422);

        DB::transaction(function () use ($items, $uuids): void {
            foreach ($uuids as $index => $uuid) {
                $items->firstWhere('uuid', $uuid)->update(['sort_order' => ($index + 1) * 10]);
            }
        });

        return back()->with('success', 'Urutan rundown berhasil disimpan.');
    }

    public function destroy(Request $request, Wedding $wedding, RundownItem $rundown_item): RedirectResponse
    {
        abort_unless($rundown_item->wedding_id === $wedding->id, 404);
        abort_unless($request->user()->can('delete', $rundown_item), 403);

        $rundown_item->delete();

        return back()->with('success', 'Item rundown berhasil dihapus.');
    }

    private function resolveEvent(Wedding $wedding, string $uuid): WeddingEvent
    {
        $event = $wedding->events()->where('uuid', $uuid)->first(['id', 'uuid']);

        abort_unless($event !== null, 404);

        return $event;
    }

    /**
     * @param  Collection<int, WeddingEvent>  $events
     * @return list<array{uuid: string, name: string, eventDate: string, isPrimary: bool}>
     */
    private function eventOptions(Collection $events): array
    {
        return $events->map(fn (WeddingEvent $event): array => [
            'uuid' => $event->uuid,
            'name' => $event->name,
            'eventDate' => $event->event_date->toDateString(),
            'isPrimary' => $event->is_primary,
        ])->all();
    }

    /** @return array{uuid: string, name: string, eventDate: string}|null */
    private function selectedEvent(?WeddingEvent $event): ?array
    {
        return $event === null ? null : [
            'uuid' => $event->uuid,
            'name' => $event->name,
            'eventDate' => $event->event_date->toDateString(),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function rundownRows(Wedding $wedding, ?WeddingEvent $event): array
    {
        $items = $event === null
            ? collect()
            : $wedding->rundownItems()
                ->where('wedding_event_id', $event->id)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();

        return $items
            ->map(fn (RundownItem $item): array => [
                'uuid' => $item->uuid,
                'title' => $item->title,
                'pic' => $item->pic,
                'notes' => $item->notes,
                'highlights' => $item->highlights,
                'status' => $item->status->value,
                'statusLabel' => $item->status->label(),
                'startTime' => $item->start_time?->format('H:i'),
                'endTime' => $item->end_time?->format('H:i'),
                'sortOrder' => $item->sort_order,
                'hasOverlap' => $this->overlapService()->hasOverlap($item),
            ])
            ->all();
    }

    private function overlapService(): RundownOverlapService
    {
        return app(RundownOverlapService::class);
    }
}
