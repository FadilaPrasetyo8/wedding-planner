<?php

namespace App\Actions\Weddings;

use App\Enums\TaskDateMode;
use App\Models\Wedding;
use App\Models\WeddingEvent;
use Illuminate\Support\Facades\DB;

class ChangePrimaryWeddingEventAction
{
    /** @param array<string, mixed> $data */
    public function update(Wedding $wedding, WeddingEvent $event, array $data): WeddingEvent
    {
        return DB::transaction(function () use ($wedding, $event, $data): WeddingEvent {
            $wedding->events()->lockForUpdate()->get();
            $becomesPrimary = ! $event->is_primary && (bool) $data['is_primary'];

            if ($becomesPrimary) {
                $wedding->events()->where('is_primary', true)->update(['is_primary' => false]);
            }

            $event->update($data);

            if ($becomesPrimary) {
                $wedding->tasks()
                    ->where('date_mode', TaskDateMode::TemplateRelative)
                    ->get()
                    ->each(function ($task) use ($event): void {
                        $task->update([
                            'start_date' => $event->event_date->copy()->addDays($task->relative_start_offset),
                            'due_date' => $event->event_date->copy()->addDays($task->relative_due_offset),
                        ]);
                    });
            }

            return $event->refresh();
        });
    }

    /** @param array<string, mixed> $data */
    public function create(Wedding $wedding, array $data): WeddingEvent
    {
        return DB::transaction(function () use ($wedding, $data): WeddingEvent {
            $wedding->events()->lockForUpdate()->get();
            if ((bool) $data['is_primary']) {
                $wedding->events()->where('is_primary', true)->update(['is_primary' => false]);
            }

            $event = $wedding->events()->create($data);

            if ((bool) $data['is_primary']) {
                $wedding->tasks()
                    ->where('date_mode', TaskDateMode::TemplateRelative)
                    ->get()
                    ->each(function ($task) use ($event): void {
                        $task->update([
                            'start_date' => $event->event_date->copy()->addDays($task->relative_start_offset),
                            'due_date' => $event->event_date->copy()->addDays($task->relative_due_offset),
                        ]);
                    });
            }

            return $event;
        });
    }
}
