<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_scenarios', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('wedding_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['wedding_id', 'id']);
            $table->index(['wedding_id', 'sort_order']);
        });

        DB::statement('CREATE UNIQUE INDEX budget_scenarios_wedding_name_unique ON budget_scenarios (wedding_id, lower(name))');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS budget_scenarios_wedding_name_unique');
        Schema::dropIfExists('budget_scenarios');
    }
};
