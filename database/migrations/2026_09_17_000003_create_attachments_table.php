<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The tasks table predates the composite-FK convention; attachments needs
        // (wedding_id, id) uniqueness on every subject table to reference it safely.
        Schema::table('tasks', function (Blueprint $table) {
            $table->unique(['wedding_id', 'id']);
        });

        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('wedding_id')->constrained()->cascadeOnDelete();
            $table->string('module', 20);
            $table->unsignedBigInteger('budget_item_id')->nullable();
            $table->unsignedBigInteger('payment_term_id')->nullable();
            $table->unsignedBigInteger('checklist_item_id')->nullable();
            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->unsignedBigInteger('guest_id')->nullable();
            $table->unsignedBigInteger('task_id')->nullable();
            $table->unsignedBigInteger('uploaded_by_member_id')->nullable();
            $table->string('disk', 32);
            $table->string('path', 512);
            $table->string('filename_original', 500);
            $table->string('filename_stored');
            $table->string('mime_type', 255);
            $table->string('extension', 20);
            $table->unsignedBigInteger('size_bytes');
            $table->timestamps();

            $table->foreign(['wedding_id', 'budget_item_id'])->references(['wedding_id', 'id'])->on('budget_items')->nullOnDelete();
            $table->foreign(['wedding_id', 'payment_term_id'])->references(['wedding_id', 'id'])->on('payment_terms')->nullOnDelete();
            $table->foreign(['wedding_id', 'checklist_item_id'])->references(['wedding_id', 'id'])->on('checklist_items')->nullOnDelete();
            $table->foreign(['wedding_id', 'vendor_id'])->references(['wedding_id', 'id'])->on('vendors')->nullOnDelete();
            $table->foreign(['wedding_id', 'guest_id'])->references(['wedding_id', 'id'])->on('guests')->nullOnDelete();
            $table->foreign(['wedding_id', 'task_id'])->references(['wedding_id', 'id'])->on('tasks')->nullOnDelete();
            $table->foreign(['wedding_id', 'uploaded_by_member_id'])->references(['wedding_id', 'id'])->on('wedding_members')->nullOnDelete();

            $table->index(['wedding_id', 'module', 'budget_item_id']);
            $table->index(['wedding_id', 'module', 'payment_term_id']);
            $table->index(['wedding_id', 'module', 'checklist_item_id']);
            $table->index(['wedding_id', 'module', 'vendor_id']);
            $table->index(['wedding_id', 'module', 'guest_id']);
            $table->index(['wedding_id', 'module', 'task_id']);
        });

        DB::statement('CREATE INDEX attachments_subject_wedding_idx ON attachments (wedding_id, COALESCE(budget_item_id, payment_term_id, checklist_item_id, vendor_id, guest_id, task_id))');
        DB::statement('ALTER TABLE attachments ADD CONSTRAINT attachments_module_check CHECK (module = \'budget_item\' OR module = \'payment_term\' OR module = \'checklist_item\' OR module = \'vendor\' OR module = \'guest\' OR module = \'task\')');
        DB::statement('ALTER TABLE attachments ADD CONSTRAINT attachments_size_bytes_check CHECK (size_bytes > 0)');
        DB::statement('ALTER TABLE attachments ADD CONSTRAINT attachments_one_subject_check CHECK (((budget_item_id IS NOT NULL)::int + (payment_term_id IS NOT NULL)::int + (checklist_item_id IS NOT NULL)::int + (vendor_id IS NOT NULL)::int + (guest_id IS NOT NULL)::int + (task_id IS NOT NULL)::int) = 1)');
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropUnique(['wedding_id', 'id']);
        });
    }
};
