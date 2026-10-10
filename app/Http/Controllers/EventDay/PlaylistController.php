<?php

namespace App\Http\Controllers\EventDay;

use App\Http\Controllers\Controller;
use App\Http\Requests\EventDay\StorePlaylistItemRequest;
use App\Http\Requests\EventDay\UpdatePlaylistItemRequest;
use App\Models\PlaylistItem;
use App\Models\Wedding;
use App\Models\WeddingEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PlaylistController extends Controller
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

        $items = $selectedEvent === null
            ? collect()
            : $wedding->playlistItems()
                ->where('wedding_event_id', $selectedEvent->id)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->map(fn (PlaylistItem $item): array => [
                    'uuid' => $item->uuid,
                    'title' => $item->title,
                    'artist' => $item->artist,
                    'link' => $item->link,
                    'requestedBy' => $item->requested_by,
                    'moment' => $item->moment,
                    'notes' => $item->notes,
                    'sortOrder' => $item->sort_order,
                ]);

        return Inertia::render('weddings/event-day/playlist', [
            'events' => $events->map(fn (WeddingEvent $event): array => [
                'uuid' => $event->uuid,
                'name' => $event->name,
                'eventDate' => $event->event_date->toDateString(),
                'isPrimary' => $event->is_primary,
            ])->all(),
            'selectedEvent' => $selectedEvent === null ? null : [
                'uuid' => $selectedEvent->uuid,
                'name' => $selectedEvent->name,
                'eventDate' => $selectedEvent->event_date->toDateString(),
            ],
            'items' => $items->all(),
            'filters' => ['event' => $selectedEvent?->uuid ?? ''],
            'canEdit' => $request->user()->can('editOperationalData', $wedding),
        ]);
    }

    public function store(StorePlaylistItemRequest $request, Wedding $wedding): RedirectResponse
    {
        $data = $request->itemData();
        $event = $this->resolveEvent($wedding, $data['event_uuid']);

        $item = DB::transaction(function () use ($wedding, $event, $data): PlaylistItem {
            $maxOrder = $wedding->playlistItems()
                ->where('wedding_event_id', $event->id)
                ->max('sort_order') ?? 0;

            return $wedding->playlistItems()->create([
                'wedding_event_id' => $event->id,
                'title' => $data['title'],
                'artist' => $data['artist'],
                'link' => $data['link'],
                'requested_by' => $data['requested_by'],
                'moment' => $data['moment'],
                'notes' => $data['notes'],
                'sort_order' => $maxOrder + 10,
            ]);
        });

        return back()->with('success', 'Playlist '.$item->title.' berhasil ditambahkan.');
    }

    public function update(UpdatePlaylistItemRequest $request, Wedding $wedding, PlaylistItem $playlist_item): RedirectResponse
    {
        abort_unless($playlist_item->wedding_id === $wedding->id, 404);

        $data = $request->itemData();
        $event = $this->resolveEvent($wedding, $data['event_uuid']);

        $playlist_item->update([
            'wedding_event_id' => $event->id,
            'title' => $data['title'],
            'artist' => $data['artist'],
            'link' => $data['link'],
            'requested_by' => $data['requested_by'],
            'moment' => $data['moment'],
            'notes' => $data['notes'],
        ]);

        return back()->with('success', 'Playlist '.$playlist_item->title.' berhasil diperbarui.');
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

        $items = $wedding->playlistItems()
            ->whereIn('uuid', $uuids)
            ->get(['id', 'uuid', 'wedding_event_id', 'sort_order']);

        abort_unless($items->count() === count($uuids), 404);

        $eventId = $items->first()->wedding_event_id;
        abort_unless($items->every(fn (PlaylistItem $item): bool => $item->wedding_event_id === $eventId), 422);

        DB::transaction(function () use ($items, $uuids): void {
            foreach ($uuids as $index => $uuid) {
                $items->firstWhere('uuid', $uuid)->update(['sort_order' => ($index + 1) * 10]);
            }
        });

        return back()->with('success', 'Urutan playlist berhasil disimpan.');
    }

    public function destroy(Request $request, Wedding $wedding, PlaylistItem $playlist_item): RedirectResponse
    {
        abort_unless($playlist_item->wedding_id === $wedding->id, 404);
        abort_unless($request->user()->can('delete', $playlist_item), 403);

        $playlist_item->delete();

        return back()->with('success', 'Playlist berhasil dihapus.');
    }

    private function resolveEvent(Wedding $wedding, string $uuid): WeddingEvent
    {
        $event = $wedding->events()->where('uuid', $uuid)->first(['id', 'uuid']);

        abort_unless($event !== null, 404);

        return $event;
    }
}
