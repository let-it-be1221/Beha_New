<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Critical bugfix migration — makes two columns nullable so the
 * customer + applicant workflows can actually run with real data.
 *
 * Bug 1: customers.reference_code was NOT NULL UNIQUE → CustomerController::store()
 *        couldn't create a customer (reference code is issued later by Record Officer).
 *        Fix: make it nullable. MySQL allows multiple NULLs on a UNIQUE column.
 *
 * Bug 2: workflow_instances.created_by_user_id was NOT NULL FK → users.
 *        ApplicantController::apply() (public, no auth) passed creatorId=0
 *        which violated the FK constraint.
 *        Fix: make it nullable. Public workflows have NULL creator.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $t) {
            $t->string('reference_code', 32)->nullable()->unique()->change();
        });

        Schema::table('workflow_instances', function (Blueprint $t) {
            $t->unsignedBigInteger('created_by_user_id')->nullable()->change();
        });

        // Drop and re-add the FK as SET NULL on delete
        Schema::table('workflow_instances', function (Blueprint $t) {
            $t->dropForeign(['created_by_user_id']);
            $t->foreign('created_by_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('workflow_instances', function (Blueprint $t) {
            $t->dropForeign(['created_by_user_id']);
            $t->unsignedBigInteger('created_by_user_id')->nullable(false)->change();
            $t->foreign('created_by_user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('customers', function (Blueprint $t) {
            $t->string('reference_code', 32)->nullable(false)->unique()->change();
        });
    }
};
