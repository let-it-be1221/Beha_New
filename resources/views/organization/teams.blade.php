@extends('layouts.app')
@section('title', 'organization.teams · Beha')
@section('content')
<div class="bg-white rounded-lg border border-neutral-200 p-8 text-center text-neutral-500">
    <p class="font-medium text-neutral-700">{{ str_replace('.', ' · ', 'organization.teams') }}</p>
    <p class="text-sm mt-2">This view is a Phase 2 scaffold — implement the actual UI per <code>docs/06-ui-ux-structure.md</code>.</p>
</div>
@endsection
