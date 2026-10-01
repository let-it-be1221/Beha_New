@extends('layouts.base')
@section('title', 'Change Password · Beha')
@section('body')
<div class="min-h-screen flex items-center justify-center bg-neutral-100 py-12 px-4">
    <div class="w-full max-w-md bg-white rounded-lg shadow-lg p-8">
        <div class="mb-4 px-4 py-3 rounded bg-amber-50 border border-amber-200 text-amber-800 text-sm">
            Your account was created with a temporary password. You must set a new password before continuing.
        </div>

        <h2 class="text-lg font-semibold mb-4">Set a new password</h2>

        <form method="POST" action="{{ route('password.force-change.post') }}">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-neutral-700 mb-1">New password</label>
                <input type="password" name="password" required
                    class="w-full px-3 py-2 border border-neutral-300 rounded focus:border-beha-navy focus:ring-1 focus:ring-beha-navy outline-none">
                @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium text-neutral-700 mb-1">Confirm password</label>
                <input type="password" name="password_confirmation" required
                    class="w-full px-3 py-2 border border-neutral-300 rounded focus:border-beha-navy focus:ring-1 focus:ring-beha-navy outline-none">
            </div>
            <button type="submit" class="w-full py-2.5 bg-beha-navy text-white rounded font-semibold hover:bg-neutral-800">
                Change password & continue
            </button>
        </form>

        <p class="mt-4 text-xs text-neutral-500">
            Minimum {{ config('beha.password_policy.min_length', 12) }} characters,
            @if(config('beha.password_policy.mixed_case'))mixed case, @endif
            @if(config('beha.password_policy.require_number'))at least one number, @endif
            @if(config('beha.password_policy.require_symbol'))at least one symbol.@endif
        </p>
    </div>
</div>
@endsection
