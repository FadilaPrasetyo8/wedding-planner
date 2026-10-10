<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('wedding_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('budget_category_id')->nullable();
            $table->unsignedBigInteger('wedding_event_id')->nullable();
            $table->string('name');
            $table->bigInteger('estimated_amount')->default(0);
            $table->bigInteger('final_amount')->nullable();
            $table->string('status', 20)->default('draft');
            $table->text('notes')->nullable();
            $table->string('source_scenario_name')->nullable();
            $table->string('source_item_name')->nullable();
            $table->uuid('application_batch_id')->nullable();
            $table->timestamps();

            $table->foreign(['wedding_id', 'budget_category_id'])->references(['wedding_id', 'id'])->on('budget_categories')->nullOnDelete();
            $table->foreign(['wedding_id', 'wedding_event_id'])->references(['wedding_id', 'id'])->on('wedding_events')->nullOnDelete();
            $table->unique(['wedding_id', 'id']);
            $table->index(['wedding_id', 'status']);
            $table->index(['wedding_id', 'budget_category_id']);
            $table->index(['wedding_id', 'application_batch_id']);
        });

        DB::statement("ALTER TABLE budget_items ADD CONSTRAINT budget_items_status_check CHECK (status IN ('draft', 'committed', 'cancelled'))");
        DB::statement('ALTER TABLE budget_items ADD CONSTRAINT budget_items_estimated_amount_check CHECK (estimated_amount >= 0)');
        DB::statement('ALTER TABLE budget_items ADD CONSTRAINT budget_items_final_amount_check CHECK (final_amount IS NULL OR final_amount >= 0)');
        DB::statement('ALTER TABLE budget_items ADD CONSTRAINT budget_items_commit_check CHECK (status <> \'committed\' OR final_amount IS NOT NULL)');
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_items');
    }
};
