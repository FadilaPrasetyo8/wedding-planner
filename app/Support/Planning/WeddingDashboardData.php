<?php

namespace App\Support\Planning;

use App\Enums\TaskBehavior;
use App\Models\Task;
use App\Models\Wedding;
use App\Models\WeddingEvent;
use App\Support\Finance\FinanceCalculations;

class WeddingDashboardData
{
    public function __construct(
        private readonly FinanceCalculations $calculations = new FinanceCalculations,
    ) {}

    /** @return array<string, mixed> */
    public function for(Wedding $wedding): array
    {
        $today = now($wedding->timezone)->startOfDay();
        $primaryEvent = $wedding->events()->where('is_primary', true)->first(['uuid', 'name', 'event_date']);
        $includedBehaviors = [
            TaskBehavior::Todo->value,
            TaskBehavior::InProgress->value,
            TaskBehavior::Completed->value,
        ];

        $total = $wedding->tasks()
            ->whereHas('status', fn ($status) => $status->whereIn('behavior', $includedBehaviors))
            ->count();
        $completed = $wedding->tasks()
            ->whereHas('status', fn ($status) => $status->where('behavior', TaskBehavior::Completed->value))
            ->count();

        $overdueQuery = $wedding->tasks()
            ->with(['status:id,uuid,name,color,behavior', 'category:id,uuid,name,color'])
            ->whereDate('due_date', '<', $today->toDateString())
            ->whereHas('status', fn ($status) => $status->whereNotIn('behavior', [
                TaskBehavior::Completed->value,
                TaskBehavior::Cancelled->value,
            ]));

        $budgetSummary = $this->calculations->budgetSummary($wedding);

        return [
            'primaryEvent' => $primaryEvent ? [
                'uuid' => $primaryEvent->uuid,
                'name' => $primaryEvent->name,
                'date' => $primaryEvent->event_date->toDateString(),
                'daysUntil' => (int) $today->diffInDays($primaryEvent->event_date, false),
            ] : null,
            'taskSummary' => [
                'total' => $total,
                'completed' => $completed,
                'completionPercentage' => $total === 0 ? 0 : (int) round(($completed / $total) * 100),
            ],
            'overdueCount' => (clone $overdueQuery)->count(),
            'overdueTasks' => (clone $overdueQuery)
                ->orderBy('due_date')->orderBy('id')->limit(5)->get()
                ->map(fn (Task $task): array => $this->task($task)),
            'upcomingTasks' => $wedding->tasks()
                ->with(['status:id,uuid,name,color,behavior', 'category:id,uuid,name,color'])
                ->whereDate('due_date', '>=', $today->toDateString())
                ->whereHas('status', fn ($status) => $status->whereNotIn('behavior', [
                    TaskBehavior::Completed->value,
                    TaskBehavior::Cancelled->value,
                ]))
                ->orderBy('due_date')->orderBy('id')->limit(5)->get()
                ->map(fn (Task $task): array => $this->task($task)),
            'upcomingEvents' => $wedding->events()
                ->whereDate('event_date', '>=', $today->toDateString())
                ->orderBy('event_date')->orderBy('sort_order')->limit(5)
                ->get(['uuid', 'name', 'event_date', 'is_primary'])
                ->map(fn (WeddingEvent $event): array => [
                    'uuid' => $event->uuid,
                    'name' => $event->name,
                    'date' => $event->event_date->toDateString(),
                    'isPrimary' => $event->is_primary,
                ]),
            'statusGroups' => $wedding->taskStatuses()
                ->withCount('tasks')->orderBy('sort_order')->get(['uuid', 'name', 'color', 'behavior'])
                ->map(fn ($status): array => [
                    'uuid' => $status->uuid,
                    'name' => $status->name,
                    'color' => $status->color,
                    'behavior' => $status->behavior->value,
                    'count' => $status->tasks_count,
                ]),
            'categoryGroups' => $wedding->taskCategories()
                ->withCount('tasks')->orderBy('sort_order')->get(['uuid', 'name', 'color'])
                ->map(fn ($category): array => [
                    'uuid' => $category->uuid,
                    'name' => $category->name,
                    'color' => $category->color,
                    'count' => $category->tasks_count,
                ]),
            'financeSummary' => [
                'estimatedTotal' => $budgetSummary['estimatedTotal'],
                'committedTotal' => $budgetSummary['committedTotal'],
                'paidTotal' => $budgetSummary['paidTotal'],
                'outstanding' => $budgetSummary['outstanding'],
                'overpaid' => $budgetSummary['overpaid'],
            ],
            'upcomingPayments' => $this->calculations->upcomingPayments($wedding, 3),
        ];
    }

    /** @return array<string, mixed> */
    private function task(Task $task): array
    {
        return [
            'uuid' => $task->uuid,
            'title' => $task->title,
            'dueDate' => $task->due_date?->toDateString(),
            'status' => $task->status->name,
            'category' => $task->category->name,
        ];
    }
}
