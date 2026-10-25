@extends('layouts.app')

@section('title', 'StepWise: step difficulty')

@section('content')
<div class="mx-auto min-h-[100dvh] max-w-[720px] bg-counter px-5 sm:shadow-xl"
     style="padding-top: calc(env(safe-area-inset-top) + 12px); padding-bottom: calc(env(safe-area-inset-bottom) + 96px);">

    <a href="{{ url('/manager') }}" class="tap -ml-1 inline-flex items-center gap-1 text-sm font-semibold text-cobalt-deep">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M15 18l-6-6 6-6"/>
        </svg>
        Overview
    </a>

    <h1 class="mt-2 text-[30px] font-extrabold leading-[1.08] tracking-tight">Step difficulty</h1>
    <p class="mt-1 text-[15px] leading-snug text-muted">
        Which steps confuse agents: AI questions asked, alerts raised and typical time spent, per step.
    </p>

    <!-- Gig picker -->
    <div class="mt-5 flex flex-wrap gap-2">
        @foreach ($gigs as $g)
            <a href="{{ url('/manager/insights?gig=' . $g->slug) }}"
               class="tap inline-flex items-center rounded-full px-4 text-sm font-semibold {{ $gig && $g->id === $gig->id ? 'bg-ink text-white' : 'border border-hairline bg-slip' }}">
                {{ $g->name }}
            </a>
        @endforeach
    </div>

    @if (! $gig)
        <p class="mt-6 rounded-2xl bg-slip p-4 text-[15px] text-muted">No gigs with steps yet.</p>
    @else
        @php
            // Each measure is scaled to its own largest value, so bars of different
            // measures are never compared on one shared scale.
            $maxQuestions = max(1, $rows->max('questions'));
            $maxAlerts = max(1, $rows->max('alerts'));
            $maxMinutes = max(1, (float) $rows->max('minutes'));

            $formatMinutes = fn ($m) => $m === null ? 'No data' : ($m < 1 ? 'under 1 min' : rtrim(rtrim(number_format($m, 1), '0'), '.') . ' min');
        @endphp

        <!-- Headline: the hardest step -->
        @if ($worst)
            <div class="mt-5 rounded-2xl border-2 border-amber bg-amber-soft p-4">
                <div class="text-xs font-bold uppercase tracking-wide text-ink/70">Hardest step</div>
                <div class="mt-1 text-[20px] font-extrabold leading-snug">
                    {{ $worst['step']->order_number }}. {{ $worst['step']->title }}
                </div>
                <p class="mt-1 text-[15px] text-ink/80">
                    {{ $worst['questions'] }} AI {{ \Illuminate\Support\Str::plural('question', $worst['questions']) }},
                    {{ $worst['alerts'] }} {{ \Illuminate\Support\Str::plural('alert', $worst['alerts']) }},
                    typical time {{ $formatMinutes($worst['minutes']) }}.
                </p>
            </div>
        @else
            <p class="mt-5 rounded-2xl bg-slip p-4 text-[15px] text-muted">
                No questions, alerts or saved steps for this gig yet.
            </p>
        @endif

        <!-- One card per step, three bars each -->
        <div class="mt-5 space-y-2">
            @foreach ($rows as $row)
                @php
                    $isWorst = $worst && $worst['step']->id === $row['step']->id;

                    $bars = [
                        ['label' => 'AI questions', 'value' => $row['questions'], 'text' => $row['questions'],
                         'width' => $row['questions'] / $maxQuestions * 100, 'color' => 'bg-cobalt'],
                        ['label' => 'Alerts', 'value' => $row['alerts'], 'text' => $row['alerts'],
                         'width' => $row['alerts'] / $maxAlerts * 100, 'color' => 'bg-amber'],
                        ['label' => 'Typical time', 'value' => $row['minutes'], 'text' => $formatMinutes($row['minutes']),
                         'width' => (float) $row['minutes'] / $maxMinutes * 100, 'color' => 'bg-muted'],
                    ];
                @endphp

                <div class="rounded-2xl border {{ $isWorst ? 'border-2 border-amber bg-amber-soft/50' : 'border-hairline bg-slip' }} p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="text-[15px] font-bold leading-snug">
                            {{ $row['step']->order_number }}. {{ $row['step']->title }}
                        </div>
                        @if ($isWorst)
                            <span class="shrink-0 rounded-full bg-amber px-2.5 py-0.5 text-xs font-bold text-ink">&#9888; Hardest</span>
                        @endif
                    </div>

                    <dl class="mt-3 space-y-1.5">
                        @foreach ($bars as $bar)
                            <div class="grid grid-cols-[96px_1fr_84px] items-center gap-2 text-sm"
                                 title="{{ $bar['label'] }}: {{ $bar['text'] }}">
                                <dt class="text-muted">{{ $bar['label'] }}</dt>
                                <dd class="h-2 rounded-full bg-hairline/60">
                                    @if ($bar['value'])
                                        <div class="h-2 rounded-full {{ $bar['color'] }}"
                                             style="width: {{ max(3, round($bar['width'])) }}%"></div>
                                    @endif
                                </dd>
                                <dd class="text-right font-semibold tabular-nums">{{ $bar['text'] }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            @endforeach
        </div>

        <p class="mt-5 text-xs leading-snug text-muted">
            How this is counted: questions from the AI chat log and alerts raised on each step, all onboardings of this gig.
            Time on a step runs from the previous step's save (or the start, for step 1) to this step's save; the typical
            time is the median, so one long break does not distort it. Steps saved offline are timed when they sync.
        </p>
    @endif

</div>

@include('partials.tabbar')
@endsection
