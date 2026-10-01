<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 8 — Evaluation engine + Phase 9 — Performance tables.
 * Spec §27, §28, §7, §26.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('evaluation_templates', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->string('code', 64)->unique();
            $t->string('name');
            $t->unsignedTinyInteger('target_level')->nullable();
            $t->decimal('passing_score', 5, 2)->default(60.00);
            $t->boolean('is_active')->default(true);
            $t->json('metadata')->nullable();
            $t->timestamps();
            $t->softDeletes();

            $t->index('target_level');
        });

        Schema::create('evaluation_criteria', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('template_id');
            $t->string('code', 64);
            $t->string('label');
            $t->decimal('weight', 5, 2);
            $t->decimal('min_score', 5, 2)->default(0.00);
            $t->decimal('max_score', 5, 2)->default(100.00);
            $t->decimal('passing_score', 5, 2)->default(60.00);
            $t->json('required_evidence')->nullable();
            $t->integer('sort_order')->default(0);
            $t->timestamps();

            $t->foreign('template_id')->references('id')->on('evaluation_templates')->cascadeOnDelete();
            $t->unique(['template_id', 'code'], 'uq_ec_template_code');
        });

        Schema::create('evaluations', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('template_id');
            $t->unsignedBigInteger('team_member_id');
            $t->unsignedBigInteger('subject_user_id');
            $t->unsignedBigInteger('evaluator_user_id');
            $t->decimal('total_score', 6, 2)->default(0);
            $t->decimal('weighted_score', 6, 2)->default(0);
            $t->string('decision', 24)->default('pending');
            $t->date('evaluation_period_start');
            $t->date('evaluation_period_end');
            $t->text('notes')->nullable();
            $t->timestamp('finalized_at')->nullable();
            $t->timestamps();
            $t->softDeletes();

            $t->foreign('template_id')->references('id')->on('evaluation_templates')->restrictOnDelete();
            $t->foreign('team_member_id')->references('id')->on('team_members')->cascadeOnDelete();
            $t->foreign('subject_user_id')->references('id')->on('users')->cascadeOnDelete();
            $t->foreign('evaluator_user_id')->references('id')->on('users')->cascadeOnDelete();
            $t->index('subject_user_id');
            $t->index('evaluator_user_id');
            $t->index('template_id');
            $t->index('decision');
        });

        Schema::create('evaluation_scores', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('evaluation_id');
            $t->unsignedBigInteger('criterion_id');
            $t->decimal('score', 6, 2);
            $t->text('comment')->nullable();
            $t->timestamps();

            $t->foreign('evaluation_id')->references('id')->on('evaluations')->cascadeOnDelete();
            $t->foreign('criterion_id')->references('id')->on('evaluation_criteria')->cascadeOnDelete();
            $t->unique(['evaluation_id', 'criterion_id'], 'uq_es_eval_criterion');
        });

        Schema::create('evaluation_evidence', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('evaluation_id');
            $t->unsignedBigInteger('criterion_id');
            $t->string('file_path');
            $t->string('file_name');
            $t->string('mime_type', 64);
            $t->unsignedBigInteger('size_bytes');
            $t->unsignedBigInteger('uploaded_by');
            $t->timestamp('created_at')->useCurrent();

            $t->foreign('evaluation_id')->references('id')->on('evaluations')->cascadeOnDelete();
            $t->foreign('criterion_id')->references('id')->on('evaluation_criteria')->cascadeOnDelete();
            $t->foreign('uploaded_by')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('level_promotions', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('team_member_id');
            $t->unsignedBigInteger('evaluation_id')->nullable();
            $t->unsignedTinyInteger('from_level');
            $t->unsignedTinyInteger('to_level');
            $t->string('decision', 24);
            $t->unsignedBigInteger('approved_by')->nullable();
            $t->text('notes')->nullable();
            $t->timestamp('created_at')->useCurrent();

            $t->foreign('team_member_id')->references('id')->on('team_members')->cascadeOnDelete();
            $t->foreign('evaluation_id')->references('id')->on('evaluations')->nullOnDelete();
            $t->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $t->index('team_member_id');
            $t->index('decision');
        });

        Schema::create('performance_records', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('team_member_id');
            $t->date('period_start');
            $t->date('period_end');
            $t->string('metric', 64);
            $t->decimal('value', 12, 2);
            $t->timestamp('recorded_at')->useCurrent();

            $t->foreign('team_member_id')->references('id')->on('team_members')->cascadeOnDelete();
            $t->index('team_member_id');
            $t->index('metric');
            $t->index(['period_start', 'period_end'], 'idx_pr_period');
        });

        Schema::create('performance_rankings', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('team_member_id')->unique();
            $t->integer('rank_in_team')->nullable();
            $t->integer('rank_in_branch')->nullable();
            $t->integer('rank_in_generation')->nullable();
            $t->decimal('overall_score', 8, 2)->default(0);
            $t->timestamp('recalculated_at')->nullable();

            $t->foreign('team_member_id')->references('id')->on('team_members')->cascadeOnDelete();
        });

        Schema::create('performance_ranking_history', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('team_member_id');
            $t->integer('rank_in_team')->nullable();
            $t->integer('rank_in_branch')->nullable();
            $t->integer('rank_in_generation')->nullable();
            $t->decimal('overall_score', 8, 2);
            $t->string('reason', 64)->nullable();
            $t->timestamp('recorded_at')->useCurrent();

            $t->foreign('team_member_id')->references('id')->on('team_members')->cascadeOnDelete();
            $t->index('team_member_id');
            $t->index('recorded_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_ranking_history');
        Schema::dropIfExists('performance_rankings');
        Schema::dropIfExists('performance_records');
        Schema::dropIfExists('level_promotions');
        Schema::dropIfExists('evaluation_evidence');
        Schema::dropIfExists('evaluation_scores');
        Schema::dropIfExists('evaluations');
        Schema::dropIfExists('evaluation_criteria');
        Schema::dropIfExists('evaluation_templates');
    }
};
