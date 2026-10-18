@extends('layouts.app')

@section('title', 'StepWise: ' . $agent->name)

@section('content')
<div
    class="mx-auto min-h-[100dvh] max-w-[480px] bg-counter px-5 sm:shadow-xl"
    style="padding-top: calc(env(safe-area-inset-top) + 20px); padding-bottom: calc(env(safe-area-inset-bottom) + 28px);">

    <a href="{{ url('/agent') }}" class="text-sm font-semibold text-cobalt-deep">Switch agent</a>

    <h1 class="mt-3 text-[30px] font-extrabold leading-[1.08] tracking-tight">{{ $agent->name }}</h1>
    <p class="mt-1 text-[15px] text-muted">{{ $agent->region ?? 'No region set' }}</p>

    <!-- Summary -->
    <div class="mt-5 grid grid-cols-3 gap-2">
        <div class="rounded-xl bg-slip p-3">
            <div class="text-[24px] font-extrabold leading-none">{{ $counts['in_progress'] }}</div>
            <div class="mt-1 text-xs text-muted">In progress</div>
        </div>
        <div class="rounded-xl bg-slip p-3">
            <div class="text-[24px] font-extrabold leading-none">{{ $counts['completed'] }}</div>
            <div class="mt-1 text-xs text-muted">Completed</div>
        </div>
        <div class="rounded-xl {{ $counts['alerts'] > 0 ? 'bg-amber-soft' : 'bg-slip' }} p-3">
            <div class="text-[24px] font-extrabold leading-none">{{ $counts['alerts'] }}</div>
            <div class="mt-1 text-xs text-muted">Open alerts</div>
        </div>
    </div>

    <!-- Start a new onboarding -->
    <form method="POST" action="{{ url('/agent/' . $agent->id . '/sessions') }}" class="mt-5">
        @csrf
        <button type="submit"
                class="h-14 w-full rounded-xl bg-cobalt text-[17px] font-bold text-white">
            Start new onboarding
        </button>
    </form>

    <h2 class="mt-8 text-[15px] font-semibold">Your onboardings</h2>

    @forelse ($sessions as $session)
        @php
            $saved = (int) $session->saved_steps;

            $pill = match ($session->status) {
                'completed' => 'bg-ledger-soft text-ledger',
                'abandoned' => 'bg-counter text-muted',
                default => 'bg-cobalt-soft text-cobalt-deep',
            };

            $label = ucfirst(str_replace('_', ' ', $session->status));

            $startedAt = \Carbon\Carbon::parse($session->started_at)->diffForHumans();

            $lastActivity = $session->last_activity
                ? \Carbon\Carbon::parse($session->last_activity)->diffForHumans()
                : null;
        @endphp

        <a href="{{ url('/onboarding/' . $session->id) }}"
           class="mt-3 block rounded-2xl border border-hairline bg-slip p-4">

            <div class="flex items-start justify-between gap-3">
                <div>
                    <div class="text-[17px] font-bold">Onboarding #{{ $session->id }}</div>
                    <div class="mt-0.5 text-sm text-muted">Started {{ $startedAt }}</div>
                </div>

                <span class="shrink-0 rounded-full px-3 py-1 text-xs font-semibold {{ $pill }}">
                    {{ $label }}
                </span>
            </div>

            <!-- The same ruler as the onboarding screen: one segment per step -->
            <div class="mt-4 flex items-center gap-1" aria-hidden="true">
                @for ($i = 1; $i <= $totalSteps; $i++)
                    <span class="h-2.5 flex-1 rounded-[3px] {{ $i <= $saved ? 'bg-ledger' : 'bg-hairline' }}"></span>
                @endfor
            </div>

            <div class="mt-3 flex items-center justify-between gap-3 text-sm">
                <span class="text-muted">
                    {{ $saved }} of {{ $totalSteps }} steps saved{{ $lastActivity ? ', last activity ' . $lastActivity : '' }}
                </span>

                @if ($session->open_alerts > 0)
                    <span class="shrink-0 rounded-full bg-amber-soft px-3 py-1 text-xs font-semibold">
                        {{ $session->open_alerts }} {{ \Illuminate\Support\Str::plural('alert', $session->open_alerts) }}
                    </span>
                @endif
            </div>

        </a>
    @empty
        <p class="mt-3 rounded-2xl bg-slip p-4 text-[15px] leading-snug text-muted">
            No onboardings yet. Start one with the button above.
        </p>
    @endforelse

</div>
@endsection