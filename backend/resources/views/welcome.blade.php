@extends('layouts.app')

@section('title', 'StepWise: log in')

@section('content')
<div
    class="mx-auto min-h-[100dvh] max-w-[480px] bg-counter px-5 sm:shadow-xl"
    style="padding-top: calc(env(safe-area-inset-top) + 28px); padding-bottom: calc(env(safe-area-inset-bottom) + 28px);">

    <h1 class="text-[32px] font-extrabold leading-[1.05] tracking-tight">StepWise</h1>
    <p class="mt-2 text-[16px] leading-snug text-muted">Log in to continue your onboardings.</p>

    @if ($errors->any())
        <p class="mt-6 rounded-2xl bg-red-100 p-4 text-[15px] leading-snug text-red-800" role="alert">
            {{ $errors->first() }}
        </p>
    @endif



    @if ($passwordLogin)
        <!-- Demo accounts only. This form and its route exist only when APP_ENV=local. -->
        <div class="mt-8 rounded-2xl border border-hairline bg-slip p-4">
            <div class="text-sm font-semibold text-muted">Demo login (local only)</div>

            <form method="POST" action="{{ url('/login') }}" class="mt-3 space-y-3">
                @csrf
                <input type="email" name="email" value="{{ old('email') }}" required
                       placeholder="Email" autocomplete="username"
                       class="w-full rounded-xl border border-hairline px-3 py-3 text-[16px]">
                <input type="password" name="password" required
                       placeholder="Password" autocomplete="current-password"
                       class="w-full rounded-xl border border-hairline px-3 py-3 text-[16px]">
                <button type="submit"
                        class="w-full rounded-xl border border-hairline bg-counter px-4 py-3 text-[16px] font-semibold">
                    Log in
                </button>
            </form>
        </div>
    @endif

    <a href="{{ url('/auth/google') }}"
       class="mt-7 flex w-full items-center justify-center gap-3 rounded-2xl bg-cobalt px-4 py-4 text-[17px] font-bold text-white">
        <span class="flex h-7 w-7 items-center justify-center rounded-full bg-white">
            <img src="/icons/google.png" alt="" width="18" height="18" class="h-[18px] w-[18px]">
        </span>
        Sign in with Google
    </a>

    <p class="mt-4 text-center text-xs leading-snug text-muted">
        By continuing, you agree to our
        <a href="{{ url('/terms') }}" class="font-semibold underline">Terms of use</a> and
        <a href="{{ url('/privacy') }}" class="font-semibold underline">Privacy policy</a>.
    </p>
    <!-- Install hint for iPhone users (iOS has no install button, so we explain the steps) -->
    <div id="ios-install" class="mt-8 hidden rounded-2xl border border-hairline bg-slip p-4">
        <div class="text-[15px] font-bold">Install StepWise on your iPhone</div>
        <p class="mt-1 text-[15px] leading-snug text-muted">
            In Safari, tap
            <svg class="mx-0.5 inline-block align-[-3px] text-cobalt" width="18" height="18" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-label="Share">
                <path d="M12 3v12M8 7l4-4 4 4"/><path d="M5 12v8h14v-8"/>
            </svg>
            <strong class="text-ink">Share</strong>, then <strong class="text-ink">Add to Home Screen</strong>.
            It opens full screen, like a normal app.
        </p>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Show the hint on iPhone/iPad, but not once the app is already installed
    (function () {
        const isIos = /iphone|ipad|ipod/i.test(navigator.userAgent) ||
            (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1); // iPadOS reports as a Mac
        const installed = window.navigator.standalone === true ||
            window.matchMedia('(display-mode: standalone)').matches;

        if (isIos && !installed) {
            document.getElementById('ios-install').classList.remove('hidden');
        }
    })();
</script>
@endpush
