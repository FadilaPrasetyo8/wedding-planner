<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saving_goals', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('wedding_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->bigInteger('target_amount');
            $table->date('target_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['wedding_id', 'id']);
            $table->index(['wedding_id', 'is_active']);
        });

        DB::statement('ALTER TABLE saving_goals ADD CONSTRAINT saving_goals_target_amount_check CHECK (target_amount >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('saving_goals');
    }
};
