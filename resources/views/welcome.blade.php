@extends('layouts.base')
@section('title', 'Beha — Real Estate Management System')
@section('body')
<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-beha-navy via-neutral-900 to-beha-navy text-white">
    <div class="max-w-3xl text-center px-6">
        <div class="flex items-center justify-center gap-3 mb-6">
            <span class="inline-block h-12 w-12 rounded bg-beha-gold"></span>
            <span class="text-3xl font-bold tracking-tight">Beha</span>
        </div>
        <h1 class="text-4xl md:text-5xl font-bold mb-4">Real Estate Management, Reimagined.</h1>
        <p class="text-lg text-white/70 mb-8">A modern enterprise platform for sales, customers, properties, evaluations, and organizational workflow — built for the real estate organization of tomorrow.</p>
        <div class="flex flex-col md:flex-row gap-4 justify-center">
            <a href="{{ route('login') }}" class="px-6 py-3 rounded bg-beha-gold text-beha-navy font-semibold hover:opacity-90">Sign in</a>
            <a href="{{ route('applicants.apply') }}" class="px-6 py-3 rounded border border-white/20 text-white hover:bg-white/10">Apply to join</a>
        </div>
        <div class="mt-12 text-sm text-white/50">
            <a href="{{ route('public.properties.index') }}" class="hover:text-white">Browse properties →</a>
        </div>
    </div>
</div>
@endsection
