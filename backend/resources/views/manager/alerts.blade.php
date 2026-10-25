@extends('layouts.app')

@section('title', 'StepWise: alerts')

@section('content')
<div class="mx-auto min-h-[100dvh] max-w-7xl bg-counter px-5 sm:px-8 sm:shadow-xl lg:px-12"
     style="padding-top: calc(env(safe-area-inset-top) + 24px); padding-bottom: calc(env(safe-area-inset-bottom) + 96px);">

    <h1 class="text-[30px] font-extrabold leading-[1.08] tracking-tight">Alerts</h1>
    <p class="mt-1 text-[15px] text-muted">Agents who may need a hand.</p>

    @include('manager._gig-filter', ['action' => '/manager/alerts'])

    <div class="mt-4 space-y-3">
        @forelse ($alerts as $alert)
            <div class="rounded-2xl border border-amber/60 bg-amber-soft p-4">
                <div class="flex flex-wrap items-start justify-between gap-3">

                    <div>
                        <div class="text-[16px] font-bold">{{ $alert->reason }}</div>

                        <div class="mt-1 text-sm leading-snug text-ink/80">
                            {{ $alert->session?->agent?->name ?? 'Unknown agent' }},
                            onboarding #{{ $alert->session_id }},
                            {{ $alert->session?->gig?->name ?? 'Unknown gig' }},
                            step "{{ $alert->step?->title ?? 'Unknown step' }}"
                        </div>

                        <div class="mt-0.5 text-xs text-muted">
                            Raised {{ \Carbon\Carbon::parse($alert->created_at)->diffForHumans() }}
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <a href="{{ url('/manager/sessions/' . $alert->session_id) }}"
                           class="tap inline-flex items-center rounded-xl border border-hairline bg-slip px-3 text-sm font-semibold">
                            View onboarding
                        </a>

                        <form method="POST" action="{{ url('/manager/alerts/' . $alert->id . '/resolve') }}">
                            @csrf
                            <button type="submit"
                                    class="rounded-xl bg-ink px-3 text-sm font-semibold text-white">
                                Mark resolved
                            </button>
                        </form>
                    </div>

                </div>
            </div>
        @empty
            <p class="rounded-2xl bg-ledger-soft p-4 text-[15px] text-ledger">
                No open alerts{{ $selectedGig ? ' for this gig' : '' }}. Every active onboarding is moving along.
            </p>
        @endforelse
    </div>

</div>

@include('partials.tabbar')
@endsection
