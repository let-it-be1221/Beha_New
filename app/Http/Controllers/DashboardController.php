<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Role-aware dashboard routing (spec §22)
        $view = match (true) {
            $user->hasRole('system_administrator') => 'dashboard.system-admin',
            $user->hasRole('executive_officer')    => 'dashboard.executive',
            $user->hasRole('record_officer')       => 'dashboard.record-officer',
            $user->hasRole('finance_officer')       => 'dashboard.finance',
            $user->isGenerationLeader()             => 'dashboard.generation',
            $user->isBranchLeader()                  => 'dashboard.branch',
            $user->isTeamLeader()                    => 'dashboard.team-leader',
            default                                  => 'dashboard.team-member',
        };

        return view($view, [
            'user'       => $user,
            'pendingWorkflows' => [],
            'notifications'    => $user->notifications()->limit(8)->get(),
        ]);
    }
}
