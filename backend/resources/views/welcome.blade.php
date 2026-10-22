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
</div>
@endsection
