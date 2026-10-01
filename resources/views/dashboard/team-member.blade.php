@extends('layouts.app')
@section('breadcrumb', 'Dashboard')
@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold">Welcome, {{ auth()->user()->username }}</h1>
        <p class="text-neutral-600">Official ID: <span class="font-mono">{{ auth()->user()->official_id }}</span> · Level {{ auth()->user()->level }}</p>
    </div>

    {{-- Stat cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg border border-neutral-200 p-5">
            <p class="text-xs uppercase tracking-wide text-neutral-500">Pending Workflows</p>
            <p class="text-2xl font-bold mt-1">{{ count($pendingWorkflows) }}</p>
        </div>
        <div class="bg-white rounded-lg border border-neutral-200 p-5">
            <p class="text-xs uppercase tracking-wide text-neutral-500">Notifications</p>
            <p class="text-2xl font-bold mt-1">{{ $notifications->count() }}</p>
        </div>
        <div class="bg-white rounded-lg border border-neutral-200 p-5">
            <p class="text-xs uppercase tracking-wide text-neutral-500">Role</p>
            <p class="text-lg font-semibold mt-1">{{ auth()->user()->roles->pluck('display_name')->implode(', ') }}</p>
        </div>
        <div class="bg-white rounded-lg border border-neutral-200 p-5">
            <p class="text-xs uppercase tracking-wide text-neutral-500">Last login</p>
            <p class="text-sm font-semibold mt-1">{{ auth()->user()->last_login_at?->diffForHumans() ?? '—' }}</p>
        </div>
    </div>

    {{-- Recent notifications --}}
    <div class="bg-white rounded-lg border border-neutral-200">
        <div class="px-5 py-3 border-b border-neutral-200 flex items-center justify-between">
            <h2 class="font-semibold">Recent Notifications</h2>
            <a href="#" class="text-sm text-beha-navy hover:underline">View all</a>
        </div>
        <div class="divide-y divide-neutral-100">
            @forelse($notifications as $notif)
            <div class="px-5 py-3 text-sm">
                <p class="font-medium">{{ $notif->data['title'] ?? $notif->type }}</p>
                <p class="text-xs text-neutral-500">{{ $notif->created_at->diffForHumans() }}</p>
            </div>
            @empty
            <div class="px-5 py-8 text-sm text-neutral-500 text-center">No notifications yet.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
