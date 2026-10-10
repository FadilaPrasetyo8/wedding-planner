<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_field_definitions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('wedding_id')->constrained()->cascadeOnDelete();
            $table->string('module', 20);
            $table->string('field_type', 20);
            $table->string('label');
            $table->string('field_key');
            $table->boolean('is_required')->default(false);
            $table->jsonb('options')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['wedding_id', 'id']);
            $table->unique(['wedding_id', 'module', 'field_key']);
            $table->unique(['wedding_id', 'module', 'label']);
            $table->index(['wedding_id', 'module', 'sort_order']);
        });

        DB::statement("ALTER TABLE custom_field_definitions ADD CONSTRAINT custom_field_definitions_module_check CHECK (module IN ('guest', 'task', 'checklist_item', 'budget_item'))");
        DB::statement("ALTER TABLE custom_field_definitions ADD CONSTRAINT custom_field_definitions_field_type_check CHECK (field_type IN ('text', 'textarea', 'number', 'currency', 'date', 'select', 'multi_select', 'checkbox', 'url', 'file_reference'))");

        Schema::create('custom_field_values', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('wedding_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('custom_field_definition_id');
            $table->string('module', 20);
            $table->unsignedBigInteger('subject_id');
            $table->jsonb('value')->nullable();
            $table->timestamps();

            $table->foreign(['wedding_id', 'custom_field_definition_id'])->references(['wedding_id', 'id'])->on('custom_field_definitions')->cascadeOnDelete();
            $table->unique(['wedding_id', 'custom_field_definition_id', 'subject_id']);
            $table->index(['wedding_id', 'module', 'subject_id']);
        });

        DB::statement("ALTER TABLE custom_field_values ADD CONSTRAINT custom_field_values_module_check CHECK (module IN ('guest', 'task', 'checklist_item', 'budget_item'))");
        DB::statement("ALTER TABLE custom_field_values ADD CONSTRAINT custom_field_values_definition_module_check CHECK (module = 'guest' OR module = 'task' OR module = 'checklist_item' OR module = 'budget_item')");
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_field_values');
        Schema::dropIfExists('custom_field_definitions');
    }
};
