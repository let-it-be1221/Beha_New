<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2 — Laravel base tables: users, password_reset_tokens, sessions.
 * (Spec §5, §26)
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->string('official_id', 32)->unique();
            $t->text('confidential_id')->nullable();           // encrypted at rest (App\Models\User cast)
            $t->string('username', 64)->unique();
            $t->string('email', 191)->unique();
            $t->timestamp('email_verified_at')->nullable();
            $t->string('password');
            $t->boolean('must_change_password')->default(false);
            $t->boolean('is_active')->default(true);
            $t->unsignedInteger('failed_login_count')->default(0);
            $t->timestamp('locked_until')->nullable();
            $t->unsignedBigInteger('current_team_id')->nullable();
            $t->unsignedTinyInteger('level')->default(1);
            $t->timestamp('last_login_at')->nullable();
            $t->string('last_login_ip', 45)->nullable();
            $t->rememberToken();
            $t->text('two_factor_secret')->nullable();
            $t->string('profile_photo_path', 255)->nullable();
            $t->timestamps();
            $t->softDeletes();

            $t->index('email');
            $t->index('official_id');
            $t->index('current_team_id');
            $t->index('level');
            $t->index('is_active');
        });

        Schema::create('password_reset_tokens', function (Blueprint $t) {
            $t->string('email', 191)->primary();
            $t->string('token');
            $t->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $t) {
            $t->string('id', 191)->primary();
            $t->unsignedBigInteger('user_id')->nullable()->index();
            $t->string('ip_address', 45)->nullable();
            $t->text('user_agent')->nullable();
            $t->longText('payload');
            $t->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
