@extends('layouts.app')

@section('title', 'StepWise: ' . $agent->name)

@section('content')
<div class="mx-auto min-h-[100dvh] max-w-[640px] bg-counter px-5 sm:shadow-xl"
     style="padding-top: calc(env(safe-area-inset-top) + 12px); padding-bottom: calc(env(safe-area-inset-bottom) + 96px);">

    <a href="{{ url('/manager/agents') }}" class="tap -ml-1 inline-flex items-center gap-1 text-sm font-semibold text-cobalt-deep">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M15 18l-6-6 6-6"/>
        </svg>
        Agents
    </a>

    <h1 class="mt-2 text-[30px] font-extrabold leading-[1.08] tracking-tight">{{ $agent->name }}</h1>
    <p class="mt-1 text-[15px] text-muted">
        {{ $agent->region ?? 'No region' }}@if ($agent->phone) &middot; {{ $agent->phone }}@endif
    </p>

    @if (session('status'))
        <p class="mt-4 rounded-2xl bg-ledger-soft p-3 text-[15px] font-semibold text-ledger" role="status">
            {{ session('status') }}
        </p>
    @endif

    <h2 class="mt-7 text-[15px] font-semibold">Gigs</h2>
    <p class="text-sm text-muted">Switch a gig on to let {{ $agent->name }} start it.</p>

    <div class="mt-3 space-y-3">
        @forelse ($gigs as $gig)
            @php
                $assigned = $assignedIds->contains($gig->id);
                $sessions = $sessionsByGig[$gig->id] ?? collect();
                $latest = $sessions->first();
                $completed = $sessions->where('status', 'completed')->count();
                $total = (int) $gig->steps_count;
            @endphp

            <div class="rounded-2xl border {{ $assigned ? 'border-cobalt/40' : 'border-hairline' }} bg-slip p-4">

                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="text-[16px] font-bold leading-snug">{{ $gig->name }}</div>
                        <div class="text-sm text-muted">
                            @if ($gig->client){{ $gig->client }} &middot; @endif{{ $total }} steps
                        </div>
                    </div>

                    <!-- The switch: a form that sends the state we want (on or off) -->
                    <form method="POST" action="{{ url('/manager/agents/' . $agent->id . '/gigs/' . $gig->id) }}">
                        @csrf
                        <input type="hidden" name="assign" value="{{ $assigned ? 0 : 1 }}">

                        <button type="submit" role="switch" aria-checked="{{ $assigned ? 'true' : 'false' }}"
                                aria-label="{{ $assigned ? 'Unassign' : 'Assign' }} {{ $gig->name }}"
                                class="flex items-center">
                            <span class="relative inline-block h-8 w-14 rounded-full transition-colors {{ $assigned ? 'bg-cobalt' : 'bg-hairline' }}">
                                <span class="absolute top-1 h-6 w-6 rounded-full bg-white shadow transition-all {{ $assigned ? 'left-7' : 'left-1' }}"></span>
                            </span>
                        </button>
                    </form>
                </div>

                <!-- Progress on this gig -->
                @if ($latest)
                    @php $saved = (int) $latest->saved_steps; @endphp

                    <div class="mt-4 flex items-center gap-1" aria-hidden="true">
                        @for ($i = 1; $i <= $total; $i++)
                            <span class="h-2 flex-1 rounded-[2px] {{ $i <= $saved ? 'bg-ledger' : 'bg-hairline' }}"></span>
                        @endfor
                    </div>

                    <div class="mt-2 flex flex-wrap items-center justify-between gap-2 text-sm">
                        <span class="text-muted">
                            Latest: {{ $saved }} of {{ $total }} steps &middot;
                            {{ $sessions->count() }} started, {{ $completed }} completed
                        </span>
                        <a href="{{ url('/manager/sessions/' . $latest->id) }}"
                           class="tap inline-flex items-center font-semibold text-cobalt-deep">
                            View #{{ $latest->id }}
                        </a>
                    </div>
                @else
                    <p class="mt-3 text-sm text-muted">
                        {{ $assigned ? 'Assigned, not started yet.' : 'Not assigned.' }}
                    </p>
                @endif

                @if (! $assigned && $sessions->where('status', 'in_progress')->count())
                    <p class="mt-2 text-xs text-muted">
                        Onboardings already started on this gig can still be finished.
                    </p>
                @endif
            </div>
        @empty
            <p class="rounded-2xl bg-slip p-4 text-[15px] text-muted">No gigs exist yet.</p>
        @endforelse
    </div>

</div>

@include('partials.tabbar')
@endsection
