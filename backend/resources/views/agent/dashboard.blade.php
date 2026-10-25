@extends('layouts.app')

@section('title', 'StepWise: ' . $agent->name)

@section('content')
<div
    class="mx-auto min-h-[100dvh] max-w-[480px] bg-counter px-5 sm:shadow-xl"
    style="padding-top: calc(env(safe-area-inset-top) + 20px); padding-bottom: calc(env(safe-area-inset-bottom) + 96px);">

    <p class="text-[15px] text-muted">Hi,</p>
    <h1 class="text-[30px] font-extrabold leading-[1.08] tracking-tight">{{ $agent->name }}</h1>
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

    <!-- Continue the newest unfinished onboarding -->
    @if ($current)
        <h2 class="mt-7 text-[15px] font-semibold">Continue where you left off</h2>
        @include('agent._session-card', ['session' => $current])
    @endif

    <!-- Start a new onboarding: choose the gig first -->
    <form method="POST" action="{{ url('/agent/sessions') }}" class="mt-7">
        @csrf

        <h2 class="text-[15px] font-semibold">Start a new onboarding</h2>

        @error('gig_id')
            <p class="mt-2 rounded-2xl bg-red-100 p-3 text-[15px] font-semibold text-red-800" role="alert">{{ $message }}</p>
        @enderror

        @if ($gigs->isEmpty())
            <div class="mt-2 rounded-2xl bg-slip p-5 text-center">
                <div class="text-[17px] font-bold">No gig yet</div>
                <p class="mt-1 text-[15px] leading-snug text-muted">
                    Your manager hasn't assigned you a gig yet. You'll see it here as soon as they do.
                </p>
            </div>
        @else
            <div class="mt-2 space-y-2">
                @foreach ($gigs as $gig)
                    <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-hairline bg-slip p-4 has-[:checked]:border-cobalt has-[:checked]:bg-cobalt-soft">
                        <input type="radio" name="gig_id" value="{{ $gig->id }}"
                               class="mt-1 h-5 w-5 shrink-0 accent-[#2A3EDC]"
                               {{ $loop->first ? 'checked' : '' }}>

                        <span class="min-w-0">
                            <span class="block text-[16px] font-bold leading-snug">{{ $gig->name }}</span>

                            <span class="block text-sm text-muted">
                                @if ($gig->client){{ $gig->client }} &middot; @endif{{ $stepCounts[$gig->id] ?? 0 }} steps
                            </span>

                            @if ($gig->description)
                                <span class="mt-1 block text-sm leading-snug text-muted">{{ $gig->description }}</span>
                            @endif
                        </span>
                    </label>
                @endforeach
            </div>

            <button type="submit"
                    class="mt-3 h-14 w-full rounded-xl bg-cobalt text-[17px] font-bold text-white active:bg-cobalt-deep">
                Start onboarding
            </button>
        @endif
    </form>

</div>

@include('partials.tabbar')
@endsection
