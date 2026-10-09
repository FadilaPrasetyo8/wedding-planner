<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checklist_groups', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('wedding_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->string('name');
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['wedding_id', 'id']);
            $table->unique(['wedding_id', 'type', 'name']);
            $table->index(['wedding_id', 'type']);
        });

        DB::statement("ALTER TABLE checklist_groups ADD CONSTRAINT checklist_groups_type_check CHECK (type IN ('seserahan', 'administration'))");

        Schema::create('checklist_categories', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('wedding_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->string('name');
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['wedding_id', 'id']);
            $table->unique(['wedding_id', 'type', 'name']);
            $table->index(['wedding_id', 'type']);
        });

        DB::statement("ALTER TABLE checklist_categories ADD CONSTRAINT checklist_categories_type_check CHECK (type IN ('seserahan', 'administration'))");

        Schema::create('checklist_statuses', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('wedding_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->string('name');
            $table->string('color', 20)->default('slate');
            $table->boolean('is_completed')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['wedding_id', 'id']);
            $table->unique(['wedding_id', 'type', 'name']);
            $table->index(['wedding_id', 'type']);
        });

        DB::statement("ALTER TABLE checklist_statuses ADD CONSTRAINT checklist_statuses_type_check CHECK (type IN ('seserahan', 'administration'))");

        Schema::create('checklist_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('wedding_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->unsignedBigInteger('checklist_group_id')->nullable();
            $table->unsignedBigInteger('checklist_category_id')->nullable();
            $table->unsignedBigInteger('checklist_status_id')->nullable();
            $table->unsignedBigInteger('assignee_member_id')->nullable();
            $table->string('name');
            $table->text('notes')->nullable();
            $table->integer('quantity')->default(1);
            $table->bigInteger('estimated_cost')->nullable();
            $table->bigInteger('actual_cost')->nullable();
            $table->date('deadline')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('purchase_url')->nullable();
            $table->timestamps();

            $table->foreign(['wedding_id', 'checklist_group_id'])->references(['wedding_id', 'id'])->on('checklist_groups')->nullOnDelete();
            $table->foreign(['wedding_id', 'checklist_category_id'])->references(['wedding_id', 'id'])->on('checklist_categories')->nullOnDelete();
            $table->foreign(['wedding_id', 'checklist_status_id'])->references(['wedding_id', 'id'])->on('checklist_statuses')->nullOnDelete();
            $table->foreign(['wedding_id', 'assignee_member_id'])->references(['wedding_id', 'id'])->on('wedding_members')->nullOnDelete();
            $table->unique(['wedding_id', 'id']);
            $table->index(['wedding_id', 'type']);
            $table->index(['wedding_id', 'type', 'checklist_status_id']);
            $table->index(['wedding_id', 'type', 'deadline']);
        });

        DB::statement("ALTER TABLE checklist_items ADD CONSTRAINT checklist_items_type_check CHECK (type IN ('seserahan', 'administration'))");
        DB::statement('ALTER TABLE checklist_items ADD CONSTRAINT checklist_items_quantity_check CHECK (quantity >= 1)');
        DB::statement('ALTER TABLE checklist_items ADD CONSTRAINT checklist_items_estimated_cost_check CHECK (estimated_cost IS NULL OR estimated_cost >= 0)');
        DB::statement('ALTER TABLE checklist_items ADD CONSTRAINT checklist_items_actual_cost_check CHECK (actual_cost IS NULL OR actual_cost >= 0)');
        DB::statement('ALTER TABLE checklist_items ADD CONSTRAINT checklist_items_completed_check CHECK (completed_at IS NULL OR checklist_status_id IS NOT NULL)');
    }

    public function down(): void
    {
        Schema::dropIfExists('checklist_items');
        Schema::dropIfExists('checklist_statuses');
        Schema::dropIfExists('checklist_categories');
        Schema::dropIfExists('checklist_groups');
    }
};
