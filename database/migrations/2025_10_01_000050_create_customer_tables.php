<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 6 — Customer management tables.
 * Spec §8, §9, §26.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->string('reference_code', 32)->unique();
            $t->string('full_name');
            $t->string('email', 191)->nullable();
            $t->string('phone', 32);
            $t->string('national_id', 64)->nullable();
            $t->date('date_of_birth')->nullable();
            $t->string('customer_source', 64)->nullable();
            $t->unsignedBigInteger('sales_agent_user_id');
            $t->unsignedBigInteger('team_id');
            $t->unsignedBigInteger('branch_id');
            $t->unsignedBigInteger('generation_id');
            $t->string('status', 48)->default('draft');
            $t->text('notes')->nullable();
            $t->json('metadata')->nullable();
            $t->timestamps();
            $t->softDeletes();

            $t->foreign('sales_agent_user_id')->references('id')->on('users')->cascadeOnDelete();
            $t->foreign('team_id')->references('id')->on('teams')->cascadeOnDelete();
            $t->foreign('branch_id')->references('id')->on('branches')->cascadeOnDelete();
            $t->foreign('generation_id')->references('id')->on('generations')->cascadeOnDelete();
            $t->index('email');
            $t->index('phone');
            $t->index('national_id');
            $t->index('status');
            $t->index('team_id');
            $t->index('branch_id');
            $t->index('generation_id');
        });

        Schema::create('customer_documents', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('customer_id');
            $t->string('document_type', 64);
            $t->string('file_path');
            $t->string('file_name');
            $t->string('mime_type', 64);
            $t->unsignedBigInteger('size_bytes');
            $t->unsignedBigInteger('uploaded_by');
            $t->timestamps();

            $t->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete();
            $t->foreign('uploaded_by')->references('id')->on('users')->cascadeOnDelete();
            $t->index('customer_id');
        });

        Schema::create('customer_references', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('customer_id');
            $t->string('reference_code', 32);
            $t->timestamp('issued_at')->useCurrent();
            $t->unsignedBigInteger('issued_by');
            $t->string('note')->nullable();

            $t->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete();
            $t->foreign('issued_by')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('customer_reviews', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('customer_id');
            $t->unsignedBigInteger('reviewer_user_id');
            $t->string('review_stage', 48);
            $t->string('decision', 24);
            $t->text('comment')->nullable();
            $t->timestamps();

            $t->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete();
            $t->foreign('reviewer_user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('customer_duplicates', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('new_customer_id');
            $t->unsignedBigInteger('existing_customer_id');
            $t->string('match_field', 64);
            $t->decimal('match_score', 5, 2);
            $t->boolean('resolved')->default(false);
            $t->unsignedBigInteger('resolved_by')->nullable();
            $t->string('resolved_action', 24)->nullable();
            $t->timestamps();

            $t->foreign('new_customer_id')->references('id')->on('customers')->cascadeOnDelete();
            $t->foreign('existing_customer_id')->references('id')->on('customers')->cascadeOnDelete();
            $t->foreign('resolved_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_duplicates');
        Schema::dropIfExists('customer_reviews');
        Schema::dropIfExists('customer_references');
        Schema::dropIfExists('customer_documents');
        Schema::dropIfExists('customers');
    }
};
