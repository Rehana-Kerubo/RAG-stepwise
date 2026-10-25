@extends('layouts.app')

@section('title', 'StepWise: onboarding #' . $session->id)

@section('content')
<div class="mx-auto min-h-[100dvh] max-w-7xl bg-counter px-5 sm:px-8 sm:shadow-xl lg:px-12"
     style="padding-top: calc(env(safe-area-inset-top) + 12px); padding-bottom: calc(env(safe-area-inset-bottom) + 96px);">

    <a href="{{ url('/manager') }}" class="tap -ml-1 inline-flex items-center gap-1 text-sm font-semibold text-cobalt-deep">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M15 18l-6-6 6-6"/>
        </svg>
        Overview
    </a>

    @php
        $pill = match ($session->status) {
            'completed' => 'bg-ledger-soft text-ledger',
            'abandoned' => 'bg-counter text-muted',
            default => 'bg-cobalt-soft text-cobalt-deep',
        };

        $label = ucfirst(str_replace('_', ' ', $session->status));

        $started = \Carbon\Carbon::parse($session->started_at)
            ->setTimezone('Africa/Nairobi')
            ->format('j M Y, H:i');
    @endphp

    <div class="mt-3 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-[30px] font-extrabold leading-[1.08] tracking-tight">Onboarding #{{ $session->id }}</h1>
            <p class="mt-1 text-[15px] text-muted">
                {{ $session->agent?->name ?? 'Unknown agent' }},
                {{ $session->gig?->name ?? 'No gig' }},
                started {{ $started }}
            </p>
        </div>

        <span class="rounded-full px-4 py-1.5 text-sm font-semibold {{ $pill }}">{{ $label }}</span>
    </div>

    <!-- Alerts for this onboarding -->
    @if ($alerts->count())
        <h2 class="mt-8 text-[18px] font-bold">Alerts</h2>

        <div class="mt-3 space-y-2">
            @foreach ($alerts as $alert)
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border {{ $alert->is_resolved ? 'border-hairline bg-slip' : 'border-amber/60 bg-amber-soft' }} p-4">
                    <div>
                        <div class="text-[15px] font-bold">{{ $alert->reason }}</div>
                        <div class="mt-0.5 text-sm text-muted">
                            {{ $session->gig?->name ?? 'No gig' }},
                            step "{{ $alert->step?->title ?? 'Unknown step' }}",
                            {{ $alert->is_resolved ? 'resolved' : 'open' }}
                        </div>
                    </div>

                    @unless ($alert->is_resolved)
                        <form method="POST" action="{{ url('/manager/alerts/' . $alert->id . '/resolve') }}">
                            @csrf
                            <button type="submit"
                                    class="rounded-xl bg-ink px-3 text-sm font-semibold text-white">
                                Mark resolved
                            </button>
                        </form>
                    @endunless
                </div>
            @endforeach
        </div>
    @endif

    <!-- Steps -->
    <h2 class="mt-8 text-[18px] font-bold">Steps</h2>

    <div class="mt-3 space-y-2">
        @php $currentFound = false; @endphp

        @foreach ($steps as $step)
            @php
                $p = $progress->get($step->id);
                $done = $p && $p->completed_at;

                $summary = '';

                if ($done) {
                    $data = is_array($p->submission_data)
                        ? $p->submission_data
                        : (json_decode((string) $p->submission_data, true) ?? []);

                    // Turn a select value into its label from this step's own options.
                    $optionLabel = fn ($value) => collect($step->input_config['options'] ?? [])
                        ->firstWhere('value', $value)['label'] ?? $value;

                    // Activation codes and ID numbers are never shown, only that they were entered.
                    $summary = match ($data['type'] ?? '') {
                        'document' => 'Photo uploaded',
                        'confirmation' => 'Confirmed by agent',
                        'activation_code' => 'Activation code entered',
                        'eligibility_check' => 'Eligible, original document seen: ' . $optionLabel($data['id_type'] ?? ''),
                        'id_number' => 'ID number recorded',
                        'sim_number' => 'Registered number: ' . ($data['response'] ?? ''),
                        'airtel_money' => ($data['response'] ?? '') === 'activated'
                            ? 'Airtel Money activated'
                            : 'Customer declined Airtel Money',
                        'linking_request' => ($data['request_type'] ?? '') . ' requested',
                        'staff_accounts' => ($data['response'] ?? '') === 'setup_required'
                            ? 'Staff accounts set up'
                            : 'No extra staff accounts needed',
                        default => 'Saved',
                    };
                }

                $isCurrent = ! $done && ! $currentFound && $session->status === 'in_progress';

                if ($isCurrent) {
                    $currentFound = true;
                }

                $tone = $done
                    ? 'border-ledger/30 bg-ledger-soft'
                    : ($isCurrent ? 'border-cobalt bg-cobalt-soft' : 'border-hairline bg-slip');

                $badge = $done
                    ? 'bg-ledger text-white'
                    : ($isCurrent ? 'bg-cobalt text-white' : 'border border-hairline bg-slip text-muted');

                $status = $done
                    ? $summary . ', ' . \Carbon\Carbon::parse($p->completed_at)->setTimezone('Africa/Nairobi')->format('j M, H:i')
                    : ($isCurrent ? 'Current step' : 'Not started');
            @endphp

            <div class="flex items-start gap-3 rounded-xl border p-3 {{ $tone }}">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-bold {{ $badge }}">
                    {{ $done ? '✓' : $step->order_number }}
                </div>

                <div>
                    <div class="text-[15px] font-semibold leading-snug">{{ $step->title }}</div>
                    <div class="mt-0.5 text-sm text-muted">{{ $status }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- What the agent asked the AI -->
    <h2 class="mt-8 text-[18px] font-bold">Questions the agent asked the AI</h2>

    <div class="mt-3 space-y-3">
        @forelse ($chat as $log)
            <div class="rounded-2xl border border-hairline bg-slip p-4">
                <div class="text-xs text-muted">
                    {{ $log->step?->title ?? 'General question' }},
                    {{ \Carbon\Carbon::parse($log->created_at)->setTimezone('Africa/Nairobi')->format('j M, H:i') }}
                </div>

                <div class="mt-2 text-[15px] font-semibold leading-snug">{{ $log->question }}</div>

                <div class="mt-2 whitespace-pre-line text-[15px] leading-snug text-ink/80">{{ $log->answer }}</div>
            </div>
        @empty
            <p class="rounded-2xl bg-slip p-4 text-[15px] text-muted">The agent hasn't asked the AI anything yet.</p>
        @endforelse
    </div>

</div>
@include('partials.tabbar')
@endsection
