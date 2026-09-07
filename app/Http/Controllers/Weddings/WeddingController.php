<?php

namespace App\Http\Controllers\Weddings;

use App\Actions\Weddings\CreateWeddingAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Weddings\StoreWeddingRequest;
use App\Models\Template;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class WeddingController extends Controller
{
    public function create(): Response
    {
        $templates = Template::query()
            ->where('is_active', true)
            ->with('sections.items')
            ->orderBy('name')
            ->get()
            ->map(function (Template $template): array {
                $items = [];

                foreach ($template->sections as $section) {
                    foreach ($section->items as $item) {
                        $items[] = [
                            'type' => $item->type,
                            'name' => $item->payload['name'] ?? $item->payload['title'],
                        ];
                    }
                }

                return [
                    'id' => $template->id,
                    'name' => $template->name,
                    'description' => $template->description,
                    'items' => $items,
                ];
            });

        return Inertia::render('weddings/create', [
            'templates' => $templates,
            'timezones' => [
                ['value' => 'Asia/Jakarta', 'label' => 'WIB — Jakarta'],
                ['value' => 'Asia/Makassar', 'label' => 'WITA — Makassar'],
                ['value' => 'Asia/Jayapura', 'label' => 'WIT — Jayapura'],
            ],
        ]);
    }

    public function store(
        StoreWeddingRequest $request,
        CreateWeddingAction $createWedding,
    ): RedirectResponse {
        $wedding = $createWedding->execute($request->user(), $request->weddingData());

        $request->session()->put('active_wedding_uuid', $wedding->uuid);

        return to_route('weddings.show', $wedding);
    }
}
