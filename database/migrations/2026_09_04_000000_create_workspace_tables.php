<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weddings', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('partner_one_name');
            $table->string('partner_two_name');
            $table->date('wedding_date');
            $table->date('planning_start_date')->nullable();
            $table->string('timezone', 64)->default('Asia/Jakarta');
            $table->char('currency', 3)->default('IDR');
            $table->unsignedBigInteger('target_budget')->nullable();
            $table->string('status', 20)->default('planning');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('wedding_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wedding_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20);
            $table->timestamp('joined_at');
            $table->timestamp('left_at')->nullable();
            $table->timestamps();

            $table->unique(['wedding_id', 'user_id']);
            $table->index(['user_id', 'left_at']);
        });

        DB::statement("ALTER TABLE weddings ADD CONSTRAINT weddings_currency_check CHECK (currency = 'IDR')");
        DB::statement("ALTER TABLE weddings ADD CONSTRAINT weddings_status_check CHECK (status IN ('planning', 'completed', 'archived'))");
        DB::statement('ALTER TABLE weddings ADD CONSTRAINT weddings_date_order_check CHECK (planning_start_date IS NULL OR planning_start_date <= wedding_date)');
        DB::statement("ALTER TABLE wedding_members ADD CONSTRAINT wedding_members_role_check CHECK (role IN ('owner', 'editor', 'viewer'))");
        DB::statement("CREATE UNIQUE INDEX wedding_members_one_active_owner ON wedding_members (wedding_id) WHERE role = 'owner' AND left_at IS NULL");

        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('template_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('template_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_section_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30);
            $table->jsonb('payload');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('wedding_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('wedding_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->date('event_date');
            $table->boolean('is_primary')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['wedding_id', 'event_date']);
            $table->unique(['wedding_id', 'name', 'event_date']);
        });

        Schema::create('wedding_invitations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('wedding_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->string('role', 20);
            $table->char('token_hash', 64)->unique();
            $table->foreignId('invited_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['wedding_id', 'email']);
        });

        DB::statement("ALTER TABLE template_items ADD CONSTRAINT template_items_type_check CHECK (type IN ('event'))");
        DB::statement("ALTER TABLE wedding_invitations ADD CONSTRAINT wedding_invitations_role_check CHECK (role IN ('editor', 'viewer'))");
        DB::statement('CREATE UNIQUE INDEX wedding_invitations_one_pending_email ON wedding_invitations (wedding_id, lower(email)) WHERE accepted_at IS NULL AND declined_at IS NULL AND cancelled_at IS NULL');

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wedding_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->jsonb('changes')->nullable();
            $table->timestamps();

            $table->index(['wedding_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('wedding_invitations');
        Schema::dropIfExists('wedding_events');
        Schema::dropIfExists('template_items');
        Schema::dropIfExists('template_sections');
        Schema::dropIfExists('templates');
        Schema::dropIfExists('wedding_members');
        Schema::dropIfExists('weddings');
    }
};
