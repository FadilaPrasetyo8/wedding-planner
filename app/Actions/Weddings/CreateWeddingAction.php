<?php

namespace App\Actions\Weddings;

use App\Enums\WeddingMemberRole;
use App\Models\Template;
use App\Models\User;
use App\Models\Wedding;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CreateWeddingAction
{
    public function __construct(
        private CloneWeddingTemplateAction $cloner,
        private SeedWeddingPlanningDefaultsAction $planningDefaults,
    ) {}

    /**
     * @param  array{name: string, partner_one_name: string, partner_two_name: string, wedding_date: string, planning_start_date?: string|null, timezone: string, target_budget?: int|null, initial_event_name: string, template_id?: int|null}  $data
     */
    public function execute(User $user, array $data): Wedding
    {
        return DB::transaction(function () use ($user, $data): Wedding {
            $wedding = Wedding::query()->create(Arr::only($data, ['name', 'partner_one_name', 'partner_two_name', 'wedding_date', 'planning_start_date', 'timezone', 'target_budget']));
            $wedding->memberships()->create(['user_id' => $user->id, 'role' => WeddingMemberRole::Owner, 'joined_at' => now()]);
            $this->planningDefaults->execute($wedding);
            $wedding->events()->create([
                'name' => $data['initial_event_name'],
                'type' => mb_strtolower($data['initial_event_name']) === 'akad' ? 'akad' : 'custom',
                'event_date' => $data['wedding_date'],
                'is_primary' => true,
                'sort_order' => 0,
            ]);
            $template = isset($data['template_id'])
                ? Template::query()->where('is_active', true)->findOrFail($data['template_id'])
                : null;
            $this->cloner->execute($wedding, $template, $data['initial_event_name']);
            $wedding->activityLogs()->create(['actor_id' => $user->id, 'action' => 'wedding.created', 'subject_type' => Wedding::class, 'subject_id' => $wedding->id]);

            return $wedding;
        });
    }
}
