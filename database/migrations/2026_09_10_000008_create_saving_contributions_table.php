<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saving_contributions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('wedding_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('saving_goal_id');
            $table->unsignedBigInteger('saving_contributor_id');
            $table->bigInteger('amount');
            $table->date('contributed_on')->nullable();
            $table->string('source', 40)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign(['wedding_id', 'saving_goal_id'])->references(['wedding_id', 'id'])->on('saving_goals')->cascadeOnDelete();
            $table->foreign(['wedding_id', 'saving_contributor_id'])->references(['wedding_id', 'id'])->on('saving_contributors')->cascadeOnDelete();
            $table->unique(['wedding_id', 'id']);
            $table->index(['wedding_id', 'saving_goal_id', 'contributed_on']);
            $table->index(['wedding_id', 'saving_contributor_id']);
        });

        DB::statement('ALTER TABLE saving_contributions ADD CONSTRAINT saving_contributions_amount_check CHECK (amount > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('saving_contributions');
    }
};
