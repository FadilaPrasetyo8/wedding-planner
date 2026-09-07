<?php

namespace App\Actions\Weddings;

use App\Enums\TaskBehavior;
use App\Enums\TaskDateMode;
use App\Models\Task;
use App\Models\Wedding;
use Illuminate\Support\Facades\DB;

class PersistTaskAction
{
    /** @param array<string, mixed> $data */
    public function create(Wedding $wedding, array $data): Task
    {
        return DB::transaction(fn (): Task => $wedding->tasks()->create($this->normalize($wedding, $data)));
    }

    /** @param array<string, mixed> $data */
    public function update(Wedding $wedding, Task $task, array $data): Task
    {
        return DB::transaction(function () use ($wedding, $task, $data): Task {
            $task->update($this->normalize($wedding, $data));

            return $task->refresh();
        });
    }

    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function normalize(Wedding $wedding, array $data): array
    {
        $category = $wedding->taskCategories()->where('uuid', $data['task_category_id'])->firstOrFail();
        $status = $wedding->taskStatuses()->where('uuid', $data['task_status_id'])->firstOrFail();
        $event = isset($data['wedding_event_id'])
            ? $wedding->events()->where('uuid', $data['wedding_event_id'])->firstOrFail()
            : null;
        $anchor = $wedding->events()
            ->where('is_primary', true)
            ->firstOrFail(['event_date'])
            ->event_date;
        $relative = $data['date_mode'] === TaskDateMode::TemplateRelative->value;

        return [
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'task_category_id' => $category->id,
            'task_status_id' => $status->id,
            'priority' => $data['priority'],
            'assignee_membership_id' => $data['assignee_membership_id'] ?? null,
            'wedding_event_id' => $event?->id,
            'date_mode' => $data['date_mode'],
            'start_date' => $relative ? $anchor->copy()->addDays($data['relative_start_offset']) : $data['start_date'],
            'due_date' => $relative ? $anchor->copy()->addDays($data['relative_due_offset']) : $data['due_date'],
            'relative_start_offset' => $relative ? $data['relative_start_offset'] : null,
            'relative_due_offset' => $relative ? $data['relative_due_offset'] : null,
            'completed_at' => $status->behavior === TaskBehavior::Completed ? now() : null,
            'notes' => $data['notes'] ?? null,
        ];
    }
}
