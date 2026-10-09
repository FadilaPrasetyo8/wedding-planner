<?php

namespace App\Http\Controllers\Exports;

use App\Enums\ChecklistType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Exports\WeddingExportRequest;
use App\Models\BudgetItem;
use App\Models\ChecklistItem;
use App\Models\Guest;
use App\Models\RundownItem;
use App\Models\Task;
use App\Models\Wedding;
use App\Support\Exports\CsvExportResponse;
use App\Support\Planning\TaskListQuery;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WeddingExportController extends Controller
{
    public function budget(WeddingExportRequest $request, Wedding $wedding): StreamedResponse
    {
        $filters = $request->filters();
        $items = $wedding->budgetItems()
            ->with(['category:id,name', 'event:id,name', 'vendor:id,name', 'paymentTerms:id,budget_item_id,planned_amount,actual_paid_amount,status'])
            ->when($filters['search'] ?? null, fn ($q, string $value) => $q->where('name', 'ilike', "%{$value}%"))
            ->when($filters['status'] ?? null, fn ($q, string $value) => $q->where('status', $value))
            ->when($filters['category'] ?? null, fn ($q, string $value) => $q->whereHas('category', fn ($category) => $category->where('uuid', $value)))
            ->orderBy('name')->orderBy('id')->get();

        return CsvExportResponse::stream($this->filename($wedding, 'budget'), ['Nama', 'Kategori', 'Acara', 'Vendor', 'Status', 'Estimasi', 'Final', 'Terbayar', 'Sisa', 'Catatan'], $items->map(fn (BudgetItem $item): array => [
            $item->name,
            $item->category?->name,
            $item->event?->name,
            $item->vendor?->name,
            $item->status->value,
            $item->estimated_amount,
            $item->final_amount,
            $item->paymentTerms->where('status.value', '!=', 'cancelled')->sum('actual_paid_amount'),
            max(0, ($item->final_amount ?? $item->estimated_amount) - $item->paymentTerms->where('status.value', '!=', 'cancelled')->sum('actual_paid_amount')),
            $item->notes,
        ]));
    }

    public function guests(WeddingExportRequest $request, Wedding $wedding): StreamedResponse
    {
        $filters = $request->filters();
        $items = $wedding->guests()
            ->with(['side:id,name', 'group:id,name'])
            ->when($filters['search'] ?? null, fn ($q, string $value) => $q->where('name', 'ilike', "%{$value}%"))
            ->when($filters['side'] ?? null, fn ($q, string $value) => $q->whereHas('side', fn ($side) => $side->where('uuid', $value)))
            ->when($filters['group'] ?? null, fn ($q, string $value) => $q->whereHas('group', fn ($group) => $group->where('uuid', $value)))
            ->when($filters['invitation'] ?? null, fn ($q, string $value) => $q->where('invitation_status', $value))
            ->when($filters['rsvp'] ?? null, fn ($q, string $value) => $q->where('rsvp_status', $value))
            ->orderBy('name')->orderBy('id')->get();

        return CsvExportResponse::stream($this->filename($wedding, 'tamu'), ['Nama', 'Sisi', 'Grup', 'Telepon', 'Undangan', 'Pax', 'Status Undangan', 'RSVP', 'Catatan'], $items->map(fn (Guest $guest): array => [
            $guest->name, $guest->side?->name, $guest->group?->name, $guest->phone, $guest->invitation_count, $guest->pax_count,
            $guest->invitation_status->value, $guest->rsvp_status?->value, $guest->notes,
        ]));
    }

    public function planning(WeddingExportRequest $request, Wedding $wedding, TaskListQuery $taskListQuery): StreamedResponse
    {
        $items = $taskListQuery->apply($wedding, $request->filters())->get();

        return CsvExportResponse::stream($this->filename($wedding, 'timeline-tugas'), ['Tugas', 'Kategori', 'Status', 'Prioritas', 'Penanggung Jawab', 'Acara', 'Mulai', 'Tenggat', 'Selesai', 'Catatan'], $items->map(fn (Task $task): array => [
            $task->title, $task->category?->name, $task->status?->name, $task->priority->value,
            $task->assignee?->user?->name, $task->event?->name, $task->start_date?->toDateString(),
            $task->due_date?->toDateString(), $task->completed_at?->toDateTimeString(), $task->notes ?? $task->description,
        ]));
    }

    public function checklist(WeddingExportRequest $request, Wedding $wedding, string $type): StreamedResponse
    {
        abort_unless(ChecklistType::tryFrom($type) !== null, 404);
        $filters = $request->filters();
        $items = $wedding->checklistItems()
            ->where('type', $type)
            ->with(['group:id,name', 'category:id,name', 'status:id,name', 'assignee.user:id,name'])
            ->when($filters['search'] ?? null, fn ($q, string $value) => $q->where('name', 'ilike', "%{$value}%"))
            ->when($filters['status'] ?? null, fn ($q, string $value) => $q->whereHas('status', fn ($status) => $status->where('uuid', $value)))
            ->when($filters['category'] ?? null, fn ($q, string $value) => $q->whereHas('category', fn ($category) => $category->where('uuid', $value)))
            ->orderBy('deadline')->orderBy('id')->get();

        return CsvExportResponse::stream($this->filename($wedding, 'checklist-'.$type), ['Nama', 'Grup', 'Kategori', 'Status', 'Penanggung Jawab', 'Jumlah', 'Estimasi', 'Aktual', 'Tenggat', 'Catatan'], $items->map(fn (ChecklistItem $item): array => [
            $item->name, $item->group?->name, $item->category?->name, $item->status?->name, $item->assignee?->user?->name,
            $item->quantity, $item->estimatedTotal(), $item->actualTotal(), $item->deadline?->toDateString(), $item->notes,
        ]));
    }

    public function rundown(WeddingExportRequest $request, Wedding $wedding): StreamedResponse
    {
        $events = $wedding->events()->orderBy('event_date')->orderBy('sort_order')->orderBy('id')->get(['id', 'uuid', 'name', 'is_primary']);
        $requestedEvent = $request->string('event')->toString();
        $event = $events->first(fn ($candidate) => $requestedEvent === '' ? $candidate->is_primary : $candidate->uuid === $requestedEvent) ?? $events->first();
        $items = $event === null ? collect() : $wedding->rundownItems()->where('wedding_event_id', $event->id)->orderBy('sort_order')->orderBy('id')->get();

        return CsvExportResponse::stream($this->filename($wedding, 'rundown'), ['Acara', 'Urutan', 'Waktu Mulai', 'Waktu Selesai', 'Judul', 'PIC', 'Status', 'Highlight', 'Catatan'], $items->map(fn (RundownItem $item): array => [
            $event?->name, $item->sort_order, $item->start_time?->format('H:i'), $item->end_time?->format('H:i'), $item->title,
            $item->pic, $item->status->value, $item->highlights, $item->notes,
        ]));
    }

    private function filename(Wedding $wedding, string $module): string
    {
        $slug = preg_replace('/[^A-Za-z0-9]+/', '-', strtolower($wedding->name)) ?: 'wedding';

        return trim($slug, '-').'-'.$module.'-'.now()->format('Y-m-d').'.csv';
    }
}
