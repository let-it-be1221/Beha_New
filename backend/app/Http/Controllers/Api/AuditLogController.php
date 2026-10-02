<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', AuditLog::class);

        $logs = AuditLog::query()
            ->with(['user'])
            ->when($request->input('action'),   fn($q, $a)  => $q->where('action', $a))
            ->when($request->input('category'),fn($q, $c)  => $q->where('category', $c))
            ->when($request->input('severity'),fn($q, $s)  => $q->where('severity', $s))
            ->when($request->input('user_id'),  fn($q, $u)  => $q->where('user_id', $u))
            ->latest()
            ->paginate(50);

        return response()->json($logs);
    }
}
