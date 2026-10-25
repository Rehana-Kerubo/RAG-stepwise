@extends('layouts.app')

@section('title', 'StepWise: profile')

@section('content')
<div
    class="mx-auto min-h-[100dvh] max-w-[480px] bg-counter px-5 sm:shadow-xl"
    style="padding-top: calc(env(safe-area-inset-top) + 20px); padding-bottom: calc(env(safe-area-inset-bottom) + 96px);">

    <h1 class="text-[30px] font-extrabold leading-[1.08] tracking-tight">Profile</h1>

    <div class="mt-5 flex items-center gap-4 rounded-2xl bg-slip p-4">
        <!-- First letter of the name; the Google photo comes in the next phase -->
        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-cobalt text-[22px] font-extrabold text-white">
            {{ strtoupper(mb_substr($user->name, 0, 1)) }}
        </div>

        <div class="min-w-0">
            <div class="truncate text-[18px] font-bold">{{ $user->name }}</div>
            <div class="truncate text-sm text-muted">{{ $user->email }}</div>
            <span class="mt-1 inline-block rounded-full bg-cobalt-soft px-3 py-0.5 text-xs font-semibold text-cobalt-deep">
                {{ ucfirst($user->role) }}
            </span>
        </div>
    </div>

    @if ($agent)
        <div class="mt-3 rounded-2xl bg-slip p-4">
            <div class="text-sm text-muted">Region</div>
            <div class="text-[16px] font-semibold">{{ $agent->region ?? 'No region set' }}</div>
        </div>
    @endif

    <form method="POST" action="{{ url('/logout') }}" class="mt-6">
        @csrf
        <button type="submit"
                class="h-14 w-full rounded-xl border border-hairline bg-slip text-[17px] font-bold text-red-700">
            Log out
        </button>
    </form>

</div>

@include('partials.tabbar')
@endsection
