@extends('layouts.base')
@section('body')
<div class="flex min-h-screen">
    {{-- Sidebar --}}
    <aside class="hidden lg:flex w-60 flex-col bg-beha-navy text-white">
        <div class="flex items-center gap-3 px-5 py-5 border-b border-white/10">
            <span class="inline-block h-8 w-8 rounded bg-beha-gold"></span>
            <span class="text-lg font-semibold tracking-tight">Beha</span>
        </div>
        <nav class="flex-1 px-3 py-4 space-y-1 text-sm">
            @auth
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2 rounded hover:bg-white/10 {{ request()->routeIs('dashboard') ? 'bg-white/10' : '' }}">
                <span>Dashboard</span>
            </a>
            @can('viewAny', \App\Models\Customer::class)
            <a href="{{ route('customers.index') }}" class="block px-3 py-2 rounded hover:bg-white/10 {{ request()->routeIs('customers.*') ? 'bg-white/10' : '' }}">Customers</a>
            @endcan
            @can('viewAny', \App\Models\Property::class)
            <a href="{{ route('properties.index') }}" class="block px-3 py-2 rounded hover:bg-white/10 {{ request()->routeIs('properties.*') ? 'bg-white/10' : '' }}">Properties</a>
            @endcan
            @can('viewAny', \App\Models\Applicant::class)
            <a href="{{ route('applicants.index') }}" class="block px-3 py-2 rounded hover:bg-white/10 {{ request()->routeIs('applicants.*') ? 'bg-white/10' : '' }}">Applicants</a>
            @endcan
            <a href="{{ route('workflows.index') }}" class="block px-3 py-2 rounded hover:bg-white/10 {{ request()->routeIs('workflows.*') ? 'bg-white/10' : '' }}">Workflows</a>
            <a href="{{ route('organization.hierarchy') }}" class="block px-3 py-2 rounded hover:bg-white/10 {{ request()->routeIs('organization.*') ? 'bg-white/10' : '' }}">Organization</a>
            @endauth
        </nav>
        <div class="px-3 py-4 border-t border-white/10 text-xs text-white/60">
            Beha · v0.1.0-phase1
        </div>
    </aside>

    {{-- Main --}}
    <div class="flex-1 flex flex-col">
        {{-- Topnav --}}
        <header class="h-16 bg-white border-b border-neutral-200 flex items-center justify-between px-6">
            <div class="flex items-center gap-3 text-sm">
                <button class="lg:hidden text-neutral-700" aria-label="Open menu">≡</button>
                <nav class="hidden md:flex items-center gap-2 text-neutral-500">
                    <a href="{{ route('dashboard') }}" class="hover:text-neutral-900">Home</a>
                    <span>/</span>
                    <span class="text-neutral-900">@yield('breadcrumb', 'Dashboard')</span>
                </nav>
            </div>
            <div class="flex items-center gap-4 text-sm">
                @auth
                <button class="relative">
                    <span class="text-neutral-700">Notifications</span>
                    @if(($notifications = auth()->user()->notifications()->whereNull('read_at')->count()) > 0)
                    <span class="absolute -top-2 -right-3 inline-flex items-center justify-center h-5 min-w-[20px] px-1 text-xs font-semibold text-white bg-red-500 rounded-full">{{ $notifications }}</span>
                    @endif
                </button>
                <div class="relative group">
                    <button class="flex items-center gap-2">
                        <span class="inline-block h-8 w-8 rounded-full bg-beha-navy text-white text-xs font-semibold flex items-center justify-center">{{ strtoupper(substr(auth()->user()->username, 0, 2)) }}</span>
                        <span class="hidden md:inline">{{ auth()->user()->username }}</span>
                    </button>
                    <div class="hidden group-hover:block absolute right-0 mt-2 w-48 bg-white rounded shadow-lg border border-neutral-200 py-2 text-sm">
                        <a href="{{ route('dashboard') }}" class="block px-4 py-2 hover:bg-neutral-50">Profile</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="block w-full text-left px-4 py-2 hover:bg-neutral-50 text-red-600">Sign out</button>
                        </form>
                    </div>
                </div>
                @endauth
            </div>
        </header>

        {{-- Content --}}
        <main class="flex-1 p-6 md:p-8">
            @if(session('success'))
            <div class="mb-4 px-4 py-3 rounded bg-green-50 border border-green-200 text-green-800 text-sm">{{ session('success') }}</div>
            @endif
            @if(session('warning'))
            <div class="mb-4 px-4 py-3 rounded bg-amber-50 border border-amber-200 text-amber-800 text-sm">{{ session('warning') }}</div>
            @endif
            @if(session('error'))
            <div class="mb-4 px-4 py-3 rounded bg-red-50 border border-red-200 text-red-800 text-sm">{{ session('error') }}</div>
            @endif

            @yield('content')
        </main>
    </div>
</div>
@endsection
