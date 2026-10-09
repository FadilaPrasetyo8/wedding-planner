<?php

namespace App\Http\Controllers\Weddings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Weddings\PlanningIndexRequest;
use App\Models\Task;
use App\Models\Wedding;
use App\Support\Planning\TaskListQuery;
use Inertia\Inertia;
use Inertia\Response;

class WeddingTimelineController extends Controller
{
    public function __invoke(PlanningIndexRequest $request, Wedding $wedding, TaskListQuery $taskList): Response
    {
        $filters = $request->filters();
        $tasks = $taskList->apply($wedding, $filters)
            ->whereNotNull('start_date')
            ->whereNotNull('due_date')
            ->limit(200)
            ->get()
            ->map(fn (Task $task): array => [
                'uuid' => $task->uuid,
                'title' => $task->title,
                'startDate' => $task->start_date->toDateString(),
                'dueDate' => $task->due_date->toDateString(),
                'status' => $task->status->name,
                'statusBehavior' => $task->status->behavior->value,
                'isOverdue' => $task->due_date->isBefore(now($wedding->timezone)->startOfDay())
                    && ! in_array($task->status->behavior->value, ['completed', 'cancelled'], true),
            ]);

        return Inertia::render('weddings/planning/timeline', [
            'tasks' => $tasks,
            'events' => $wedding->events()->orderBy('event_date')->limit(50)->get(['uuid', 'name', 'event_date', 'is_primary']),
            'filters' => $filters,
            'canEdit' => $request->user()->can('editOperationalData', $wedding),
            'canExport' => $request->user()->can('export', $wedding),
            'categories' => $wedding->taskCategories()->orderBy('sort_order')->get(['uuid', 'name']),
            'statuses' => $wedding->taskStatuses()->orderBy('sort_order')->get(['uuid', 'name']),
            'assignees' => $wedding->activeMemberships()->with('user:id,name')->orderBy('joined_at')->get()->map(fn ($membership): array => ['id' => $membership->id, 'name' => $membership->user->name]),
        ]);
    }
}
