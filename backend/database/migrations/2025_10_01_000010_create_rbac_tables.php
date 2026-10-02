<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2 — RBAC tables (Spatie laravel-permission schema).
 * Spec §21, §26.
 */
return new class extends Migration {
    public function up(): void
    {
        $teams = config('permission.teams');

        Schema::create('roles', function (Blueprint $t) use ($teams) {
            $t->bigIncrements('id');
            $t->string('name', 191);
            $t->string('guard_name', 191)->default('web');
            $t->string('display_name', 191)->nullable();
            $t->string('description')->nullable();
            if ($teams) {
                $t->unsignedBigInteger('team_id')->nullable()->index();
            }
            $t->timestamps();
            $t->unique(['name', 'guard_name'] + ($teams ? ['team_id'] : []));
        });

        Schema::create('permissions', function (Blueprint $t) use ($teams) {
            $t->bigIncrements('id');
            $t->string('name', 191);
            $t->string('guard_name', 191)->default('web');
            $t->string('module', 64)->nullable();
            $t->string('display_name', 191)->nullable();
            $t->string('description')->nullable();
            if ($teams) {
                $t->unsignedBigInteger('team_id')->nullable()->index();
            }
            $t->timestamps();
            $t->unique(['name', 'guard_name'] + ($teams ? ['team_id'] : []));
            $t->index('module');
        });

        Schema::create('model_has_permissions', function (Blueprint $t) use ($teams) {
            $permissionId = 'permission_id';
            $t->unsignedBigInteger($permissionId);
            $t->string('model_type', 191);
            $t->unsignedBigInteger('model_id');
            $t->index([$permissionId, 'model_id', 'model_type'], 'model_has_permissions_model_id_type_index');

            if ($teams) {
                $t->unsignedBigInteger('team_id');
                $t->index(['team_id'], 'model_has_permissions_team_id_index');
                $t->primary([$permissionId, 'model_id', 'model_type', 'team_id'], 'model_has_permissions_permission_model_type_primary');
            } else {
                $t->primary([$permissionId, 'model_id', 'model_type'], 'model_has_permissions_permission_model_type_primary');
            }

            $t->foreign($permissionId)
                ->references('id')->on('permissions')
                ->onDelete('cascade');
        });

        Schema::create('model_has_roles', function (Blueprint $t) use ($teams) {
            $roleId = 'role_id';
            $t->unsignedBigInteger($roleId);
            $t->string('model_type', 191);
            $t->unsignedBigInteger('model_id');
            $t->index([$roleId, 'model_id', 'model_type'], 'model_has_roles_model_id_type_index');

            if ($teams) {
                $t->unsignedBigInteger('team_id');
                $t->index(['team_id'], 'model_has_roles_team_id_index');
                $t->primary([$roleId, 'model_id', 'model_type', 'team_id'], 'model_has_roles_role_model_type_primary');
            } else {
                $t->primary([$roleId, 'model_id', 'model_type'], 'model_has_roles_role_model_type_primary');
            }

            $t->foreign($roleId)
                ->references('id')->on('roles')
                ->onDelete('cascade');
        });

        Schema::create('role_has_permissions', function (Blueprint $t) {
            $t->unsignedBigInteger('permission_id');
            $t->unsignedBigInteger('role_id');

            $t->foreign('permission_id')
                ->references('id')->on('permissions')
                ->onDelete('cascade');
            $t->foreign('role_id')
                ->references('id')->on('roles')
                ->onDelete('cascade');

            $t->primary(['permission_id', 'role_id']);
        });

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('role_has_permissions');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('model_has_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
