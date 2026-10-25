{{-- One onboarding as a tappable card. Needs $session and $stepCounts. --}}
@php
    $saved = (int) $session->saved_steps;
    $total = (int) ($stepCounts[$session->gig_id] ?? 0);

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
   class="mt-3 block rounded-2xl border border-hairline bg-slip p-4 active:bg-counter">

    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <div class="text-[17px] font-bold leading-snug">{{ $session->gig->name ?? 'Onboarding' }}</div>
            <div class="mt-0.5 text-sm text-muted">#{{ $session->id }} &middot; Started {{ $startedAt }}</div>
        </div>

        <span class="shrink-0 rounded-full px-3 py-1 text-xs font-semibold {{ $pill }}">
            {{ $label }}
        </span>
    </div>

    <!-- The same ruler as the onboarding screen: one segment per step of this gig -->
    <div class="mt-4 flex items-center gap-1" aria-hidden="true">
        @for ($i = 1; $i <= $total; $i++)
            <span class="h-2.5 flex-1 rounded-[3px] {{ $i <= $saved ? 'bg-ledger' : 'bg-hairline' }}"></span>
        @endfor
    </div>

    <div class="mt-3 flex items-center justify-between gap-3 text-sm">
        <span class="text-muted">
            {{ $saved }} of {{ $total }} steps saved{{ $lastActivity ? ', last activity ' . $lastActivity : '' }}
        </span>

        @if ($session->open_alerts > 0)
            <span class="shrink-0 rounded-full bg-amber-soft px-3 py-1 text-xs font-semibold">
                {{ $session->open_alerts }} {{ \Illuminate\Support\Str::plural('alert', $session->open_alerts) }}
            </span>
        @endif
    </div>

</a>
