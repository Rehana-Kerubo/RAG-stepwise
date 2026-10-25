@extends('layouts.app')

@section('title', 'StepWise: agents')

@section('content')
<div class="mx-auto min-h-[100dvh] max-w-7xl bg-counter px-5 sm:px-8 sm:shadow-xl lg:px-12"
     style="padding-top: calc(env(safe-area-inset-top) + 24px); padding-bottom: calc(env(safe-area-inset-bottom) + 96px);">

    <h1 class="text-[30px] font-extrabold leading-[1.08] tracking-tight">Agents</h1>
    <p class="mt-1 text-[15px] text-muted">Everyone in your team.</p>

    <div class="mt-5 space-y-2">
        @forelse ($agents as $agent)
            <div class="flex items-center gap-3 rounded-2xl border border-hairline bg-slip p-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-cobalt-soft text-[17px] font-extrabold text-cobalt-deep">
                    {{ strtoupper(mb_substr($agent->name, 0, 1)) }}
                </div>

                <div class="min-w-0 flex-1">
                    <div class="truncate text-[16px] font-bold">{{ $agent->name }}</div>
                    <div class="truncate text-sm text-muted">
                        {{ $agent->region ?? 'No region' }} &middot;
                        {{ $agent->in_progress }} in progress, {{ $agent->completed }} completed
                    </div>
                </div>

                <span class="shrink-0 rounded-full px-3 py-1 text-xs font-semibold {{ $agent->status === 'active' ? 'bg-ledger-soft text-ledger' : 'bg-counter text-muted' }}">
                    {{ ucfirst($agent->status ?? 'unknown') }}
                </span>
            </div>
        @empty
            <p class="rounded-2xl bg-slip p-4 text-[15px] text-muted">No agents yet.</p>
        @endforelse
    </div>

</div>

@include('partials.tabbar')
@endsection
