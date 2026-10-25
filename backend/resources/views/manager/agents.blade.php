@extends('layouts.app')

@section('title', 'StepWise: agents')

@section('content')
<div class="mx-auto min-h-[100dvh] max-w-7xl bg-counter px-5 sm:px-8 sm:shadow-xl lg:px-12"
     style="padding-top: calc(env(safe-area-inset-top) + 24px); padding-bottom: calc(env(safe-area-inset-bottom) + 96px);">

    <h1 class="text-[30px] font-extrabold leading-[1.08] tracking-tight">Agents</h1>
    <p class="mt-1 text-[15px] text-muted">Tap an agent to assign gigs.</p>

    <div class="mt-5 space-y-2">
        @forelse ($agents as $agent)
            <a href="{{ url('/manager/agents/' . $agent->id) }}"
               class="flex items-center gap-3 rounded-2xl border border-hairline bg-slip p-4 active:bg-counter">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-cobalt-soft text-[17px] font-extrabold text-cobalt-deep">
                    {{ strtoupper(mb_substr($agent->name, 0, 1)) }}
                </div>

                <div class="min-w-0 flex-1">
                    <div class="truncate text-[16px] font-bold">{{ $agent->name }}</div>
                    <div class="truncate text-sm text-muted">
                        {{ $agent->region ?? 'No region' }} &middot;
                        {{ $agent->gigs_count }} {{ \Illuminate\Support\Str::plural('gig', $agent->gigs_count) }} &middot;
                        {{ $agent->in_progress }} in progress, {{ $agent->completed }} completed
                    </div>
                </div>

                <span class="shrink-0 rounded-full px-3 py-1 text-xs font-semibold {{ $agent->status === 'active' ? 'bg-ledger-soft text-ledger' : 'bg-counter text-muted' }}">
                    {{ ucfirst($agent->status ?? 'unknown') }}
                </span>

                <svg class="shrink-0 text-muted" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M9 18l6-6-6-6"/>
                </svg>
            </a>
        @empty
            <p class="rounded-2xl bg-slip p-4 text-[15px] text-muted">No agents yet.</p>
        @endforelse
    </div>

</div>

@include('partials.tabbar')
@endsection
