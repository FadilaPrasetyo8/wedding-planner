<?php

namespace App\Models;

use App\Enums\TaskDateMode;
use App\Enums\TaskPriority;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int $wedding_id
 * @property string $title
 * @property string|null $description
 * @property int $task_category_id
 * @property int $task_status_id
 * @property TaskPriority $priority
 * @property int|null $assignee_membership_id
 * @property int|null $wedding_event_id
 * @property TaskDateMode $date_mode
 * @property Carbon|null $start_date
 * @property Carbon|null $due_date
 * @property int|null $relative_start_offset
 * @property int|null $relative_due_offset
 * @property Carbon|null $completed_at
 * @property string|null $notes
 * @property-read Wedding $wedding
 * @property-read TaskCategory $category
 * @property-read TaskStatus $status
 * @property-read WeddingMember|null $assignee
 * @property-read WeddingEvent|null $event
 */
#[Fillable(['wedding_id', 'title', 'description', 'task_category_id', 'task_status_id', 'priority', 'assignee_membership_id', 'wedding_event_id', 'date_mode', 'start_date', 'due_date', 'relative_start_offset', 'relative_due_offset', 'completed_at', 'notes'])]
class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(fn (Task $task) => $task->uuid ??= (string) Str::uuid());
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /** @return BelongsTo<Wedding, $this> */
    public function wedding(): BelongsTo
    {
        return $this->belongsTo(Wedding::class);
    }

    /** @return BelongsTo<TaskCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(TaskCategory::class, 'task_category_id');
    }

    /** @return BelongsTo<TaskStatus, $this> */
    public function status(): BelongsTo
    {
        return $this->belongsTo(TaskStatus::class, 'task_status_id');
    }

    /** @return BelongsTo<WeddingMember, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(WeddingMember::class, 'assignee_membership_id');
    }

    /** @return BelongsTo<WeddingEvent, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(WeddingEvent::class, 'wedding_event_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'priority' => TaskPriority::class,
            'date_mode' => TaskDateMode::class,
            'start_date' => 'date',
            'due_date' => 'date',
            'relative_start_offset' => 'integer',
            'relative_due_offset' => 'integer',
            'completed_at' => 'datetime',
        ];
    }
}
