<?php

namespace App\Support\Planning;

use App\Enums\TaskBehavior;
use App\Models\Task;
use App\Models\Wedding;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaskListQuery
{
    /** @param array<string, mixed> $filters
     * @return HasMany<Task, Wedding>
     */
    public function apply(Wedding $wedding, array $filters): HasMany
    {
        $query = $wedding->tasks()->with([
            'category:id,uuid,name,color',
            'status:id,uuid,name,color,behavior',
            'assignee.user:id,name',
            'event:id,uuid,name,event_date',
        ]);

        $query->when($filters['search'] ?? null, fn (Builder $builder, string $search) => $builder->where(fn (Builder $nested) => $nested->where('title', 'ilike', "%{$search}%")->orWhere('description', 'ilike', "%{$search}%")));
        $query->when($filters['status'] ?? null, fn (Builder $builder, string $uuid) => $builder->whereHas('status', fn (Builder $status) => $status->where('uuid', $uuid)));
        $query->when($filters['category'] ?? null, fn (Builder $builder, string $uuid) => $builder->whereHas('category', fn (Builder $category) => $category->where('uuid', $uuid)));
        $query->when($filters['priority'] ?? null, fn (Builder $builder, string $priority) => $builder->where('priority', $priority));
        $query->when($filters['assignee'] ?? null, fn (Builder $builder, int $id) => $builder->where('assignee_membership_id', $id));
        $query->when($filters['event'] ?? null, fn (Builder $builder, string $uuid) => $builder->whereHas('event', fn (Builder $event) => $event->where('uuid', $uuid)));
        $query->when($filters['overdue'] ?? false, fn (Builder $builder) => $builder->whereDate('due_date', '<', now($wedding->timezone)->toDateString())->whereHas('status', fn (Builder $status) => $status->whereNotIn('behavior', [TaskBehavior::Completed, TaskBehavior::Cancelled])));
        $query->when($filters['date_from'] ?? null, fn (Builder $builder, string $date) => $builder->whereDate('due_date', '>=', $date));
        $query->when($filters['date_to'] ?? null, fn (Builder $builder, string $date) => $builder->whereDate('start_date', '<=', $date));

        $sort = $filters['sort'] ?? 'due_date';
        $direction = $filters['direction'] ?? 'asc';
        if ($sort === 'status') {
            $query->orderBy(Task::select('sort_order')->from('task_statuses')->whereColumn('task_statuses.id', 'tasks.task_status_id'), $direction);
        } elseif ($sort === 'category') {
            $query->orderBy(Task::select('sort_order')->from('task_categories')->whereColumn('task_categories.id', 'tasks.task_category_id'), $direction);
        } else {
            $query->orderBy($sort, $direction);
        }

        return $query->orderBy('tasks.id', $direction);
    }
}
