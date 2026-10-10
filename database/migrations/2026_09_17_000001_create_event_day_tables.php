<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rundown_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('wedding_id')->constrained()->cascadeOnDelete();
            $table->foreignId('wedding_event_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('notes')->nullable();
            $table->string('pic')->nullable();
            $table->string('highlights')->nullable();
            $table->string('status', 20)->default('scheduled');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['wedding_id', 'wedding_event_id', 'sort_order']);
        });

        Schema::create('playlist_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('wedding_id')->constrained()->cascadeOnDelete();
            $table->foreignId('wedding_event_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('artist')->nullable();
            $table->string('link')->nullable();
            $table->string('requested_by')->nullable();
            $table->string('moment')->nullable();
            $table->string('notes')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['wedding_id', 'wedding_event_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('playlist_items');
        Schema::dropIfExists('rundown_items');
    }
};
