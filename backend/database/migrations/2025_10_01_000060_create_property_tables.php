<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 7 — Property management tables.
 * Spec §10–§12, §26.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->string('asset_code', 32)->nullable()->unique();
            $t->string('name');
            $t->string('property_type', 64);
            $t->string('country', 64)->nullable();
            $t->string('region', 120)->nullable();
            $t->string('city', 120)->nullable();
            $t->string('address', 255)->nullable();
            $t->decimal('gps_lat', 10, 8)->nullable();
            $t->decimal('gps_lng', 11, 8)->nullable();
            $t->decimal('size_sqm', 10, 2)->nullable();
            $t->integer('number_of_units')->default(1);
            $t->integer('bedrooms')->nullable();
            $t->integer('bathrooms')->nullable();
            $t->integer('floor')->nullable();
            $t->string('building_info', 191)->nullable();
            $t->string('developer', 191)->nullable();
            $t->string('ownership_type', 48)->nullable();
            $t->decimal('price', 14, 2);
            $t->text('description')->nullable();
            $t->json('amenities')->nullable();
            $t->string('status', 48)->default('draft');
            $t->boolean('is_published')->default(false);
            $t->timestamp('published_at')->nullable();
            $t->unsignedBigInteger('registered_by_user_id');
            $t->unsignedBigInteger('generation_id')->nullable();
            $t->timestamps();
            $t->softDeletes();

            $t->foreign('registered_by_user_id')->references('id')->on('users')->cascadeOnDelete();
            $t->foreign('generation_id')->references('id')->on('generations')->nullOnDelete();
            $t->index('status');
            $t->index('is_published');
            $t->index('property_type');
            $t->index('city');
            $t->index('price');
        });

        Schema::create('property_documents', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('property_id');
            $t->string('document_type', 64);
            $t->string('file_path');
            $t->string('file_name');
            $t->string('mime_type', 64);
            $t->unsignedBigInteger('size_bytes');
            $t->unsignedBigInteger('uploaded_by');
            $t->timestamps();

            $t->foreign('property_id')->references('id')->on('properties')->cascadeOnDelete();
            $t->foreign('uploaded_by')->references('id')->on('users')->cascadeOnDelete();
            $t->index('property_id');
        });

        Schema::create('property_images', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('property_id');
            $t->string('file_path');
            $t->string('caption')->nullable();
            $t->boolean('is_primary')->default(false);
            $t->integer('sort_order')->default(0);
            $t->timestamps();

            $t->foreign('property_id')->references('id')->on('properties')->cascadeOnDelete();
            $t->index('property_id');
            $t->index('is_primary');
        });

        Schema::create('property_verifications', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('property_id');
            $t->unsignedBigInteger('verifier_user_id');
            $t->string('decision', 24);
            $t->text('comment')->nullable();
            $t->timestamps();

            $t->foreign('property_id')->references('id')->on('properties')->cascadeOnDelete();
            $t->foreign('verifier_user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('property_assets', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('property_id')->unique();
            $t->string('asset_code', 32)->unique();
            $t->unsignedBigInteger('assigned_by');
            $t->timestamp('assigned_at')->useCurrent();

            $t->foreign('property_id')->references('id')->on('properties')->cascadeOnDelete();
            $t->foreign('assigned_by')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('property_publications', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('property_id')->unique();
            $t->unsignedBigInteger('published_by');
            $t->timestamp('published_at')->useCurrent();
            $t->unsignedBigInteger('unpublished_by')->nullable();
            $t->timestamp('unpublished_at')->nullable();

            $t->foreign('property_id')->references('id')->on('properties')->cascadeOnDelete();
            $t->foreign('published_by')->references('id')->on('users')->cascadeOnDelete();
            $t->foreign('unpublished_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_publications');
        Schema::dropIfExists('property_assets');
        Schema::dropIfExists('property_verifications');
        Schema::dropIfExists('property_images');
        Schema::dropIfExists('property_documents');
        Schema::dropIfExists('properties');
    }
};
