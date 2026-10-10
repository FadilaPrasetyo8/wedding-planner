<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scenario_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('wedding_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('budget_scenario_id');
            $table->unsignedBigInteger('budget_category_id')->nullable();
            $table->unsignedBigInteger('wedding_event_id')->nullable();
            $table->string('name');
            $table->bigInteger('amount');
            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign(['wedding_id', 'budget_scenario_id'])->references(['wedding_id', 'id'])->on('budget_scenarios')->cascadeOnDelete();
            $table->foreign(['wedding_id', 'budget_category_id'])->references(['wedding_id', 'id'])->on('budget_categories')->nullOnDelete();
            $table->foreign(['wedding_id', 'wedding_event_id'])->references(['wedding_id', 'id'])->on('wedding_events')->nullOnDelete();
            $table->unique(['wedding_id', 'id']);
            $table->index(['wedding_id', 'budget_scenario_id', 'sort_order']);
        });

        DB::statement('ALTER TABLE scenario_items ADD CONSTRAINT scenario_items_amount_check CHECK (amount >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('scenario_items');
    }
};
