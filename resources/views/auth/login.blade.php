@extends('layouts.base')
@section('title', 'Sign in · Beha')
@section('body')
<div class="min-h-screen flex items-center justify-center bg-neutral-100 py-12 px-4">
    <div class="w-full max-w-md bg-white rounded-lg shadow-lg p-8">
        <div class="flex items-center gap-3 mb-6">
            <span class="inline-block h-10 w-10 rounded bg-beha-navy"></span>
            <div>
                <h1 class="text-xl font-bold text-neutral-900">Beha</h1>
                <p class="text-xs text-neutral-500">Real Estate Management System</p>
            </div>
        </div>

        <h2 class="text-lg font-semibold mb-2">Welcome back</h2>
        <p class="text-sm text-neutral-500 mb-6">Sign in with your email, username, or Official ID.</p>

        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="mb-4">
                <label for="login" class="block text-sm font-medium text-neutral-700 mb-1">Email or Username</label>
                <input type="text" id="login" name="login" required autofocus
                    class="w-full px-3 py-2 border border-neutral-300 rounded focus:border-beha-navy focus:ring-1 focus:ring-beha-navy outline-none"
                    value="{{ old('login') }}">
                @error('login') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="mb-4">
                <label for="password" class="block text-sm font-medium text-neutral-700 mb-1">Password</label>
                <input type="password" id="password" name="password" required
                    class="w-full px-3 py-2 border border-neutral-300 rounded focus:border-beha-navy focus:ring-1 focus:ring-beha-navy outline-none">
                @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="flex items-center justify-between mb-6">
                <label class="flex items-center text-sm">
                    <input type="checkbox" name="remember" class="mr-2"> Remember me
                </label>
                <a href="#" class="text-sm text-beha-navy hover:underline">Forgot password?</a>
            </div>
            <button type="submit" class="w-full py-2.5 bg-beha-navy text-white rounded font-semibold hover:bg-neutral-800">
                Sign in
            </button>
        </form>

        <div class="my-6 border-t border-neutral-200"></div>

        <p class="text-sm text-center text-neutral-600">
            New applicant?
            <a href="{{ route('applicants.apply') }}" class="text-beha-navy font-medium hover:underline">Apply to join →</a>
        </p>
    </div>
</div>
@endsection
