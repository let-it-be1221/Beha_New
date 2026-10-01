<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * API\CustomerController — REST API controller (Phase 2+).
 * Spec §37. Authorization is enforced via Policies + Sanctum token abilities.
 */
class CustomerController extends Controller
{
    public function index(Request $request)
    {
        abort(501, 'API endpoint scaffolded — implementation pending Phase 2.');
    }

    public function show(Request $request, $id)
    {
        abort(501, 'API endpoint scaffolded — implementation pending Phase 2.');
    }

    public function store(Request $request)
    {
        abort(501, 'API endpoint scaffolded — implementation pending Phase 2.');
    }

    public function update(Request $request, $id)
    {
        abort(501, 'API endpoint scaffolded — implementation pending Phase 2.');
    }

    public function destroy(Request $request, $id)
    {
        abort(501, 'API endpoint scaffolded — implementation pending Phase 2.');
    }
}
