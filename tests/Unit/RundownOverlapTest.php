<?php

namespace Tests\Unit;

use App\Http\Controllers\EventDay\RundownOverlapService;
use App\Models\RundownItem;
use App\Models\Wedding;
use App\Models\WeddingEvent;
use Database\Factories\RundownItemFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RundownOverlapTest extends TestCase
{
    use RefreshDatabase;

    public function test_overlapping_ranges_flag_both_items(): void
    {
        [$wedding, $event] = $this->makeEvent();
        $service = app(RundownOverlapService::class);

        $a = $this->item($wedding, $event, '09:00', '10:00');
        $b = $this->item($wedding, $event, '09:30', '10:30');

        $this->assertTrue($service->hasOverlap($a));
        $this->assertTrue($service->hasOverlap($b));
    }

    public function test_adjacent_ranges_do_not_flag(): void
    {
        [$wedding, $event] = $this->makeEvent();
        $service = app(RundownOverlapService::class);

        $a = $this->item($wedding, $event, '09:00', '10:00');
        $this->item($wedding, $event, '10:00', '11:00');

        $this->assertFalse($service->hasOverlap($a));
    }

    public function test_ranges_in_other_events_do_not_flag(): void
    {
        [$wedding, $event] = $this->makeEvent();
        $otherEvent = WeddingEvent::factory()->create(['wedding_id' => $wedding->id, 'event_date' => '2027-06-19']);
        $service = app(RundownOverlapService::class);

        $a = $this->item($wedding, $event, '09:00', '10:00');
        $this->item($wedding, $otherEvent, '09:30', '10:30');

        $this->assertFalse($service->hasOverlap($a));
    }

    public function test_untimed_items_never_flag(): void
    {
        [$wedding, $event] = $this->makeEvent();
        $service = app(RundownOverlapService::class);

        $untimed = $this->item($wedding, $event, null, null);
        $this->item($wedding, $event, '09:00', '10:00');

        $this->assertFalse($service->hasOverlap($untimed));
    }

    public function test_contained_range_flags_outer_item(): void
    {
        [$wedding, $event] = $this->makeEvent();
        $service = app(RundownOverlapService::class);

        $outer = $this->item($wedding, $event, '09:00', '12:00');
        $this->item($wedding, $event, '10:00', '11:00');

        $this->assertTrue($service->hasOverlap($outer));
    }

    /** @return array{Wedding, WeddingEvent} */
    private function makeEvent(): array
    {
        $wedding = Wedding::factory()->create();
        $event = WeddingEvent::factory()->create(['wedding_id' => $wedding->id, 'event_date' => '2027-06-20', 'is_primary' => true]);

        return [$wedding, $event];
    }

    private function item(Wedding $wedding, WeddingEvent $event, ?string $start, ?string $end): RundownItem
    {
        /** @var RundownItem&RundownItemFactory */
        return RundownItem::factory()->create([
            'wedding_id' => $wedding->id,
            'wedding_event_id' => $event->id,
            'start_time' => $start,
            'end_time' => $end,
        ]);
    }
}
