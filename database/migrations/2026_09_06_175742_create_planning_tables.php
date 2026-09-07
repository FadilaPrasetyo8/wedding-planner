<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wedding_members', function (Blueprint $table) {
            $table->unique(['wedding_id', 'id']);
        });

        Schema::table('wedding_events', function (Blueprint $table) {
            $table->string('type', 20)->default('custom')->after('name');
            $table->time('starts_at')->nullable()->after('event_date');
            $table->time('ends_at')->nullable()->after('starts_at');
            $table->string('venue')->nullable()->after('ends_at');
            $table->text('notes')->nullable()->after('venue');
            $table->unique(['wedding_id', 'id']);
        });

        DB::statement("ALTER TABLE wedding_events ADD CONSTRAINT wedding_events_type_check CHECK (type IN ('engagement', 'akad', 'reception', 'custom'))");
        DB::statement('ALTER TABLE wedding_events ADD CONSTRAINT wedding_events_time_order_check CHECK (starts_at IS NULL OR ends_at IS NULL OR ends_at >= starts_at)');
        DB::statement('CREATE UNIQUE INDEX wedding_events_one_primary ON wedding_events (wedding_id) WHERE is_primary = true');

        Schema::create('task_categories', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('wedding_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('color', 20);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['wedding_id', 'id']);
            $table->index(['wedding_id', 'sort_order']);
        });

        Schema::create('task_statuses', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('wedding_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('color', 20);
            $table->string('behavior', 20);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['wedding_id', 'id']);
            $table->unique(['wedding_id', 'behavior']);
            $table->index(['wedding_id', 'sort_order']);
        });

        DB::statement('CREATE UNIQUE INDEX task_categories_wedding_name_unique ON task_categories (wedding_id, lower(name))');
        DB::statement('CREATE UNIQUE INDEX task_statuses_wedding_name_unique ON task_statuses (wedding_id, lower(name))');
        DB::statement("ALTER TABLE task_statuses ADD CONSTRAINT task_statuses_behavior_check CHECK (behavior IN ('todo', 'in_progress', 'completed', 'cancelled'))");

        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('wedding_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('task_category_id');
            $table->unsignedBigInteger('task_status_id');
            $table->string('priority', 20)->default('medium');
            $table->unsignedBigInteger('assignee_membership_id')->nullable();
            $table->unsignedBigInteger('wedding_event_id')->nullable();
            $table->string('date_mode', 24)->default('manual');
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->integer('relative_start_offset')->nullable();
            $table->integer('relative_due_offset')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign(['wedding_id', 'task_category_id'])->references(['wedding_id', 'id'])->on('task_categories')->restrictOnDelete();
            $table->foreign(['wedding_id', 'task_status_id'])->references(['wedding_id', 'id'])->on('task_statuses')->restrictOnDelete();
            $table->foreign(['wedding_id', 'assignee_membership_id'])->references(['wedding_id', 'id'])->on('wedding_members')->restrictOnDelete();
            $table->foreign(['wedding_id', 'wedding_event_id'])->references(['wedding_id', 'id'])->on('wedding_events')->restrictOnDelete();
            $table->index(['wedding_id', 'task_status_id']);
            $table->index(['wedding_id', 'task_category_id']);
            $table->index(['wedding_id', 'due_date', 'id']);
            $table->index(['wedding_id', 'start_date', 'id']);
            $table->index(['wedding_id', 'assignee_membership_id']);
            $table->index(['wedding_id', 'wedding_event_id']);
        });

        DB::statement("ALTER TABLE tasks ADD CONSTRAINT tasks_priority_check CHECK (priority IN ('low', 'medium', 'high', 'urgent'))");
        DB::statement("ALTER TABLE tasks ADD CONSTRAINT tasks_date_mode_check CHECK (date_mode IN ('manual', 'template_relative'))");
        DB::statement('ALTER TABLE tasks ADD CONSTRAINT tasks_date_order_check CHECK (start_date IS NULL OR due_date IS NULL OR due_date >= start_date)');
        DB::statement("ALTER TABLE tasks ADD CONSTRAINT tasks_offsets_check CHECK ((date_mode = 'manual' AND relative_start_offset IS NULL AND relative_due_offset IS NULL) OR (date_mode = 'template_relative' AND relative_start_offset IS NOT NULL AND relative_due_offset IS NOT NULL))");

        DB::statement('ALTER TABLE template_items DROP CONSTRAINT template_items_type_check');
        DB::statement("ALTER TABLE template_items ADD CONSTRAINT template_items_type_check CHECK (type IN ('event', 'task'))");

        $now = now();
        $categories = [
            ['name' => 'Persiapan Utama', 'color' => 'burgundy', 'sort_order' => 10],
            ['name' => 'Dokumen & Administrasi', 'color' => 'clay', 'sort_order' => 20],
            ['name' => 'Vendor & Lokasi', 'color' => 'rose', 'sort_order' => 30],
            ['name' => 'Koordinasi Keluarga', 'color' => 'sage', 'sort_order' => 40],
        ];
        $statuses = [
            ['name' => 'Belum Mulai', 'color' => 'slate', 'behavior' => 'todo', 'sort_order' => 10],
            ['name' => 'Proses', 'color' => 'clay', 'behavior' => 'in_progress', 'sort_order' => 20],
            ['name' => 'Selesai', 'color' => 'sage', 'behavior' => 'completed', 'sort_order' => 30],
            ['name' => 'Dibatalkan', 'color' => 'rose', 'behavior' => 'cancelled', 'sort_order' => 40],
        ];

        foreach (DB::table('weddings')->pluck('id') as $weddingId) {
            foreach ($categories as $category) {
                DB::table('task_categories')->insert([...$category, 'uuid' => (string) Str::uuid(), 'wedding_id' => $weddingId, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
            }
            foreach ($statuses as $status) {
                DB::table('task_statuses')->insert([...$status, 'uuid' => (string) Str::uuid(), 'wedding_id' => $weddingId, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
            }
        }
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE template_items DROP CONSTRAINT template_items_type_check');
        DB::statement("ALTER TABLE template_items ADD CONSTRAINT template_items_type_check CHECK (type IN ('event'))");

        Schema::dropIfExists('tasks');
        Schema::dropIfExists('task_statuses');
        Schema::dropIfExists('task_categories');

        DB::statement('DROP INDEX IF EXISTS wedding_events_one_primary');
        DB::statement('ALTER TABLE wedding_events DROP CONSTRAINT IF EXISTS wedding_events_time_order_check');
        DB::statement('ALTER TABLE wedding_events DROP CONSTRAINT IF EXISTS wedding_events_type_check');
        Schema::table('wedding_events', function (Blueprint $table) {
            $table->dropUnique(['wedding_id', 'id']);
            $table->dropColumn(['type', 'starts_at', 'ends_at', 'venue', 'notes']);
        });
        Schema::table('wedding_members', function (Blueprint $table) {
            $table->dropUnique(['wedding_id', 'id']);
        });
    }
};
