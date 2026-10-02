<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4 — ID sequences & history (audit).
 * Spec §5.1, §6, §7.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('id_sequences', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->string('sequence_key', 64)->unique();
            $t->unsignedBigInteger('next_value')->default(1);
            $t->string('prefix', 16)->nullable();
            $t->unsignedInteger('padding')->default(6);
            $t->timestamp('last_used_at')->nullable();
            $t->timestamps();
        });

        Schema::create('id_generation_history', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->string('sequence_key', 64);
            $t->string('generated_value', 64);
            $t->string('subject_type', 191);
            $t->unsignedBigInteger('subject_id');
            $t->unsignedBigInteger('actor_user_id')->nullable();
            $t->string('ip_address', 45)->nullable();
            $t->text('user_agent')->nullable();
            $t->json('payload')->nullable();
            $t->timestamp('created_at')->useCurrent();

            $t->index(['subject_type', 'subject_id'], 'idx_idgh_subject');
            $t->index('generated_value', 'idx_idgh_value');
            $t->index('sequence_key', 'idx_idgh_key');
            $t->foreign('actor_user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('id_generation_history');
        Schema::dropIfExists('id_sequences');
    }
};
