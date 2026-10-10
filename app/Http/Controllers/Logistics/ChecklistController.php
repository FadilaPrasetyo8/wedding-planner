<?php

namespace App\Http\Controllers\Logistics;

use App\Enums\AttachmentModule;
use App\Enums\ChecklistType;
use App\Enums\CustomFieldModule;
use App\Http\Controllers\Controller;
use App\Http\Requests\Logistics\StoreChecklistItemRequest;
use App\Http\Requests\Logistics\StoreChecklistReferenceRequest;
use App\Http\Requests\Logistics\UpdateChecklistItemRequest;
use App\Models\ChecklistItem;
use App\Models\Wedding;
use App\Models\WeddingMember;
use App\Support\CustomFields\SubjectCustomFields;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ChecklistController extends Controller
{
    public function index(Request $request, Wedding $wedding, string $type): Response
    {
        $this->assertType($type);
        $checklistType = ChecklistType::from($type);

        $search = $request->string('search')->trim()->toString();
        $statusUuid = $request->string('status')->toString();
        $categoryUuid = $request->string('category')->toString();

        $items = $wedding->checklistItems()
            ->where('type', $type)
            ->with(['group:id,uuid,name', 'category:id,uuid,name', 'status:id,uuid,name,color,is_completed', 'assignee:id,user_id'])
            ->when($search !== '', fn ($query) => $query->where('name', 'ilike', '%'.$search.'%'))
            ->when($statusUuid !== '', fn ($query) => $query->whereHas('status', fn ($status) => $status->where('uuid', $statusUuid)))
            ->when($categoryUuid !== '', fn ($query) => $query->whereHas('category', fn ($category) => $category->where('uuid', $categoryUuid)))
            ->orderBy('deadline')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        // Custom fields + attachments for the current page, keyed by item uuid
        // so each inline row can render the same panels as the detail modules.
        // This must run before through() replaces the collection items with
        // serialized arrays.
        $customFieldDefinitions = SubjectCustomFields::definitions($wedding, CustomFieldModule::ChecklistItem);
        $customValues = collect();

        if ($customFieldDefinitions !== []) {
            $valuesBySubject = SubjectCustomFields::valuesForSubjects($wedding, CustomFieldModule::ChecklistItem, $items->getCollection());
            $customValues = collect($valuesBySubject)->mapWithKeys(fn ($values, $subjectId): array => [
                (string) $items->getCollection()->firstWhere('id', (int) $subjectId)?->uuid => $values,
            ]);
        }

        // Attachments load independently of custom-field definitions: a wedding
        // without definitions can still hold uploaded proof files.
        $attachmentsBySubject = SubjectCustomFields::attachmentsForSubjects($wedding, AttachmentModule::ChecklistItem, $items->getCollection());
        $itemAttachments = collect($attachmentsBySubject)->mapWithKeys(fn ($list, $subjectId): array => [
            (string) $items->getCollection()->firstWhere('id', (int) $subjectId)?->uuid => $list,
        ]);

        $items->through(function (ChecklistItem $item) use ($type): array {
            $assignee = $item->assignee?->user;

            return [
                'uuid' => $item->uuid,
                'name' => $item->name,
                'quantity' => $item->quantity,
                'estimatedCost' => $item->estimated_cost,
                'actualCost' => $item->actual_cost,
                'estimatedTotal' => $item->estimatedTotal(),
                'actualTotal' => $item->actualTotal(),
                'deadline' => $item->deadline?->toDateString(),
                'completedAt' => $item->completed_at?->toDateTimeString(),
                'isOverdue' => $item->isOverdue(),
                'purchaseUrl' => $type === ChecklistType::Seserahan->value ? $item->purchase_url : null,
                'notes' => $item->notes,
                'group' => $item->group?->name,
                'groupUuid' => $item->group?->uuid,
                'category' => $item->category?->name,
                'categoryUuid' => $item->category?->uuid,
                'status' => $item->status?->name,
                'statusUuid' => $item->status?->uuid,
                'statusColor' => $item->status?->color,
                'isCompleted' => $item->status !== null && $item->status->is_completed,
                'assigneeName' => $assignee?->name,
                'assigneeMemberId' => $item->assignee_member_id,
            ];
        });

        $members = $wedding->activeMemberships()
            ->with('user:id,name')
            ->get(['id', 'user_id'])
            ->map(fn (WeddingMember $member): array => [
                'id' => $member->id,
                'name' => $member->user->name,
            ]);

        return Inertia::render('weddings/checklists/index', [
            'type' => $type,
            'typeLabel' => $checklistType->label(),
            'items' => $items,
            'statuses' => $wedding->checklistStatuses()->where('type', $type)->orderBy('sort_order')->get(['uuid', 'name', 'color', 'is_completed']),
            'categories' => $wedding->checklistCategories()->where('type', $type)->orderBy('sort_order')->get(['uuid', 'name']),
            'members' => $members,
            'summary' => $this->summary($wedding, $type),
            'filters' => ['search' => $search, 'status' => $statusUuid, 'category' => $categoryUuid],
            'customFieldDefinitions' => $customFieldDefinitions,
            'customValues' => $customValues->all(),
            'itemAttachments' => $itemAttachments->all(),
            'canEdit' => $request->user()->can('editOperationalData', $wedding),
            'canExport' => $request->user()->can('export', $wedding),
        ]);
    }

    public function store(StoreChecklistItemRequest $request, Wedding $wedding, string $type): RedirectResponse
    {
        $this->assertType($type);
        $data = $request->itemData();

        $status = $data['checklist_status_uuid'] !== null
            ? $wedding->checklistStatuses()->where('type', $type)->where('uuid', $data['checklist_status_uuid'])->firstOrFail(['id', 'is_completed'])
            : null;

        $item = $wedding->checklistItems()->create([
            'type' => $type,
            'name' => $data['name'],
            'checklist_group_id' => $this->resolveId($wedding, 'checklist_groups', $type, $data['checklist_group_uuid']),
            'checklist_category_id' => $this->resolveId($wedding, 'checklist_categories', $type, $data['checklist_category_uuid']),
            'checklist_status_id' => $status?->id,
            'assignee_member_id' => $data['assignee_member_id'],
            'notes' => $data['notes'],
            'quantity' => $data['quantity'],
            'estimated_cost' => $data['estimated_cost'],
            'actual_cost' => $data['actual_cost'],
            'deadline' => $data['deadline'],
            'completed_at' => $status?->is_completed ? now() : null,
            'purchase_url' => $type === ChecklistType::Seserahan->value ? $data['purchase_url'] : null,
        ]);

        return back()->with('success', 'Item '.$item->name.' berhasil ditambahkan.');
    }

    public function update(UpdateChecklistItemRequest $request, Wedding $wedding, string $type, ChecklistItem $checklist_item): RedirectResponse
    {
        abort_unless($checklist_item->wedding_id === $wedding->id && $checklist_item->type->value === $type, 404);

        $data = $request->itemData();

        $status = $data['checklist_status_uuid'] !== null
            ? $wedding->checklistStatuses()->where('type', $type)->where('uuid', $data['checklist_status_uuid'])->firstOrFail(['id', 'is_completed'])
            : null;

        $checklist_item->update([
            'name' => $data['name'],
            'checklist_group_id' => $this->resolveId($wedding, 'checklist_groups', $type, $data['checklist_group_uuid']),
            'checklist_category_id' => $this->resolveId($wedding, 'checklist_categories', $type, $data['checklist_category_uuid']),
            'checklist_status_id' => $status?->id,
            'assignee_member_id' => $data['assignee_member_id'],
            'notes' => $data['notes'],
            'quantity' => $data['quantity'],
            'estimated_cost' => $data['estimated_cost'],
            'actual_cost' => $data['actual_cost'],
            'deadline' => $data['deadline'],
            'completed_at' => match (true) {
                $status?->is_completed && $checklist_item->completed_at === null => now(),
                $status?->is_completed => $checklist_item->completed_at,
                default => null,
            },
            'purchase_url' => $type === ChecklistType::Seserahan->value ? $data['purchase_url'] : null,
        ]);

        return back()->with('success', 'Item '.$checklist_item->name.' berhasil diperbarui.');
    }

    public function destroy(Request $request, Wedding $wedding, string $type, ChecklistItem $checklist_item): RedirectResponse
    {
        abort_unless($checklist_item->wedding_id === $wedding->id && $checklist_item->type->value === $type, 404);
        abort_unless($request->user()->can('delete', $checklist_item), 403);

        $checklist_item->delete();

        return back()->with('success', 'Item checklist berhasil dihapus.');
    }

    public function storeReference(StoreChecklistReferenceRequest $request, Wedding $wedding, string $type, string $reference): RedirectResponse
    {
        $this->assertType($type);
        abort_unless($reference === 'statuses' || $reference === 'categories', 404);

        if ($reference === 'statuses') {
            $wedding->checklistStatuses()->create($request->referenceData());
        } else {
            $wedding->checklistCategories()->create($request->referenceData());
        }

        return back()->with('success', 'Referensi checklist berhasil ditambahkan.');
    }

    public function destroyReference(Request $request, Wedding $wedding, string $type, string $reference, string $uuid): RedirectResponse
    {
        $this->assertType($type);
        abort_unless($request->user()->can('editOperationalData', $wedding), 403);

        if ($reference === 'statuses') {
            $model = $wedding->checklistStatuses()->where('type', $type)->where('uuid', $uuid)->firstOrFail();

            if ($model->items()->exists()) {
                return back()->withErrors(['reference' => 'Status yang masih digunakan tidak dapat dihapus.']);
            }
        } elseif ($reference === 'categories') {
            $model = $wedding->checklistCategories()->where('type', $type)->where('uuid', $uuid)->firstOrFail();

            if ($model->items()->exists()) {
                return back()->withErrors(['reference' => 'Kategori yang masih digunakan tidak dapat dihapus.']);
            }
        } else {
            abort(404);
        }

        $model->delete();

        return back()->with('success', 'Referensi checklist berhasil dihapus.');
    }

    /** @return array{estimatedTotal: int, actualTotal: int, itemCount: int, completedCount: int, overdueCount: int} */
    private function summary(Wedding $wedding, string $type): array
    {
        $items = $wedding->checklistItems()
            ->where('type', $type)
            ->with('status:id,is_completed')
            ->get(['id', 'quantity', 'estimated_cost', 'actual_cost', 'deadline', 'completed_at', 'checklist_status_id']);

        $estimatedTotal = 0;
        $actualTotal = 0;
        $completedCount = 0;
        $overdueCount = 0;

        foreach ($items as $item) {
            $estimatedTotal += $item->estimatedTotal();
            $actualTotal += $item->actualTotal();

            if ($item->status?->is_completed) {
                $completedCount++;
            }

            if ($item->isOverdue()) {
                $overdueCount++;
            }
        }

        return [
            'estimatedTotal' => $estimatedTotal,
            'actualTotal' => $actualTotal,
            'itemCount' => $items->count(),
            'completedCount' => $completedCount,
            'overdueCount' => $overdueCount,
        ];
    }

    private function resolveId(Wedding $wedding, string $table, string $type, ?string $uuid): ?int
    {
        if ($uuid === null) {
            return null;
        }

        $id = $wedding->getConnection()->table($table)
            ->where('wedding_id', $wedding->id)
            ->where('type', $type)
            ->where('uuid', $uuid)
            ->value('id');

        abort_unless($id !== null, 404);

        return (int) $id;
    }

    private function assertType(string $type): void
    {
        abort_unless(ChecklistType::tryFrom($type) !== null, 404);
    }
}
