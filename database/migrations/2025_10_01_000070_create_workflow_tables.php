<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase: Workflows — workflow engine tables.
 * Spec §23, §26.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('workflow_definitions', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->string('code', 64)->unique();
            $t->string('label');
            $t->json('config');
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('workflow_instances', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->string('workflow_type', 64);
            $t->string('subject_type', 191);
            $t->unsignedBigInteger('subject_id');
            $t->string('current_step', 64);
            $t->unsignedBigInteger('current_owner_user_id')->nullable();
            $t->unsignedBigInteger('created_by_user_id');
            $t->string('status', 48)->default('in_progress');
            $t->timestamp('finalized_at')->nullable();
            $t->timestamps();
            $t->softDeletes();

            $t->index(['subject_type', 'subject_id'], 'idx_wi_subject');
            $t->index('status');
            $t->index('current_owner_user_id');
            $t->index('workflow_type');
            $t->foreign('current_owner_user_id')->references('id')->on('users')->nullOnDelete();
            $t->foreign('created_by_user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('workflow_steps', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('workflow_instance_id');
            $t->integer('step_index');
            $t->string('step_name', 64);
            $t->string('actor_role', 64);
            $t->string('expected_action', 48);
            $t->timestamp('completed_at')->nullable();
            $t->unsignedBigInteger('completed_by')->nullable();

            $t->foreign('workflow_instance_id')->references('id')->on('workflow_instances')->cascadeOnDelete();
            $t->foreign('completed_by')->references('id')->on('users')->nullOnDelete();
            $t->index('workflow_instance_id');
        });

        Schema::create('workflow_actions', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('workflow_instance_id');
            $t->unsignedBigInteger('actor_user_id');
            $t->string('action', 48);
            $t->string('from_step', 64)->nullable();
            $t->string('to_step', 64)->nullable();
            $t->text('comment')->nullable();
            $t->string('ip_address', 45)->nullable();
            $t->text('user_agent')->nullable();
            $t->timestamp('created_at')->useCurrent();

            $t->foreign('workflow_instance_id')->references('id')->on('workflow_instances')->cascadeOnDelete();
            $t->foreign('actor_user_id')->references('id')->on('users')->cascadeOnDelete();
            $t->index('workflow_instance_id');
            $t->index('actor_user_id');
        });

        Schema::create('workflow_comments', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('workflow_instance_id');
            $t->unsignedBigInteger('author_user_id');
            $t->text('body');
            $t->timestamps();

            $t->foreign('workflow_instance_id')->references('id')->on('workflow_instances')->cascadeOnDelete();
            $t->foreign('author_user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('workflow_attachments', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('workflow_instance_id');
            $t->unsignedBigInteger('uploaded_by');
            $t->string('file_path');
            $t->string('file_name');
            $t->string('mime_type', 64);
            $t->unsignedBigInteger('size_bytes');
            $t->timestamp('created_at')->useCurrent();

            $t->foreign('workflow_instance_id')->references('id')->on('workflow_instances')->cascadeOnDelete();
            $t->foreign('uploaded_by')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('workflow_history', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('workflow_instance_id');
            $t->string('from_step', 64)->nullable();
            $t->string('to_step', 64)->nullable();
            $t->unsignedBigInteger('actor_user_id')->nullable();
            $t->string('action', 48);
            $t->json('metadata')->nullable();
            $t->timestamp('created_at')->useCurrent();

            $t->foreign('workflow_instance_id')->references('id')->on('workflow_instances')->cascadeOnDelete();
            $t->foreign('actor_user_id')->references('id')->on('users')->nullOnDelete();
            $t->index('workflow_instance_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_history');
        Schema::dropIfExists('workflow_attachments');
        Schema::dropIfExists('workflow_comments');
        Schema::dropIfExists('workflow_actions');
        Schema::dropIfExists('workflow_steps');
        Schema::dropIfExists('workflow_instances');
        Schema::dropIfExists('workflow_definitions');
    }
};
