@extends('layouts.app')

@section('title', 'StepWise: profile')

@section('content')
<div
    class="mx-auto min-h-[100dvh] max-w-[480px] bg-counter px-5 sm:shadow-xl"
    style="padding-top: calc(env(safe-area-inset-top) + 20px); padding-bottom: calc(env(safe-area-inset-bottom) + 96px);">

    <div class="flex items-center justify-between">
    <h1 class="text-[30px] font-extrabold leading-[1.08] tracking-tight">Profile</h1>

    <button id="settings-open" type="button" aria-label="Settings"
        class="flex h-11 w-11 items-center justify-center rounded-xl border border-hairline bg-slip">
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
         stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/>
        <circle cx="12" cy="12" r="3"/>
        </svg>
    </button>
    </div>

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

    <form method="POST" action="{{ url('/logout') }}" class="mt-7" data-logout>
        @csrf
        <button type="submit"
                class="h-14 w-full rounded-xl border border-hairline bg-slip text-[17px] font-bold text-red-700">
            Log out
        </button>
    </form>

</div>
<style>
    #settings-scrim {
        position: fixed; inset: 0; z-index: 55;
        background: rgba(23, 25, 31, 0.45);
        opacity: 0; pointer-events: none;
        transition: opacity 0.2s ease;
    }
    #settings-scrim.open { opacity: 1; pointer-events: auto; }

    #settings-sheet {
        position: fixed; left: 0; right: 0; bottom: 0;
        margin: 0 auto; max-width: 480px; z-index: 60;
        background: #FFFFFF;
        border-radius: 24px 24px 0 0;
        padding-bottom: calc(env(safe-area-inset-bottom) + 16px);
        box-shadow: 0 -12px 40px rgba(23, 25, 31, 0.18);
        transform: translateY(100%);
        visibility: hidden;
        transition: transform 0.26s ease, visibility 0s linear 0.26s;
    }
    #settings-sheet.open {
        transform: translateY(0); visibility: visible;
        transition: transform 0.26s ease, visibility 0s;
    }

    /* Wider screens (manager browser): centred card instead of bottom sheet */
    @media (min-width: 640px) {
        #settings-sheet {
            bottom: auto; top: 50%; max-width: 420px;
            border-radius: 24px; opacity: 0;
            transform: translateY(-46%);
            transition: transform 0.2s ease, opacity 0.2s ease, visibility 0s linear 0.2s;
        }
        #settings-sheet.open {
            transform: translateY(-50%); opacity: 1;
            transition: transform 0.2s ease, opacity 0.2s ease, visibility 0s;
        }
    }
</style>

<div id="settings-scrim"></div>

<section id="settings-sheet" role="dialog" aria-modal="true" aria-labelledby="settings-title">

    <div id="settings-handle" style="touch-action: none;">
        <div class="mx-auto mt-3 h-1.5 w-10 rounded-full bg-hairline sm:hidden"></div>

        <div class="flex items-center justify-between px-5 pb-2 pt-3">
            <h2 id="settings-title" class="text-xl font-extrabold tracking-tight">Settings</h2>
            <button id="settings-close" type="button"
                class="h-11 rounded-xl px-3 text-sm font-semibold text-cobalt-deep">Done</button>
        </div>
    </div>

    <div class="px-5">
        @unless (auth()->user()->isManager())
            <h3 class="mt-2 text-[15px] font-semibold">Permissions</h3>

            <div class="mt-3 flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-[15px] font-medium">Camera</p>
                    <p id="cam-help" class="mt-0.5 text-sm leading-snug text-muted"></p>
                </div>
                <span id="cam-badge"
                      class="shrink-0 rounded-full bg-hairline px-3 py-1 text-xs font-bold text-muted">Checking</span>
            </div>

            <details class="mt-3 text-sm">
                <summary class="cursor-pointer font-semibold text-cobalt-deep">How to change this</summary>
                <p id="cam-steps" class="mt-2 leading-snug text-muted"></p>
            </details>
        @endunless
        <h3 class="mt-5 text-[15px] font-semibold">About</h3>

        <div class="mt-2 divide-y divide-hairline rounded-2xl bg-counter">
            <a href="{{ url('/privacy') }}" class="flex min-h-[48px] items-center justify-between px-4 text-[15px] font-medium">
                Privacy policy
                <span class="text-muted" aria-hidden="true">›</span>
            </a>
            <a href="{{ url('/terms') }}" class="flex min-h-[48px] items-center justify-between px-4 text-[15px] font-medium">
                Terms of use
                <span class="text-muted" aria-hidden="true">›</span>
            </a>
            <div class="flex min-h-[48px] items-center justify-between px-4 text-[15px]">
                <span class="text-muted">StepWise v{{ config('app.version') }}</span>
            </div>
        </div>
    </div>
