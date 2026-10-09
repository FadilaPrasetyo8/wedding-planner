<?php

namespace App\Actions\Finance;

use App\Models\SavingContribution;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Creates or updates a savings contribution transactionally with an audit
 * record covering controlled before/after values (D-021).
 */
class RecordSavingContributionAction
{
    /**
     * @param  array{wedding_id: int, saving_goal_id: int, saving_contributor_id: int, amount: int, contributed_on: string|null, source: string|null, notes: string|null}  $data
     */
    public function create(User $actor, array $data): SavingContribution
    {
        return DB::transaction(function () use ($actor, $data): SavingContribution {
            $contribution = SavingContribution::query()->create($data);

            $contribution->wedding->activityLogs()->create([
                'actor_id' => $actor->id,
                'action' => 'savings.contribution.created',
                'subject_type' => SavingContribution::class,
                'subject_id' => $contribution->id,
                'changes' => ['amount' => $contribution->amount, 'goal_id' => $contribution->saving_goal_id, 'contributor_id' => $contribution->saving_contributor_id],
            ]);

            return $contribution;
        });
    }

    /**
     * @param  array{amount: int, contributed_on: string|null, source: string|null, notes: string|null}  $data
     */
    public function update(User $actor, SavingContribution $contribution, array $data): SavingContribution
    {
        return DB::transaction(function () use ($actor, $contribution, $data): SavingContribution {
            $before = ['amount' => $contribution->amount, 'contributed_on' => $contribution->contributed_on?->toDateString()];

            $contribution->update($data);

            $contribution->wedding->activityLogs()->create([
                'actor_id' => $actor->id,
                'action' => 'savings.contribution.updated',
                'subject_type' => SavingContribution::class,
                'subject_id' => $contribution->id,
                'changes' => ['before' => $before, 'after' => ['amount' => $contribution->amount, 'contributed_on' => $contribution->contributed_on?->toDateString()]],
            ]);

            return $contribution;
        });
    }

    public function delete(User $actor, SavingContribution $contribution): void
    {
        DB::transaction(function () use ($actor, $contribution): void {
            $contribution->wedding->activityLogs()->create([
                'actor_id' => $actor->id,
                'action' => 'savings.contribution.deleted',
                'subject_type' => SavingContribution::class,
                'subject_id' => $contribution->id,
                'changes' => ['amount' => $contribution->amount, 'goal_id' => $contribution->saving_goal_id, 'contributor_id' => $contribution->saving_contributor_id],
            ]);

            $contribution->delete();
        });
    }
}
