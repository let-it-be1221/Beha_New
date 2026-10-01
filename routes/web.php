<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ForcePasswordChangeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PublicPropertyController;
use App\Http\Controllers\Applicant\PublicApplicantController;
use Illuminate\Support\Facades\Route;

/**
 * Beha — Web routes (Blade + Livewire).
 * Spec §22, §31.
 */

// ─── Public ────────────────────────────────────────────────────────────
Route::get('/', fn () => view('welcome'))->name('home');
Route::get('/properties', [PublicPropertyController::class, 'index'])->name('public.properties.index');
Route::get('/properties/{property}', [PublicPropertyController::class, 'show'])->name('public.properties.show');

// ─── Auth ───────────────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('login',  [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [LoginController::class, 'login']);
});
Route::post('logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// Public applicant self-signup (spec §14)
Route::get('/apply',  [PublicApplicantController::class, 'create'])->name('applicants.apply');
Route::post('/apply', [PublicApplicantController::class, 'store'])->name('applicants.apply.store');

// ─── Authenticated area ─────────────────────────────────────────────────
Route::middleware(['auth', 'first.login'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Customer routes (spec §8–§9)
    Route::prefix('customers')->name('customers.')->group(function () {
        Route::get('/',     [\App\Http\Controllers\Customer\CustomerController::class, 'index'])->name('index');
        Route::get('/create',[\App\Http\Controllers\Customer\CustomerController::class, 'create'])->name('create');
        Route::post('/',    [\App\Http\Controllers\Customer\CustomerController::class, 'store'])->name('store');
        Route::get('/{customer}',[\App\Http\Controllers\Customer\CustomerController::class, 'show'])->name('show');
        Route::get('/{customer}/edit', [\App\Http\Controllers\Customer\CustomerController::class, 'edit'])->name('edit');
    });

    // Property routes (spec §10–§12)
    Route::prefix('properties/admin')->name('properties.')->group(function () {
        Route::get('/',       [\App\Http\Controllers\Property\PropertyController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Property\PropertyController::class, 'create'])->name('create');
        Route::post('/',      [\App\Http\Controllers\Property\PropertyController::class, 'store'])->name('store');
        Route::get('/{property}', [\App\Http\Controllers\Property\PropertyController::class, 'show'])->name('show');
    });

    // Applicants (spec §13–§19)
    Route::prefix('applicants')->name('applicants.')->group(function () {
        Route::get('/',     [\App\Http\Controllers\Applicant\ApplicantController::class, 'index'])->name('index');
        Route::get('/{applicant}', [\App\Http\Controllers\Applicant\ApplicantController::class, 'show'])->name('show');
    });

    // Workflows (spec §23)
    Route::prefix('workflows')->name('workflows.')->group(function () {
        Route::get('/',                [\App\Http\Controllers\Workflow\WorkflowController::class, 'index'])->name('index');
        Route::get('/{instance}',      [\App\Http\Controllers\Workflow\WorkflowController::class, 'show'])->name('show');
        Route::post('/{instance}/advance',  [\App\Http\Controllers\Workflow\WorkflowController::class, 'advance'])->name('advance');
        Route::post('/{instance}/reject',   [\App\Http\Controllers\Workflow\WorkflowController::class, 'reject'])->name('reject');
    });

    // Organization (spec §4)
    Route::prefix('organization')->name('organization.')->group(function () {
        Route::get('/generations', [\App\Http\Controllers\Organization\OrganizationController::class, 'generations'])->name('generations');
        Route::get('/branches',    [\App\Http\Controllers\Organization\OrganizationController::class, 'branches'])->name('branches');
        Route::get('/teams',       [\App\Http\Controllers\Organization\OrganizationController::class, 'teams'])->name('teams');
        Route::get('/hierarchy',   [\App\Http\Controllers\Organization\OrganizationController::class, 'hierarchy'])->name('hierarchy');
    });
});

// ─── Force password change (spec §19) ───────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/password/force-change',  [ForcePasswordChangeController::class, 'show'])->name('password.force-change');
    Route::post('/password/force-change', [ForcePasswordChangeController::class, 'update'])->name('password.force-change.post');
});