</section>

<script>
    (function () {
        const sheet = document.getElementById('settings-sheet');
        const scrim = document.getElementById('settings-scrim');
        const opener = document.getElementById('settings-open');

        function open() {
            sheet.classList.add('open');
            scrim.classList.add('open');
            document.body.style.overflow = 'hidden';
        }
        function close() {
            sheet.classList.remove('open');
            scrim.classList.remove('open');
            document.body.style.overflow = '';
            opener.focus();
        }

        opener.addEventListener('click', open);
        scrim.addEventListener('click', close);
        document.getElementById('settings-close').addEventListener('click', close);
        document.addEventListener('keydown', e => { if (e.key === 'Escape') close(); });

        // Swipe down on the grip or title bar to close (phones only)
        const handle = document.getElementById('settings-handle');
        let startY = null, distance = 0;

        handle.addEventListener('pointerdown', (e) => {
            if (window.innerWidth >= 640 || e.target.closest('button')) return;
                startY = e.clientY;
                distance = 0;
            sheet.style.transition = 'none';
            handle.setPointerCapture(e.pointerId);
        });

        handle.addEventListener('pointermove', (e) => {
            if (startY === null) return;
            distance = Math.max(0, e.clientY - startY);
            sheet.style.transform = `translateY(${distance}px)`;
        });

        function finishDrag() {
            if (startY === null) return;
            const shouldClose = distance > 110;
            startY = null;
            distance = 0;
            sheet.style.transition = '';
            sheet.style.transform = '';
            if (shouldClose) close();
        }

        handle.addEventListener('pointerup', finishDrag);
        handle.addEventListener('pointercancel', finishDrag);
    })();
</script>

@unless (auth()->user()->isManager())
<script>
    (async function () {
const badge = document.getElementById('cam-badge');
const help = document.getElementById('cam-help');

let state = 'prompt';
try {
    if (navigator.permissions) {
        state = (await navigator.permissions.query({ name: 'camera' })).state;
    }
} catch (e) {}

const remembered = localStorage.getItem('camera_ok');

const set = (label, classes, text) => {
    badge.textContent = label;
    badge.className = 'shrink-0 rounded-full px-3 py-1 text-xs font-bold ' + classes;
    help.textContent = text;
};

if (state === 'denied') {
    set('Blocked', 'bg-red-100 text-red-800', 'Camera is off for StepWise.');
} else if (state === 'granted' || remembered) {
    set('Allowed', 'bg-ledger-soft text-ledger', 'Used for photo steps.');
} else {
    set('Not asked yet', 'bg-amber-soft text-ink', 'StepWise asks at your first photo step.');
}

const isIOS = /iPhone|iPad|iPod/.test(navigator.userAgent);
document.getElementById('cam-steps').textContent = isIOS
    ? "Open your iPhone Settings, tap Safari (on newer iOS: Apps, then Safari), tap Camera, and choose Ask or Allow. Then reopen StepWise."
    : "Open your phone Settings, tap Apps, then StepWise (or your browser), then Permissions, then Camera, and choose Allow. Then reopen StepWise.";
})();
</script>
@endunless

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
