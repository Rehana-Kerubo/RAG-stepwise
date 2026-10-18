@extends('layouts.app')

@section('title', 'StepWise: choose agent')

@section('content')
<div
    class="mx-auto min-h-[100dvh] max-w-[480px] bg-counter px-5 sm:shadow-xl"
    style="padding-top: calc(env(safe-area-inset-top) + 28px); padding-bottom: calc(env(safe-area-inset-bottom) + 28px);">

    <h1 class="text-[32px] font-extrabold leading-[1.05] tracking-tight">StepWise</h1>
    <p class="mt-2 text-[16px] leading-snug text-muted">Choose who is using the app.</p>

    <div class="mt-7 space-y-3">
        @forelse ($agents as $agent)
            <a href="{{ url('/agent/' . $agent->id) }}"
               class="block rounded-2xl border border-hairline bg-slip p-4">
                <div class="text-[18px] font-bold">{{ $agent->name }}</div>
                <div class="mt-0.5 text-sm text-muted">
                    {{ $agent->region ?? 'No region set' }}
                </div>
            </a>
        @empty
            <p class="rounded-2xl bg-amber-soft p-4 text-[15px] leading-snug">
                No active agents yet. Add one in the database, then reload this page.
            </p>
        @endforelse
    </div>

</div>
@endsection