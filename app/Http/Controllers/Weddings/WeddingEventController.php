<?php

namespace App\Http\Controllers\Weddings;

use App\Actions\Weddings\ChangePrimaryWeddingEventAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Weddings\StoreWeddingEventRequest;
use App\Http\Requests\Weddings\UpdateWeddingEventRequest;
use App\Models\Wedding;
use App\Models\WeddingEvent;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class WeddingEventController extends Controller
{
    public function index(Wedding $wedding): Response
    {
        abort_unless(request()->user()->can('view', $wedding), 403);

        return Inertia::render('weddings/events/index', [
            'events' => $wedding->events()
                ->orderByDesc('is_primary')
                ->orderBy('event_date')
                ->orderBy('starts_at')
                ->orderBy('id')
                ->get(['uuid', 'name', 'type', 'event_date', 'starts_at', 'ends_at', 'venue', 'notes', 'is_primary'])
                ->map(fn (WeddingEvent $event): array => [
                    'uuid' => $event->uuid,
                    'name' => $event->name,
                    'type' => $event->type,
                    'event_date' => $event->event_date->toDateString(),
                    'starts_at' => $event->starts_at,
                    'ends_at' => $event->ends_at,
                    'venue' => $event->venue,
                    'notes' => $event->notes,
                    'is_primary' => $event->is_primary,
                ]),
            'canEdit' => request()->user()->can('editOperationalData', $wedding),
        ]);
    }

    public function show(Wedding $wedding, WeddingEvent $event): Response
    {
        abort_unless(request()->user()->can('view', $event), 403);

        return Inertia::render('weddings/events/show', [
            'event' => [
                'uuid' => $event->uuid,
                'name' => $event->name,
                'type' => $event->type,
                'eventDate' => $event->event_date->toDateString(),
                'startsAt' => $event->starts_at,
                'endsAt' => $event->ends_at,
                'venue' => $event->venue,
                'notes' => $event->notes,
                'isPrimary' => $event->is_primary,
            ],
            'canEdit' => request()->user()->can('update', $event),
        ]);
    }

    public function create(Wedding $wedding): RedirectResponse
    {
        abort_unless(
            request()->user()->can('create', new WeddingEvent(['wedding_id' => $wedding->id])),
            403,
        );

        return redirect()->route('weddings.events.index', $wedding);
    }

    public function store(
        StoreWeddingEventRequest $request,
        Wedding $wedding,
        ChangePrimaryWeddingEventAction $events,
    ): RedirectResponse {
        $events->create($wedding, $request->eventData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Acara berhasil ditambahkan.')]);

        return to_route('weddings.events.index', $wedding);
    }

    public function edit(Wedding $wedding, WeddingEvent $event): RedirectResponse
    {
        abort_unless(request()->user()->can('update', $event), 403);

        return redirect()->route('weddings.events.index', $wedding);
    }

    public function update(
        UpdateWeddingEventRequest $request,
        Wedding $wedding,
        WeddingEvent $event,
        ChangePrimaryWeddingEventAction $events,
    ): RedirectResponse {
        $events->update($wedding, $event, $request->eventData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Acara berhasil diperbarui.')]);

        return to_route('weddings.events.index', $wedding);
    }

    public function destroy(Wedding $wedding, WeddingEvent $event): RedirectResponse
    {
        abort_unless(request()->user()->can('delete', $event), 403);

        if ($event->is_primary) {
            return back()->withErrors([
                'event' => 'Pilih acara utama pengganti sebelum menghapus acara ini.',
            ]);
        }

        $event->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Acara berhasil dihapus.')]);

        return to_route('weddings.events.index', $wedding);
    }
}
