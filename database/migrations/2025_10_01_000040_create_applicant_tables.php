<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 5 — Applicant onboarding tables.
 * Spec §13–§19.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('applicants', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->string('application_code', 32)->unique();
            $t->string('full_name');
            $t->string('email', 191);
            $t->string('phone', 32);
            $t->string('national_id', 64)->nullable();
            $t->text('address')->nullable();
            $t->json('education')->nullable();
            $t->json('experience')->nullable();
            $t->json('references')->nullable();
            $t->string('profile_photo_path', 255)->nullable();
            $t->string('status', 48)->default('draft');
            $t->unsignedBigInteger('reviewed_by_team_leader_id')->nullable();
            $t->unsignedBigInteger('assigned_branch_leader_id')->nullable();
            $t->unsignedBigInteger('assigned_generation_id')->nullable();
            $t->unsignedBigInteger('assigned_branch_id')->nullable();
            $t->unsignedBigInteger('assigned_team_id')->nullable();
            $t->unsignedBigInteger('record_officer_id')->nullable();
            $t->string('generated_official_id', 32)->nullable();
            $t->text('generated_confidential_id')->nullable();
            $t->timestamp('approved_at')->nullable();
            $t->timestamp('rejected_at')->nullable();
            $t->text('rejection_reason')->nullable();
            $t->timestamp('terms_accepted_at')->nullable();
            $t->timestamps();
            $t->softDeletes();

            $t->index('status');
            $t->index('email');
            $t->index('assigned_branch_id');
            $t->index('assigned_team_id');
            $t->index('assigned_generation_id');
            $t->foreign('reviewed_by_team_leader_id')->references('id')->on('users')->onDelete('set null');
            $t->foreign('assigned_branch_leader_id')->references('id')->on('users')->onDelete('set null');
            $t->foreign('record_officer_id')->references('id')->on('users')->onDelete('set null');
            $t->foreign('assigned_generation_id')->references('id')->on('generations')->onDelete('set null');
            $t->foreign('assigned_branch_id')->references('id')->on('branches')->onDelete('set null');
            $t->foreign('assigned_team_id')->references('id')->on('teams')->onDelete('set null');
        });

        Schema::create('applicant_documents', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('applicant_id');
            $t->string('document_type', 64);
            $t->string('file_path');
            $t->string('file_name');
            $t->string('mime_type', 64);
            $t->unsignedBigInteger('size_bytes');
            $t->unsignedBigInteger('uploaded_by');
            $t->timestamps();

            $t->foreign('applicant_id')->references('id')->on('applicants')->cascadeOnDelete();
            $t->foreign('uploaded_by')->references('id')->on('users')->cascadeOnDelete();
            $t->index('applicant_id');
            $t->index('document_type');
        });

        Schema::create('applicant_reviews', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('applicant_id');
            $t->unsignedBigInteger('reviewer_user_id');
            $t->string('review_stage', 48);
            $t->string('decision', 24);
            $t->text('comment')->nullable();
            $t->timestamps();

            $t->foreign('applicant_id')->references('id')->on('applicants')->cascadeOnDelete();
            $t->foreign('reviewer_user_id')->references('id')->on('users')->cascadeOnDelete();
            $t->index('applicant_id');
            $t->index('decision');
        });

        Schema::create('applicant_assignments', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('applicant_id');
            $t->unsignedBigInteger('assigner_user_id');
            $t->unsignedBigInteger('generation_id')->nullable();
            $t->unsignedBigInteger('branch_id')->nullable();
            $t->unsignedBigInteger('team_id')->nullable();
            $t->timestamp('assigned_at')->useCurrent();

            $t->foreign('applicant_id')->references('id')->on('applicants')->cascadeOnDelete();
            $t->foreign('assigner_user_id')->references('id')->on('users')->cascadeOnDelete();
            $t->foreign('generation_id')->references('id')->on('generations')->nullOnDelete();
            $t->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
            $t->foreign('team_id')->references('id')->on('teams')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applicant_assignments');
        Schema::dropIfExists('applicant_reviews');
        Schema::dropIfExists('applicant_documents');
        Schema::dropIfExists('applicants');
    }
};
