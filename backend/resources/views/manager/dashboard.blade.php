@extends('layouts.app')

@section('title', 'StepWise: manager')

@section('content')
<div class="mx-auto min-h-[100dvh] max-w-7xl bg-counter px-5 pb-12 pt-7 sm:px-8 sm:shadow-xl lg:px-12 lg:pt-10">

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-[30px] font-extrabold leading-[1.08] tracking-tight">Manager dashboard</h1>
            <p class="mt-1 text-[15px] text-muted">Onboardings and alerts across all agents.</p>
        </div>

        <a href="{{ url('/agent') }}"
           class="rounded-xl border border-hairline bg-slip px-4 py-2.5 text-sm font-semibold">
            Agent view
        </a>
    </div>

    <!-- Gig filter -->
    <form method="GET" action="{{ url('/manager') }}" class="mt-6 flex flex-wrap items-center gap-2">
        <label for="gig" class="text-sm font-semibold">Gig</label>

        <select id="gig" name="gig" onchange="this.form.submit()"
                class="rounded-xl border border-hairline bg-slip px-3 py-2 text-sm">
            <option value="">All gigs</option>
            @foreach ($gigs as $gig)
                <option value="{{ $gig->slug }}" @selected($selectedGig?->id === $gig->id)>{{ $gig->name }}</option>
            @endforeach
        </select>

        <noscript>
            <button type="submit" class="rounded-xl bg-ink px-3 py-2 text-sm font-semibold text-white">Filter</button>
        </noscript>
    </form>

    <!-- Summary -->
    <div class="mt-4 grid grid-cols-2 gap-3 md:grid-cols-4">
        <div class="rounded-2xl bg-slip p-4">
            <div class="text-[30px] font-extrabold leading-none">{{ $counts['in_progress'] }}</div>
            <div class="mt-2 text-sm text-muted">In progress</div>
        </div>
        <div class="rounded-2xl bg-slip p-4">
            <div class="text-[30px] font-extrabold leading-none">{{ $counts['completed'] }}</div>
            <div class="mt-2 text-sm text-muted">Completed</div>
        </div>
        <div class="rounded-2xl {{ $counts['alerts'] > 0 ? 'bg-amber-soft' : 'bg-slip' }} p-4">
            <div class="text-[30px] font-extrabold leading-none">{{ $counts['alerts'] }}</div>
            <div class="mt-2 text-sm text-muted">Open alerts</div>
        </div>
        <div class="rounded-2xl bg-slip p-4">
            <div class="text-[30px] font-extrabold leading-none">{{ $counts['agents'] }}</div>
            <div class="mt-2 text-sm text-muted">Active agents</div>
        </div>
    </div>

    <!-- Open alerts -->
    <h2 class="mt-10 text-[18px] font-bold">Open alerts</h2>

    <div class="mt-3 space-y-3">
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
                           class="rounded-xl border border-hairline bg-slip px-3 py-2 text-sm font-semibold">
                            View onboarding
                        </a>

                        <form method="POST" action="{{ url('/manager/alerts/' . $alert->id . '/resolve') }}">
                            @csrf
                            <button type="submit"
                                    class="rounded-xl bg-ink px-3 py-2 text-sm font-semibold text-white">
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

    <!-- All onboardings -->
    <h2 class="mt-10 text-[18px] font-bold">All onboardings</h2>

    <div class="mt-3 overflow-x-auto rounded-2xl border border-hairline bg-slip">
        <table class="w-full min-w-[760px] text-left text-sm lg:text-[15px]">
            <thead class="border-b border-hairline text-muted">
                <tr>
                    <th class="px-4 py-3 font-semibold">Onboarding</th>
                    <th class="px-4 py-3 font-semibold">Gig</th>
                    <th class="px-4 py-3 font-semibold">Agent</th>
                    <th class="px-4 py-3 font-semibold">Status</th>
                    <th class="px-4 py-3 font-semibold">Progress</th>
                    <th class="px-4 py-3 font-semibold">Alerts</th>
                    <th class="px-4 py-3 font-semibold">Last activity</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($sessions as $session)
                    @php
                        $saved = (int) $session->saved_steps;

                        // Measured against this onboarding's own gig, not every step in the system.
                        $total = (int) ($stepTotals[$session->gig_id] ?? 0);

                        $pill = match ($session->status) {
                            'completed' => 'bg-ledger-soft text-ledger',
                            'abandoned' => 'bg-counter text-muted',
                            default => 'bg-cobalt-soft text-cobalt-deep',
                        };

                        $label = ucfirst(str_replace('_', ' ', $session->status));

                        $last = $session->last_activity ?: $session->started_at;
                    @endphp

                    <tr class="border-b border-hairline last:border-0">
                        <td class="px-4 py-3">
                            <a href="{{ url('/manager/sessions/' . $session->id) }}"
                               class="font-bold text-cobalt-deep">
                                #{{ $session->id }}
                            </a>
                        </td>

                        <td class="px-4 py-3">{{ $session->gig?->name ?? 'No gig' }}</td>

                        <td class="px-4 py-3">{{ $session->agent?->name ?? 'Unknown' }}</td>

                        <td class="px-4 py-3">
                            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $pill }}">{{ $label }}</span>
                        </td>

                        <td class="px-4 py-3">
                            <div class="flex w-40 items-center gap-0.5 lg:w-56" aria-hidden="true">
                                @for ($i = 1; $i <= $total; $i++)
                                    <span class="h-2 flex-1 rounded-[2px] {{ $i <= $saved ? 'bg-ledger' : 'bg-hairline' }}"></span>
                                @endfor
                            </div>
                            <div class="mt-1 text-xs text-muted">{{ $saved }} of {{ $total }}</div>
                        </td>

                        <td class="px-4 py-3">
                            @if ($session->open_alerts > 0)
                                <span class="rounded-full bg-amber-soft px-3 py-1 text-xs font-semibold">
                                    {{ $session->open_alerts }}
                                </span>
                            @else
                                <span class="text-muted">None</span>
                            @endif
                        </td>

                        <td class="px-4 py-3 text-muted">
                            {{ \Carbon\Carbon::parse($last)->diffForHumans() }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-6 text-center text-muted">
                            No onboardings{{ $selectedGig ? ' for this gig' : '' }} yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
@endsection