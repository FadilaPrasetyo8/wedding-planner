<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saving_contributors', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('wedding_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('wedding_member_id')->nullable();
            $table->string('label');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign(['wedding_id', 'wedding_member_id'])->references(['wedding_id', 'id'])->on('wedding_members')->nullOnDelete();
            $table->unique(['wedding_id', 'id']);
            $table->index(['wedding_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saving_contributors');
    }
};
