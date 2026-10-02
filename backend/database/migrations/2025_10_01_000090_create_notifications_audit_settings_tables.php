<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2 — Notifications (Laravel default) + notification templates.
 * Phase 2 — Audit logs + System settings.
 * Spec §20, §24, §26, §35.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('type');
            $t->morphs('notifiable');
            $t->text('data');
            $t->timestamp('read_at')->nullable();
            $t->timestamp('created_at')->useCurrent();

            $t->index(['notifiable_type', 'notifiable_id'], 'idx_notifiable');
        });

        Schema::create('notification_templates', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->string('code', 64)->unique();
            $t->string('channel', 32);
            $t->string('subject', 191)->nullable();
            $t->text('body');
            $t->boolean('is_active')->default(true);
            $t->json('variables')->nullable();
            $t->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('user_id')->nullable();
            $t->string('action', 64);
            $t->string('entity_type', 191)->nullable();
            $t->unsignedBigInteger('entity_id')->nullable();
            $t->json('old_values')->nullable();
            $t->json('new_values')->nullable();
            $t->string('ip_address', 45)->nullable();
            $t->text('user_agent')->nullable();
            $t->string('category', 48)->nullable();
            $t->string('severity', 16)->default('info');
            $t->timestamp('created_at')->useCurrent();

            $t->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $t->index('user_id');
            $t->index('action');
            $t->index(['entity_type', 'entity_id'], 'idx_audit_entity');
            $t->index('category');
            $t->index('severity');
            $t->index('created_at');
        });

        Schema::create('system_settings', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->string('key', 191)->unique();
            $t->text('value')->nullable();
            $t->string('type', 24)->default('string');
            $t->text('description')->nullable();
            $t->boolean('is_encrypted')->default(false);
            $t->unsignedBigInteger('updated_by')->nullable();
            $t->timestamps();

            $t->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('notification_templates');
        Schema::dropIfExists('notifications');
    }
};
