@extends('layouts.app')

@section('title', 'StepWise: manager')

@section('content')
<div class="mx-auto min-h-[100dvh] max-w-7xl bg-counter px-5 sm:px-8 sm:shadow-xl lg:px-12"
     style="padding-top: calc(env(safe-area-inset-top) + 24px); padding-bottom: calc(env(safe-area-inset-bottom) + 96px);">

    <h1 class="text-[30px] font-extrabold leading-[1.08] tracking-tight">Overview</h1>
    <p class="mt-1 text-[15px] text-muted">Onboardings across all agents.</p>

    @include('manager._gig-filter', ['action' => '/manager'])

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
        <a href="{{ url('/manager/alerts' . ($selectedGig ? '?gig=' . $selectedGig->slug : '')) }}"
           class="rounded-2xl {{ $counts['alerts'] > 0 ? 'bg-amber-soft' : 'bg-slip' }} p-4">
            <div class="text-[30px] font-extrabold leading-none">{{ $counts['alerts'] }}</div>
            <div class="mt-2 text-sm text-muted">Open alerts &rsaquo;</div>
        </a>
        <div class="rounded-2xl bg-slip p-4">
            <div class="text-[30px] font-extrabold leading-none">{{ $counts['agents'] }}</div>
            <div class="mt-2 text-sm text-muted">{{ $selectedGig ? 'Agents on this gig' : 'Active agents' }}</div>
        </div>
    </div>

    <!-- Step difficulty insight -->
    <a href="{{ url('/manager/insights' . ($selectedGig ? '?gig=' . $selectedGig->slug : '')) }}"
       class="mt-4 flex items-center justify-between gap-3 rounded-2xl bg-ink p-4 text-white active:opacity-90">
        <div>
            <div class="text-[16px] font-bold">Step difficulty</div>
            <div class="text-sm text-white/75">See which step of each gig confuses agents most.</div>
        </div>
        <svg class="shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M9 18l6-6-6-6"/>
        </svg>
    </a>

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
                               class="tap inline-flex items-center font-bold text-cobalt-deep">
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
@include('partials.tabbar')
@endsection
