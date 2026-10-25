@extends('layouts.app')

@section('title', 'StepWise: profile')

@section('content')
<div
    class="mx-auto min-h-[100dvh] max-w-[480px] bg-counter px-5 sm:shadow-xl"
    style="padding-top: calc(env(safe-area-inset-top) + 20px); padding-bottom: calc(env(safe-area-inset-bottom) + 96px);">

    <h1 class="text-[30px] font-extrabold leading-[1.08] tracking-tight">Profile</h1>

    @if (session('status'))
        <p class="mt-4 rounded-2xl bg-ledger-soft p-3 text-[15px] font-semibold text-ledger" role="status">
            {{ session('status') }}
        </p>
    @endif

    <!-- Who you are -->
    <div class="mt-5 flex items-center gap-4 rounded-2xl bg-slip p-4">
        <div class="relative h-16 w-16 shrink-0">
            <!-- First letter of the name; covered by the Google photo when there is one -->
            <div class="flex h-16 w-16 items-center justify-center rounded-full bg-cobalt text-[24px] font-extrabold text-white">
                {{ strtoupper(mb_substr($user->name, 0, 1)) }}
            </div>

            @if ($user->avatar)
                <!-- no-referrer: Google sometimes refuses photo requests that carry a referrer.
                     If the photo cannot load (e.g. offline), it removes itself and the letter shows. -->
                <img src="{{ $user->avatar }}" alt="" referrerpolicy="no-referrer"
                     onerror="this.remove()"
                     class="absolute inset-0 h-16 w-16 rounded-full object-cover">
            @endif
        </div>

        <div class="min-w-0">
            <div class="truncate text-[18px] font-bold">{{ $user->name }}</div>
            <div class="truncate text-sm text-muted">{{ $user->email }}</div>
            <span class="mt-1 inline-block rounded-full bg-cobalt-soft px-3 py-0.5 text-xs font-semibold text-cobalt-deep">
                {{ ucfirst($user->role) }}
            </span>
        </div>
    </div>

    @if ($agent)
        <!-- Region (the agent can change it) -->
        <form method="POST" action="{{ url('/profile/region') }}" class="mt-3 rounded-2xl bg-slip p-4">
            @csrf
            <label for="region" class="text-sm text-muted">Region</label>

            <div class="mt-1 flex gap-2">
                <input id="region" name="region" type="text" maxlength="100"
                       value="{{ old('region', $agent->region) }}" placeholder="e.g. Nairobi CBD"
                       class="h-12 min-w-0 flex-1 rounded-xl border border-hairline bg-counter px-3 text-[16px]">
                <button type="submit"
                        class="h-12 shrink-0 rounded-xl bg-cobalt px-4 text-[15px] font-bold text-white">
                    Save
                </button>
            </div>

            @error('region')
                <p class="mt-2 text-sm font-semibold text-red-700">{{ $message }}</p>
            @enderror
        </form>

        <!-- Your numbers -->
        <h2 class="mt-7 text-[15px] font-semibold">Your numbers</h2>

        <div class="mt-2 grid grid-cols-3 gap-2">
            <div class="rounded-xl bg-slip p-3">
                <div class="text-[24px] font-extrabold leading-none text-ledger">{{ $stats['completed'] }}</div>
                <div class="mt-1 text-xs text-muted">Gigs completed</div>
            </div>
            <div class="rounded-xl bg-slip p-3">
                <div class="text-[24px] font-extrabold leading-none">{{ $stats['steps_saved'] }}</div>
                <div class="mt-1 text-xs text-muted">Steps saved</div>
            </div>
            <div class="rounded-xl bg-slip p-3">
                <div class="text-[24px] font-extrabold leading-none text-cobalt">{{ $stats['in_progress'] }}</div>
                <div class="mt-1 text-xs text-muted">In progress</div>
            </div>
        </div>
    @endif

    <!-- This device -->
    <h2 class="mt-7 text-[15px] font-semibold">This device</h2>

    <div class="mt-2 divide-y divide-hairline rounded-2xl bg-slip">

        <!-- Sync status, read from the wizard's offline queue -->
        <div class="flex items-center justify-between gap-3 p-4">
            <div>
                <div class="text-[15px] font-semibold">Sync</div>
                <div id="sync-status" class="text-sm text-muted">Checking...</div>
            </div>
            <a id="sync-open" href="#"
               class="tap hidden shrink-0 items-center rounded-xl bg-amber px-3 text-sm font-bold text-ink">
                Sync now
            </a>
        </div>

        <!-- Install hint -->
        <div class="flex items-center justify-between gap-3 p-4">
            <div class="min-w-0">
                <div class="text-[15px] font-semibold">App</div>
                <div id="install-text" class="text-sm leading-snug text-muted">
                    Add StepWise to your home screen from your browser's menu.
                </div>
            </div>
            <button id="install-button" type="button"
                    class="hidden shrink-0 rounded-xl bg-cobalt px-3 text-sm font-bold text-white">
                Install app
            </button>
        </div>
    </div>

    <form method="POST" action="{{ url('/logout') }}" class="mt-7">
        @csrf
        <button type="submit"
                class="h-14 w-full rounded-xl border border-hairline bg-slip text-[17px] font-bold text-red-700">
            Log out
        </button>
    </form>

</div>

@include('partials.tabbar')
@endsection

@push('scripts')
<script>
    // ---- Install hint ----
    // Chrome/Android offers a real install prompt; iPhone needs Share > Add to Home Screen.
    (function () {
        const text = document.getElementById('install-text');
        const button = document.getElementById('install-button');

        const installed = window.navigator.standalone === true ||
            window.matchMedia('(display-mode: standalone)').matches;
        const isIos = /iphone|ipad|ipod/i.test(navigator.userAgent) ||
            (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);

        if (installed) {
            text.textContent = 'Installed. You are using the app.';
            return;
        }

        if (isIos) {
            text.textContent = 'In Safari, tap Share, then Add to Home Screen.';
            return;
        }

        let installEvent = null;

        window.addEventListener('beforeinstallprompt', (event) => {
            event.preventDefault(); // keep it for our own button
            installEvent = event;
            text.textContent = 'Install StepWise for full screen and offline use.';
            button.classList.remove('hidden');
        });

        button.addEventListener('click', async () => {
            if (!installEvent) return;
            installEvent.prompt();
            await installEvent.userChoice;
            installEvent = null;
            button.classList.add('hidden');
        });

        window.addEventListener('appinstalled', () => {
            text.textContent = 'Installed. Open StepWise from your home screen.';
            button.classList.add('hidden');
        });
    })();

    // ---- Sync status ----
    // window.readSyncQueue comes from /js/sync-status.js, which runs before DOMContentLoaded.
    document.addEventListener('DOMContentLoaded', async () => {
        const status = document.getElementById('sync-status');
        const link = document.getElementById('sync-open');

        const queue = window.readSyncQueue ? await window.readSyncQueue() : [];
        const offline = !navigator.onLine;

        if (!queue.length) {
            status.textContent = offline
                ? "You're offline. Nothing is waiting to sync."
                : 'All your work is synced.';
            return;
        }

        status.textContent = (queue.length === 1 ? '1 step' : queue.length + ' steps') +
            ' saved on this device, waiting to sync.';

        // Opening that onboarding sends its queued steps (when online)
        if (!offline) {
            link.href = '/onboarding/' + queue[0].sessionId;
            link.classList.remove('hidden');
            link.classList.add('inline-flex');
        }
    });
</script>
@endpush
