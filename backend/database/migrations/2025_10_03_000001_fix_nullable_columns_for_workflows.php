<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

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
 *
 * NOTE: The original migrations already created the UNIQUE index on
 * customers.reference_code. The previous version of this migration tried
 * to add another UNIQUE index with the same name → "Duplicate key name"
 * error (MySQL error 1061). This version drops the existing index first,
 * changes the column to nullable, then re-adds the UNIQUE index.
 */
return new class extends Migration {
    public function up(): void
    {
        // ─── Fix 1: customers.reference_code → nullable ─────────────────
        // Drop the existing UNIQUE index first (if it exists), then change
        // the column to nullable, then re-add the UNIQUE index (MySQL
        // allows multiple NULLs on a UNIQUE column).
        $indexExists = DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', 'customers')
            ->where('index_name', 'customers_reference_code_unique')
            ->exists();

        if ($indexExists) {
            Schema::table('customers', function (Blueprint $t) {
                $t->dropUnique('customers_reference_code_unique');
            });
        }

        Schema::table('customers', function (Blueprint $t) {
            // Change column to nullable (keeps the VARCHAR(32) type)
            $t->string('reference_code', 32)->nullable()->change();
            // Re-add the UNIQUE index (now allows multiple NULLs)
            $t->unique('reference_code', 'customers_reference_code_unique');
        });

        // ─── Fix 2: workflow_instances.created_by_user_id → nullable ────
        // Drop the existing FK (cascade on delete), change column to nullable,
        // re-add the FK with SET NULL on delete.
        Schema::table('workflow_instances', function (Blueprint $t) {
            // Drop FK if it exists (Laravel's dropForeign with array syntax)
            $t->dropForeign(['created_by_user_id']);
        });

        Schema::table('workflow_instances', function (Blueprint $t) {
            $t->unsignedBigInteger('created_by_user_id')->nullable()->change();
        });

        Schema::table('workflow_instances', function (Blueprint $t) {
            $t->foreign('created_by_user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        // Revert workflow_instances.created_by_user_id to NOT NULL
        Schema::table('workflow_instances', function (Blueprint $t) {
            $t->dropForeign(['created_by_user_id']);
        });

        Schema::table('workflow_instances', function (Blueprint $t) {
            $t->unsignedBigInteger('created_by_user_id')->nullable(false)->change();
        });

        Schema::table('workflow_instances', function (Blueprint $t) {
            $t->foreign('created_by_user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });

        // Revert customers.reference_code to NOT NULL
        Schema::table('customers', function (Blueprint $t) {
            $t->dropUnique('customers_reference_code_unique');
        });

        Schema::table('customers', function (Blueprint $t) {
            $t->string('reference_code', 32)->nullable(false)->change();
            $t->unique('reference_code', 'customers_reference_code_unique');
        });
    }
};
