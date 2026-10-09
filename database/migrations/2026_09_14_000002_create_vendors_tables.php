<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_categories', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('wedding_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['wedding_id', 'id']);
            $table->unique(['wedding_id', 'name']);
        });

        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('wedding_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('vendor_category_id');
            $table->string('name');
            $table->string('status', 20)->default('candidate');
            $table->unsignedTinyInteger('internal_rating')->nullable();
            $table->string('address')->nullable();
            $table->string('website')->nullable();
            $table->string('social_media')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign(['wedding_id', 'vendor_category_id'])->references(['wedding_id', 'id'])->on('vendor_categories')->restrictOnDelete();
            $table->unique(['wedding_id', 'id']);
            $table->index(['wedding_id', 'status']);
            $table->index(['wedding_id', 'vendor_category_id']);
        });

        DB::statement("ALTER TABLE vendors ADD CONSTRAINT vendors_status_check CHECK (status IN ('candidate', 'contacted', 'negotiating', 'selected', 'rejected', 'done'))");
        DB::statement('ALTER TABLE vendors ADD CONSTRAINT vendors_internal_rating_check CHECK (internal_rating IS NULL OR internal_rating BETWEEN 0 AND 5)');

        Schema::create('vendor_contacts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('wedding_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('vendor_id');
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('role')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->foreign(['wedding_id', 'vendor_id'])->references(['wedding_id', 'id'])->on('vendors')->cascadeOnDelete();
            $table->unique(['wedding_id', 'id']);
            $table->index(['wedding_id', 'vendor_id']);
        });

        Schema::table('budget_items', function (Blueprint $table) {
            $table->unsignedBigInteger('vendor_id')->nullable();

            $table->foreign(['wedding_id', 'vendor_id'])->references(['wedding_id', 'id'])->on('vendors')->nullOnDelete();
            $table->index(['wedding_id', 'vendor_id']);
        });
    }

    public function down(): void
    {
        Schema::table('budget_items', function (Blueprint $table) {
            $table->dropIndex(['wedding_id', 'vendor_id']);
            $table->dropForeign(['wedding_id', 'vendor_id']);
            $table->dropColumn('vendor_id');
        });

        Schema::dropIfExists('vendor_contacts');
        Schema::dropIfExists('vendors');
        Schema::dropIfExists('vendor_categories');
    }
};
