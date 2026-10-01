@extends('dashboard.team-member')
@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold">Welcome, {{ auth()->user()->username }}</h1>
        <p class="text-neutral-600">Role: {{ auth()->user()->roles->pluck('display_name')->implode(', ') }}</p>
    </div>
    <div class="bg-white rounded-lg border border-neutral-200 p-8 text-center text-neutral-500">
        <p class="font-medium text-neutral-700">{{ ucfirst(str_replace('-', ' ', 'branch')) }} Dashboard</p>
        <p class="text-sm mt-1">This dashboard is a Phase 2 deliverable scaffold. Wire KPI cards, charts, and role-scoped lists here.</p>
    </div>
</div>
@endsection
