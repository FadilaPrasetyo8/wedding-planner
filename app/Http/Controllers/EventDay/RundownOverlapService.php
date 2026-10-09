<?php

namespace App\Http\Controllers\EventDay;

use App\Models\RundownItem;
use App\Models\Wedding;
use Illuminate\Support\Collection;

/**
 * Computes overlap warnings between rundown items of one event. Overlapping
 * time ranges are a warning, never a blocker (PRD FR-13).
 */
class RundownOverlapService
{
    /**
     * @param  Collection<int, RundownItem>  $items
     * @return array<string, bool> uuid => has overlap with any other item
     */
    public function overlaps(Wedding $wedding, Collection $items): array
    {
        $timed = $items
            ->filter(fn (RundownItem $item): bool => $item->start_time !== null && $item->end_time !== null)
            ->values();

        $result = [];

        foreach ($timed as $item) {
            $result[$item->uuid] = $timed->contains(
                fn (RundownItem $other): bool => $other->uuid !== $item->uuid
                    && $other->start_time !== null
                    && $other->end_time !== null
                    && $item->start_time < $other->end_time
                    && $other->start_time < $item->end_time,
            );
        }

        return $result;
    }

    public function hasOverlap(RundownItem $item): bool
    {
        $siblings = $item->wedding->rundownItems()
            ->where('wedding_event_id', $item->wedding_event_id)
            ->where('id', '!=', $item->id)
            ->whereNotNull('start_time')
            ->whereNotNull('end_time')
            ->get(['id', 'uuid', 'start_time', 'end_time']);

        if ($item->start_time === null || $item->end_time === null) {
            return false;
        }

        return $siblings->contains(
            fn (RundownItem $other): bool => $other->start_time !== null
                && $other->end_time !== null
                && $item->start_time < $other->end_time
                && $other->start_time < $item->end_time,
        );
    }
}
