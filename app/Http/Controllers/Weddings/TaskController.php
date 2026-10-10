<?php

namespace App\Http\Controllers\Weddings;

use App\Actions\Weddings\PersistTaskAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Weddings\PlanningIndexRequest;
use App\Http\Requests\Weddings\StoreTaskRequest;
use App\Http\Requests\Weddings\UpdateTaskRequest;
use App\Models\Task;
use App\Models\Wedding;
use App\Support\CustomFields\SubjectCustomFields;
use App\Support\Planning\TaskListQuery;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    public function index(
        PlanningIndexRequest $request,
        Wedding $wedding,
        TaskListQuery $taskList,
    ): Response {
        $filters = $request->filters();
        $tasks = $taskList->apply($wedding, $filters)
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Task $task): array => [
                'uuid' => $task->uuid,
                'title' => $task->title,
                'description' => $task->description,
                'priority' => $task->priority->value,
                'startDate' => $task->start_date?->toDateString(),
                'dueDate' => $task->due_date?->toDateString(),
                'completedAt' => $task->completed_at?->toIso8601String(),
                'category' => ['uuid' => $task->category->uuid, 'name' => $task->category->name, 'color' => $task->category->color],
                'status' => ['uuid' => $task->status->uuid, 'name' => $task->status->name, 'color' => $task->status->color, 'behavior' => $task->status->behavior->value],
                'assignee' => $task->assignee?->user->name,
                'assigneeMembershipId' => $task->assignee_membership_id,
                'event' => $task->event?->name,
                'eventUuid' => $task->event?->uuid,
                'dateMode' => $task->date_mode->value,
                'relativeStartOffset' => $task->relative_start_offset,
                'relativeDueOffset' => $task->relative_due_offset,
                'notes' => $task->notes,
                'isOverdue' => $task->due_date?->isBefore(now($wedding->timezone)->startOfDay())
                    && ! in_array($task->status->behavior->value, ['completed', 'cancelled'], true),
            ]);

        return Inertia::render('weddings/planning/index', [
            'tasks' => $tasks,
            'filters' => [...$filters, 'overdue' => $request->boolean('overdue')],
            'canEdit' => $request->user()->can('editOperationalData', $wedding),
            'canExport' => $request->user()->can('export', $wedding),
            ...$this->formOptions($wedding, activeOnly: false),
        ]);
    }

    public function configuration(PlanningIndexRequest $request, Wedding $wedding): Response
    {
        return Inertia::render('weddings/planning/configuration', [
            'categories' => $wedding->taskCategories()->withCount('tasks')->orderBy('sort_order')->get(['uuid', 'name', 'color', 'sort_order', 'is_active']),
            'statuses' => $wedding->taskStatuses()->withCount('tasks')->orderBy('sort_order')->get(['uuid', 'name', 'color', 'behavior', 'sort_order', 'is_active']),
            'canEdit' => $request->user()->can('editOperationalData', $wedding),
        ]);
    }

    public function create(Wedding $wedding): RedirectResponse
    {
        abort_unless(request()->user()->can('create', new Task(['wedding_id' => $wedding->id])), 403);

        return redirect()->route('weddings.planning.index', $wedding);
    }

    public function store(StoreTaskRequest $request, Wedding $wedding, PersistTaskAction $tasks): RedirectResponse
    {
        $tasks->create($wedding, $request->taskData());

        return to_route('weddings.planning.index', $wedding)->with('success', 'Tugas berhasil ditambahkan.');
    }

    public function show(Wedding $wedding, Task $task): Response
    {
        abort_unless(request()->user()->can('view', $task), 403);
        $task->load(['category:id,uuid,name,color', 'status:id,uuid,name,color,behavior', 'assignee.user:id,name', 'event:id,uuid,name,event_date']);

        return Inertia::render('weddings/planning/show', [
            'task' => [
                'uuid' => $task->uuid,
                'title' => $task->title,
                'description' => $task->description,
                'priority' => $task->priority->value,
                'category' => $task->category->name,
                'status' => $task->status->name,
                'statusBehavior' => $task->status->behavior->value,
                'assignee' => $task->assignee?->user->name,
                'event' => $task->event?->name,
                'dateMode' => $task->date_mode->value,
                'startDate' => $task->start_date?->toDateString(),
                'dueDate' => $task->due_date?->toDateString(),
                'completedAt' => $task->completed_at?->toIso8601String(),
                'notes' => $task->notes,
            ],
            'customFields' => SubjectCustomFields::payload($wedding, \App\Enums\CustomFieldModule::Task, $task),
            'attachments' => SubjectCustomFields::attachments($wedding, \App\Enums\AttachmentModule::Task, $task),
            'canEdit' => request()->user()->can('update', $task),
        ]);
    }

    public function edit(Wedding $wedding, Task $task): RedirectResponse
    {
        abort_unless(request()->user()->can('update', $task), 403);

        return redirect()->route('weddings.planning.index', $wedding);
    }

    public function update(UpdateTaskRequest $request, Wedding $wedding, Task $task, PersistTaskAction $tasks): RedirectResponse
    {
        $tasks->update($wedding, $task, $request->taskData());

        return to_route('weddings.planning.index', $wedding)->with('success', 'Tugas berhasil diperbarui.');
    }

    public function destroy(Wedding $wedding, Task $task): RedirectResponse
    {
        abort_unless(request()->user()->can('delete', $task), 403);
        $task->delete();

        return to_route('weddings.planning.index', $wedding)->with('success', 'Tugas berhasil dihapus.');
    }

    /** @return array<string, mixed> */
    private function formOptions(Wedding $wedding, bool $activeOnly = true): array
    {
        $categories = $wedding->taskCategories();
        $statuses = $wedding->taskStatuses();

        if ($activeOnly) {
            $categories->where('is_active', true);
            $statuses->where('is_active', true);
        }

        return [
            'categories' => $categories->orderBy('sort_order')->get(['uuid', 'name']),
            'statuses' => $statuses->orderBy('sort_order')->get(['uuid', 'name', 'behavior']),
            'assignees' => $wedding->activeMemberships()->with('user:id,name')->orderBy('joined_at')->get()->map(fn ($membership): array => ['id' => $membership->id, 'name' => $membership->user->name]),
            'events' => $wedding->events()->orderBy('event_date')->get(['uuid', 'name']),
        ];
    }
}
