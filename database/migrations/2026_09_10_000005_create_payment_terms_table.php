<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_terms', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('wedding_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('budget_item_id');
            $table->string('name');
            $table->unsignedInteger('sort_order')->default(0);
            $table->bigInteger('planned_amount');
            $table->date('due_date')->nullable();
            $table->bigInteger('actual_paid_amount')->default(0);
            $table->timestamp('paid_at')->nullable();
            $table->string('status', 20)->default('planned');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign(['wedding_id', 'budget_item_id'])->references(['wedding_id', 'id'])->on('budget_items')->cascadeOnDelete();
            $table->unique(['wedding_id', 'id']);
            $table->index(['wedding_id', 'status']);
            $table->index(['wedding_id', 'budget_item_id', 'sort_order']);
        });

        DB::statement("ALTER TABLE payment_terms ADD CONSTRAINT payment_terms_status_check CHECK (status IN ('planned', 'partially_paid', 'paid', 'cancelled'))");
        DB::statement('ALTER TABLE payment_terms ADD CONSTRAINT payment_terms_planned_amount_check CHECK (planned_amount >= 0)');
        DB::statement('ALTER TABLE payment_terms ADD CONSTRAINT payment_terms_actual_paid_amount_check CHECK (actual_paid_amount >= 0)');
        DB::statement('ALTER TABLE payment_terms ADD CONSTRAINT payment_terms_paid_at_check CHECK ((actual_paid_amount = 0 AND paid_at IS NULL) OR (actual_paid_amount > 0 AND paid_at IS NOT NULL))');
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_terms');
    }
};
