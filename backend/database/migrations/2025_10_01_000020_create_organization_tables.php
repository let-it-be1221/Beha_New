<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3 — Organization hierarchy: generations, branches, teams, team_members, user_levels.
 * Spec §4, §16, §26.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('generations', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->string('name', 120);
            $t->integer('generation_number')->unique();
            $t->unsignedBigInteger('leader_user_id')->nullable();
            $t->text('description')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->softDeletes();

            $t->index('leader_user_id');
            $t->index('is_active');
        });

        Schema::create('branches', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('generation_id');
            $t->string('name', 120);
            $t->integer('branch_number');
            $t->unsignedBigInteger('leader_user_id')->nullable();
            $t->text('description')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->softDeletes();

            $t->unique(['generation_id', 'branch_number'], 'uq_branch_number_per_gen');
            $t->foreign('generation_id')->references('id')->on('generations');
            $t->index('leader_user_id');
            $t->index('is_active');
        });

        Schema::create('teams', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('branch_id');
            $t->string('name', 120);
            $t->integer('team_number');
            $t->unsignedBigInteger('leader_user_id')->nullable();
            $t->text('description')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->softDeletes();

            $t->unique(['branch_id', 'team_number'], 'uq_team_number_per_branch');
            $t->foreign('branch_id')->references('id')->on('branches');
            $t->index('leader_user_id');
            $t->index('is_active');
        });

        Schema::create('team_members', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('user_id')->unique();
            $t->unsignedBigInteger('team_id');
            $t->unsignedTinyInteger('level')->default(1);
            $t->date('joined_at');
            $t->date('promoted_at')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->softDeletes();

            $t->foreign('user_id')->references('id')->on('users');
            $t->foreign('team_id')->references('id')->on('teams');
            $t->index('team_id');
            $t->index('level');
            $t->index('is_active');
        });

        Schema::create('user_levels', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedTinyInteger('code')->unique();
            $t->string('name', 64);
            $t->text('description')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_levels');
        Schema::dropIfExists('team_members');
        Schema::dropIfExists('teams');
        Schema::dropIfExists('branches');
        Schema::dropIfExists('generations');
    }
};
