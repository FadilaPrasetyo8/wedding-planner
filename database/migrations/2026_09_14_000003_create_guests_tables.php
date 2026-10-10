<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_sides', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('wedding_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['wedding_id', 'id']);
            $table->unique(['wedding_id', 'name']);
        });

        Schema::create('guest_groups', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('wedding_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['wedding_id', 'id']);
            $table->unique(['wedding_id', 'name']);
        });

        Schema::create('guests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('wedding_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('guest_side_id')->nullable();
            $table->unsignedBigInteger('guest_group_id')->nullable();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->integer('invitation_count')->default(1);
            $table->integer('pax_count')->default(1);
            $table->string('invitation_status', 20)->default('not_sent');
            $table->string('rsvp_status', 20)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign(['wedding_id', 'guest_side_id'])->references(['wedding_id', 'id'])->on('guest_sides')->nullOnDelete();
            $table->foreign(['wedding_id', 'guest_group_id'])->references(['wedding_id', 'id'])->on('guest_groups')->nullOnDelete();
            $table->unique(['wedding_id', 'id']);
            $table->index(['wedding_id', 'guest_side_id']);
            $table->index(['wedding_id', 'guest_group_id']);
            $table->index(['wedding_id', 'invitation_status']);
            $table->index(['wedding_id', 'name']);
        });

        DB::statement("ALTER TABLE guests ADD CONSTRAINT guests_invitation_status_check CHECK (invitation_status IN ('not_sent', 'sent', 'delivered', 'failed'))");
        DB::statement("ALTER TABLE guests ADD CONSTRAINT guests_rsvp_status_check CHECK (rsvp_status IS NULL OR rsvp_status IN ('pending', 'attending', 'not_attending', 'tentative'))");
        DB::statement('ALTER TABLE guests ADD CONSTRAINT guests_invitation_count_check CHECK (invitation_count >= 0)');
        DB::statement('ALTER TABLE guests ADD CONSTRAINT guests_pax_count_check CHECK (pax_count >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('guests');
        Schema::dropIfExists('guest_groups');
        Schema::dropIfExists('guest_sides');
    }
};
