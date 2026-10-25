@extends('layouts.app')

@section('title', 'StepWise: my onboardings')

@section('content')
<div
    class="mx-auto min-h-[100dvh] max-w-[480px] bg-counter px-5 sm:shadow-xl"
    style="padding-top: calc(env(safe-area-inset-top) + 20px); padding-bottom: calc(env(safe-area-inset-bottom) + 96px);">

    <h1 class="text-[30px] font-extrabold leading-[1.08] tracking-tight">My Onboardings</h1>
    <p class="mt-1 text-[15px] text-muted">Tap one to continue or review it.</p>

    @forelse ($sessions as $session)
        @include('agent._session-card')
    @empty
        <div class="mt-5 rounded-2xl bg-slip p-5 text-center">
            <p class="text-[15px] leading-snug text-muted">No onboardings yet.</p>
            <a href="{{ url('/agent') }}"
               class="tap mt-3 inline-flex items-center rounded-xl bg-cobalt px-5 text-[15px] font-bold text-white">
                Start one from Home
            </a>
        </div>
    @endforelse

</div>

@include('partials.tabbar')
@endsection
