<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>StepWise</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#F2F3EF">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="StepWise">
    <link rel="apple-touch-icon" sizes="180x180" href="/icons/apple-touch-icon.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400;12..96,500;12..96,600;12..96,700;12..96,800&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        if (window.tailwind) {
            tailwind.config = {
                theme: {
                    extend: {
                        colors: {
                            counter: '#F2F3EF',
                            slip: '#FFFFFF',
                            ink: '#17191F',
                            muted: '#5B6070',
                            hairline: '#D9DCE3',
                            cobalt: { DEFAULT: '#2A3EDC', soft: '#E8EBFC', deep: '#1B2AA3' },
                            ledger: { DEFAULT: '#0B7A52', soft: '#E4F4EC' },
                            amber: { DEFAULT: '#F2B01E', soft: '#FFF3D1' }
                        },
                        fontFamily: {
                            sans: ['"Bricolage Grotesque"', 'ui-sans-serif', 'system-ui', 'sans-serif']
                        }
                    }
                }
            };
        }
    </script>

    <style>
        html { -webkit-text-size-adjust: 100%; }
        body {
            -webkit-tap-highlight-color: transparent;
            font-family: "Bricolage Grotesque", ui-sans-serif, system-ui, sans-serif;
        }
        button, label, select { touch-action: manipulation; }

        /* Feel like an app: no text selection or long-press menu on controls */
        button, a, label {
            -webkit-user-select: none;
            user-select: none;
            -webkit-touch-callout: none;
        }

        /* Apple's minimum touch target is 44px */
        button, select { min-height: 44px; }

        /* Grey placeholder blocks shown while the steps load */
        .skeleton {
            display: block;
            border-radius: 8px;
            background: linear-gradient(90deg, #E4E6E1 25%, #EEF0EB 50%, #E4E6E1 75%);
            background-size: 200% 100%;
            animation: shimmer 1.2s linear infinite;
        }
        @keyframes shimmer { to { background-position: -200% 0; } }

        #app { min-height: 100vh; min-height: 100dvh; }

        /* Ruler segment for steps saved on the device but not yet synced */
        .queued-seg {
            background: repeating-linear-gradient(135deg, #F2B01E 0 4px, #FBE29A 4px 8px);
        }

        /* Bottom sheets (steps overview and chat) */
        .sheet {
            position: fixed;
            left: 0;
            right: 0;
            bottom: var(--kb, 0px);
            margin: 0 auto;
            max-width: 480px;
            height: 88vh;
            height: min(88dvh, calc(var(--vvh, 100dvh) - 12px));
            z-index: 50;
            display: flex;
            flex-direction: column;
            background: #FFFFFF;
            border-radius: 24px 24px 0 0;
            box-shadow: 0 -12px 40px rgba(23, 25, 31, 0.18);
            transform: translateY(100%);
            visibility: hidden;
            transition: transform 0.26s ease, visibility 0s linear 0.26s;
        }
        .sheet.open {
            transform: translateY(0);
            visibility: visible;
            transition: transform 0.26s ease, visibility 0s;
        }
        .sheet-handle { touch-action: none; cursor: grab; }

        #scrim {
            position: fixed;
            inset: 0;
            z-index: 40;
            background: rgba(23, 25, 31, 0.45);
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s ease;
        }
        #scrim.open { opacity: 1; pointer-events: auto; }

        #toast {
            position: fixed;
            left: 0;
            right: 0;
            margin: 0 auto;
            max-width: 480px;
            z-index: 35;
            padding: 0 16px;
            bottom: calc(env(safe-area-inset-bottom) + 92px);
            pointer-events: none;
            opacity: 0;
            transform: translateY(8px);
            transition: opacity 0.2s ease, transform 0.2s ease;
        }
        #toast.show { opacity: 1; transform: translateY(0); pointer-events: auto; }

        @media (prefers-reduced-motion: reduce) {
            .sheet, .sheet.open, #scrim, #toast { transition: none; }
            .skeleton { animation: none; }
        }

        button:focus-visible, select:focus-visible, input:focus-visible, a:focus-visible {
            outline: 3px solid #2A3EDC;
            outline-offset: 2px;
        }
    </style>
</head>

<body class="bg-[#E4E6E1] font-sans text-ink antialiased">

<div id="app" class="relative mx-auto max-w-[480px] bg-counter sm:shadow-xl">

    <!-- =========================================================
         HEADER: brand, saved count, and the progress ruler
    ========================================================== -->
    <header
        class="relative bg-counter px-5 pb-3"
        style="padding-top: calc(env(safe-area-inset-top) + 14px);">

        <div class="flex items-center justify-between">
            <a id="back-link"
               href="{{ url('/agent') }}"
               class="-ml-1 flex min-h-[44px] items-center gap-1.5 pr-2"
               aria-label="Back to dashboard">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M15 18l-6-6 6-6"/>
                </svg>
                <span class="text-[19px] font-extrabold tracking-tight">StepWise</span>
            </a>

            <button
                id="steps-open"
                type="button"
                class="flex h-11 items-center gap-2 rounded-xl border border-hairline bg-slip px-3 text-sm font-semibold"
                aria-label="Show all steps">
                <span id="progress-text">Loading</span>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>
                </svg>
            </button>
        </div>

        <div id="ruler" class="mt-3 flex items-center gap-1" aria-hidden="true"></div>

        <div class="mt-2 flex items-center justify-between gap-2">
            <p id="step-counter" class="text-sm font-medium text-muted">
                <span class="skeleton h-4 w-24" aria-hidden="true"></span>
                <span class="sr-only">Loading</span>
            </p>

            <!-- Steps saved on this device that have not reached the server yet -->
            <span id="sync-chip"
                  class="hidden shrink-0 rounded-full bg-amber px-3 py-1 text-xs font-bold text-ink"></span>
        </div>
    </header>

    <!-- Offline / sync status (hidden when there is nothing to report) -->
    <div id="offline-banner" class="hidden" role="status"></div>


    <!-- =========================================================
         CURRENT STEP
    ========================================================== -->
    <main class="px-5 pt-4" style="padding-bottom: calc(env(safe-area-inset-bottom) + 190px);">

        <h2
            id="step-title"
            class="text-[30px] font-extrabold leading-[1.08] tracking-tight">
            <span class="skeleton h-8 w-4/5" aria-hidden="true"></span>
            <span class="skeleton mt-2 h-8 w-1/2" aria-hidden="true"></span>
            <span class="sr-only">Loading step</span>
        </h2>

        <section class="mt-5">
            <h3 class="text-[15px] font-semibold">What to do</h3>
            <p
                id="step-instructions"
                class="mt-2 border-l-[3px] border-cobalt pl-3 text-[16px] leading-relaxed text-ink/80">
                <span class="skeleton h-4 w-full" aria-hidden="true"></span>
                <span class="skeleton mt-2 h-4 w-11/12" aria-hidden="true"></span>
                <span class="skeleton mt-2 h-4 w-2/3" aria-hidden="true"></span>
            </p>
        </section>

        <section class="mt-7">
            <h3 id="step-input-label" class="mb-2 text-[15px] font-semibold">Step submission</h3>

            <div id="step-input-container">
                <span class="skeleton h-14 w-full rounded-xl" aria-hidden="true"></span>
            </div>

            <p id="validation-hint" class="mt-2 text-sm leading-snug text-muted"></p>
            <p id="form-error" role="alert" class="mt-2 hidden text-sm font-semibold text-red-700"></p>
        </section>

        <section class="mt-7 rounded-2xl bg-cobalt-soft p-4">
            <h3 class="text-[15px] font-semibold text-cobalt-deep">Tip from StepWise</h3>

            <p
                id="guidance-label"
                class="mt-1 whitespace-pre-line text-[15px] leading-snug text-ink/80">
                <span class="skeleton h-4 w-full" aria-hidden="true"></span>
                <span class="skeleton mt-2 h-4 w-3/4" aria-hidden="true"></span>
            </p>

            <button
                id="help-button"
                type="button"
                class="mt-3 h-11 rounded-xl border border-cobalt/40 bg-slip px-4 text-[15px] font-semibold text-cobalt-deep">
                Ask about this step
            </button>
        </section>

    </main>


    <!-- =========================================================
         BOTTOM BAR (within thumb reach)
    ========================================================== -->
    <div
        class="fixed inset-x-0 bottom-0 z-30 mx-auto max-w-[480px] border-t border-hairline bg-counter px-4 pt-3"
        style="padding-bottom: calc(env(safe-area-inset-bottom) + 12px);">

        <div class="flex gap-3">

            <button
                id="chat-open"
                type="button"
                class="relative flex h-14 w-14 shrink-0 items-center justify-center rounded-xl border border-hairline bg-slip"
                aria-label="Ask StepWise">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12z"/>
                </svg>
                <span id="chat-dot"
                      class="absolute right-2 top-2 hidden h-3 w-3 rounded-full bg-cobalt ring-2 ring-slip"></span>
            </button>

            <button
                id="next-button"
                type="button"
                class="h-14 flex-1 rounded-xl bg-cobalt text-[17px] font-bold text-white disabled:bg-hairline disabled:text-muted">
                Save and continue
            </button>

        </div>
    </div>

    <!-- Short messages: saved, feedback ready -->
    <div id="toast" role="status">
        <div class="flex items-center justify-between gap-3 rounded-xl bg-ink px-4 py-3 text-sm text-white shadow-lg">
            <span id="toast-text"></span>
            <button id="toast-action" type="button" class="hidden shrink-0 font-bold text-amber">Read</button>
        </div>
    </div>

    <div id="scrim"></div>


    <!-- =========================================================
         SHEET: ALL STEPS
    ========================================================== -->
    <section
        id="steps-sheet"
        class="sheet"
        role="dialog"
        aria-modal="true"
        aria-labelledby="steps-sheet-title">

        <div class="sheet-handle">
            <div class="mx-auto mt-3 h-1.5 w-10 rounded-full bg-hairline"></div>

            <div class="flex items-center justify-between px-5 pb-3 pt-3">
                <h2 id="steps-sheet-title" class="text-xl font-extrabold tracking-tight">All steps</h2>
                <button data-close type="button"
                        class="h-11 rounded-xl px-3 text-sm font-semibold text-cobalt-deep">Close</button>
            </div>
        </div>

        <div id="steps-list"
             class="flex-1 space-y-2 overflow-y-auto px-5"
             style="padding-bottom: calc(env(safe-area-inset-bottom) + 20px);">
            <span class="skeleton h-14 w-full rounded-xl" aria-hidden="true"></span>
            <span class="skeleton h-14 w-full rounded-xl" aria-hidden="true"></span>
            <span class="skeleton h-14 w-full rounded-xl" aria-hidden="true"></span>
        </div>
    </section>


    <!-- =========================================================
         SHEET: ASK STEPWISE (AI chat)
    ========================================================== -->
    <section
        id="chat-panel"
        class="sheet"
        role="dialog"
        aria-modal="true"
        aria-labelledby="chat-title">

        <div class="sheet-handle">
            <div class="mx-auto mt-3 h-1.5 w-10 rounded-full bg-hairline"></div>

            <div class="flex items-center justify-between px-5 pb-3 pt-3">
                <h2 id="chat-title" class="text-xl font-extrabold tracking-tight">Ask StepWise</h2>
                <button data-close type="button"
                        class="h-11 rounded-xl px-3 text-sm font-semibold text-cobalt-deep">Close</button>
            </div>
        </div>

        <div id="chat-window" class="flex-1 space-y-3 overflow-y-auto px-4 py-2">
            <p id="chat-empty" class="px-2 text-center text-sm leading-snug text-muted">
                Ask anything about onboarding this merchant. Answers can take up to 30 seconds.
            </p>
        </div>

        <form
            id="chat-form"
            class="flex gap-2 border-t border-hairline p-3"
            style="padding-bottom: calc(env(safe-area-inset-bottom) + 12px);">

            <input
                id="question-input"
                type="text"
                placeholder="Ask about this step"
                class="h-12 min-w-0 flex-1 rounded-xl border border-hairline bg-slip px-4 text-[16px]"
                autocomplete="off"
            />

            <button
                type="submit"
                class="h-12 rounded-xl bg-cobalt px-5 text-[16px] font-bold text-white">
                Send
            </button>

        </form>
    </section>

</div>


<script>

    // ============================================================
    // SESSION
    // ============================================================

    const SESSION_ID = {{ $sessionId ?? 3 }};
    const DASHBOARD_URL = "{{ url('/agent') }}";
    // Laravel rejects POSTs without this token (CSRF protection).
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').content;
    const STEPS_CACHE_KEY = 'stepwise_steps_' + SESSION_ID;

    let steps = [];
    let currentStepIndex = 0;
    let finishedShown = false;


    // ============================================================
    // ELEMENTS
    // ============================================================

    const stepsList = document.getElementById('steps-list');

    const progressText = document.getElementById('progress-text');
    const ruler = document.getElementById('ruler');

    const stepCounter = document.getElementById('step-counter');
    const stepTitle = document.getElementById('step-title');
    const stepInstructions = document.getElementById('step-instructions');

    const stepInputContainer = document.getElementById('step-input-container');
    const stepInputLabel = document.getElementById('step-input-label');
    let stepInput = null;
    const validationHint = document.getElementById('validation-hint');
    const formError = document.getElementById('form-error');

    const guidanceLabel = document.getElementById('guidance-label');

    const nextButton = document.getElementById('next-button');
    const helpButton = document.getElementById('help-button');

    const offlineBanner = document.getElementById('offline-banner');
    const syncChip = document.getElementById('sync-chip');

    const stepsOpen = document.getElementById('steps-open');
    const chatOpen = document.getElementById('chat-open');
    const chatDot = document.getElementById('chat-dot');
    const stepsSheet = document.getElementById('steps-sheet');
    const chatSheet = document.getElementById('chat-panel');
    const scrim = document.getElementById('scrim');

    const toast = document.getElementById('toast');
    const toastText = document.getElementById('toast-text');
    const toastAction = document.getElementById('toast-action');

    let previewUrl = null;


    // ============================================================
    // SHEETS AND TOASTS
    // ============================================================

    let activeSheet = null;
    let lastFocused = null;
    let toastTimer = null;

    function openSheet(sheet) {

        if (activeSheet) closeSheet();

        lastFocused = document.activeElement;
        activeSheet = sheet;

        sheet.classList.add('open');
        scrim.classList.add('open');
        document.body.style.overflow = 'hidden';

        if (sheet === chatSheet) {
            chatDot.classList.add('hidden');
            hideToast();
            chatWindow.scrollTop = chatWindow.scrollHeight;
        }

        const closeButton = sheet.querySelector('[data-close]');
        if (closeButton) closeButton.focus();

    }

    function closeSheet() {

        if (!activeSheet) return;

        activeSheet.classList.remove('open');
        scrim.classList.remove('open');
        document.body.style.overflow = '';
        activeSheet = null;

        if (lastFocused && lastFocused.focus) lastFocused.focus();

    }

    scrim.addEventListener('click', closeSheet);

    document.querySelectorAll('[data-close]').forEach(button => {
        button.addEventListener('click', closeSheet);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closeSheet();
    });

    stepsOpen.addEventListener('click', () => openSheet(stepsSheet));
    chatOpen.addEventListener('click', () => openSheet(chatSheet));

    // Keep the sheets above the on-screen keyboard. Phones shrink the *visible*
    // area when the keyboard opens, so the sheets follow that area.
    function fitToKeyboard() {

        const vv = window.visualViewport;

        if (!vv) return;

        const keyboard = vv.scale > 1.01
            ? 0
            : Math.max(0, window.innerHeight - vv.height - vv.offsetTop);

        const root = document.documentElement.style;

        root.setProperty('--vvh', vv.height + 'px');
        root.setProperty('--kb', keyboard + 'px');

    }

    if (window.visualViewport) {
        window.visualViewport.addEventListener('resize', fitToKeyboard);
        window.visualViewport.addEventListener('scroll', fitToKeyboard);
        fitToKeyboard();
    }

    // Swipe down on the grip or title bar to close a sheet
    function enableSwipeToClose(sheet) {

        const handle = sheet.querySelector('.sheet-handle');

        let startY = null;
        let distance = 0;

        handle.addEventListener('pointerdown', (event) => {

            if (event.target.closest('button')) return; // let the Close button work normally

            startY = event.clientY;
            distance = 0;

            sheet.style.transition = 'none';
            handle.setPointerCapture(event.pointerId);

        });

        handle.addEventListener('pointermove', (event) => {

            if (startY === null) return;

            distance = Math.max(0, event.clientY - startY);

            sheet.style.transform = `translateY(${distance}px)`;

        });

        function finishDrag() {

            if (startY === null) return;

            const shouldClose = distance > 110;

            startY = null;
            distance = 0;

            sheet.style.transition = '';
            sheet.style.transform = '';

            if (shouldClose) closeSheet();

        }

        handle.addEventListener('pointerup', finishDrag);
        handle.addEventListener('pointercancel', finishDrag);

    }

    enableSwipeToClose(stepsSheet);
    enableSwipeToClose(chatSheet);

    function showToast(message, actionLabel, onAction) {

        toastText.textContent = message;

        if (actionLabel) {
            toastAction.textContent = actionLabel;
            toastAction.classList.remove('hidden');
            toastAction.onclick = () => {
                hideToast();
                onAction();
            };
        } else {
            toastAction.classList.add('hidden');
            toastAction.onclick = null;
        }

        toast.classList.add('show');

        clearTimeout(toastTimer);
        toastTimer = setTimeout(hideToast, 6000);

    }

    function hideToast() {
        toast.classList.remove('show');
    }

    function showError(message) {
        formError.textContent = message;
        formError.classList.remove('hidden');
    }

    function clearError() {
        formError.textContent = '';
        formError.classList.add('hidden');
    }

    // Typing or choosing something clears an old error message
    stepInputContainer.addEventListener('input', clearError);
    stepInputContainer.addEventListener('change', clearError);


    // ============================================================
    // OFFLINE QUEUE (IndexedDB)
    // Photos can be several MB, which is too big for localStorage,
    // so submissions waiting to sync are stored in IndexedDB.
    // ============================================================

    const DB_NAME = 'stepwise-offline';
    const STORE = 'queue';

    function openDb() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(DB_NAME, 1);

            request.onupgradeneeded = () => {
                request.result.createObjectStore(STORE, { keyPath: 'key' });
            };
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    async function dbRun(mode, work) {
        const db = await openDb();

        return new Promise((resolve, reject) => {
            const tx = db.transaction(STORE, mode);
            const request = work(tx.objectStore(STORE));

            tx.oncomplete = () => resolve(request ? request.result : undefined);
            tx.onerror = () => reject(tx.error);
            tx.onabort = () => reject(tx.error);
        });
    }

    const dbPut = (item) => dbRun('readwrite', store => store.put(item));
    const dbDelete = (key) => dbRun('readwrite', store => store.delete(key));
    const dbAll = () => dbRun('readonly', store => store.getAll());

    // Queue for this session, in step order (the server requires earlier steps first)
    async function getQueue() {
        try {
            const all = await dbAll();

            return all
                .filter(item => item.sessionId === SESSION_ID)
                .sort((a, b) => a.order - b.order);
        } catch (error) {
            return [];
        }
    }

    async function queueSubmission(step, payload) {
        await dbPut({
            key: `${SESSION_ID}-${step.id}`,
            sessionId: SESSION_ID,
            stepId: step.id,
            order: Number(step.order_number),
            title: step.title,
            payload: payload,
            queuedAt: Date.now(),
            error: null
        });

        step.queued = true;
    }


    // ============================================================
    // STATUS BANNER
    // ============================================================

    async function updateBanner() {

        const queue = await getQueue();
        const stuck = queue.find(item => item.error);

        const base = 'px-5 py-2.5 text-sm font-medium leading-snug ';

        if (queue.length) {
            syncChip.textContent = queue.length === 1
                ? '1 step waiting to sync'
                : `${queue.length} steps waiting to sync`;
            syncChip.classList.remove('hidden');
        } else {
            syncChip.classList.add('hidden');
        }

        if (stuck) {

            offlineBanner.className = base + 'bg-red-100 text-red-800';
            offlineBanner.textContent =
                `Sync paused on "${stuck.title}": ${stuck.error}`;

        } else if (!navigator.onLine) {

            offlineBanner.className = base + 'border-y border-amber/50 bg-amber-soft text-ink';
            offlineBanner.textContent = "You're offline. Your work will sync later";

        } else if (queue.length) {

            offlineBanner.className = base + 'border-y border-amber/50 bg-amber-soft text-ink';
            offlineBanner.textContent =
                `Back online. Syncing ${queue.length} saved step(s)...`;

        } else {

            offlineBanner.className = 'hidden';
            offlineBanner.textContent = '';

        }

    }


    // ============================================================
    // LOAD STEPS (falls back to the last cached copy when offline)
    // ============================================================

    async function fetchSteps() {

        let response;

        try {

            response = await fetch(`/wizard/sessions/${SESSION_ID}/steps`);

        } catch (networkError) {

            const cached = localStorage.getItem(STEPS_CACHE_KEY);

            if (cached) return JSON.parse(cached);

            throw new Error(
                'You are offline and no steps have been saved yet. ' +
                'Connect once to load them.'
            );

        }

        if (!response.ok) {
            throw new Error('Could not load onboarding steps.');
        }

        const data = await response.json();

        localStorage.setItem(STEPS_CACHE_KEY, JSON.stringify(data));

        return data;

    }

    // Keep the offline copy in step with what the agent has completed,
    // otherwise reloading offline would show stale (earlier) progress.
    function saveStepsCache() {

        try {

            localStorage.setItem(
                STEPS_CACHE_KEY,
                JSON.stringify(steps.map(({ queued, ...rest }) => rest))
            );

        } catch (error) {}

    }

    async function loadSteps() {

        try {

            const previousId = steps[currentStepIndex]?.id;

            steps = await fetchSteps();

            if (!steps.length) {
                throw new Error('No onboarding steps were found.');
            }

            // Steps saved on this device but not yet synced count as done locally.
            const queue = await getQueue();
            const queuedIds = new Set(queue.map(item => item.stepId));

            steps.forEach(step => {
                step.queued = !step.completed && queuedIds.has(step.id);
            });

            // Find the first step that still needs the agent's input.
            const firstOpen = steps.findIndex(
                step => !step.completed && !step.queued
            );

            if (firstOpen === -1) {
                showFinishedScreen();
                return;
            }

            // If the agent is mid-entry on a step that is still open, refresh the
            // list only, so a background sync never wipes what they are typing.
            const keepIndex = steps.findIndex(
                step => step.id === previousId && !step.completed && !step.queued
            );

            if (keepIndex !== -1 && !finishedShown) {
                currentStepIndex = keepIndex;
                renderStepsList();
                return;
            }

            currentStepIndex = firstOpen;

            renderStepsList();
            renderCurrentStep();

        } catch (error) {

            stepsList.innerHTML = `
                <div class="text-center text-sm font-medium text-red-700">
                    ${error.message}
                </div>
            `;

            stepTitle.textContent = 'Steps could not load';
            stepInstructions.textContent = error.message;
            stepInputContainer.innerHTML = '';
            stepCounter.textContent = '';
            guidanceLabel.textContent = '';
            progressText.textContent = 'Steps';

        }

    }


    // ============================================================
    // PROGRESS RULER AND STEP LIST
    // ============================================================

    function renderProgress() {

        const saved = steps.filter(step => step.completed || step.queued).length;

        progressText.textContent = `${saved} of ${steps.length} saved`;

        ruler.innerHTML = '';

        steps.forEach((step, index) => {

            const segment = document.createElement('span');

            const isCurrent = index === currentStepIndex && !finishedShown;

            let classes = 'h-2.5 rounded-[3px] ';

            if (step.completed) {
                classes += 'bg-ledger';
            } else if (step.queued) {
                classes += 'queued-seg';
            } else if (isCurrent) {
                classes += 'bg-cobalt';
            } else {
                classes += 'bg-hairline';
            }

            segment.className = classes;
            segment.style.flex = isCurrent ? '2.4' : '1';

            ruler.appendChild(segment);

        });

    }

    function renderStepsList() {

        renderProgress();

        stepsList.innerHTML = '';

        steps.forEach((step, index) => {

            const item = document.createElement('div');

            const isCurrent = index === currentStepIndex && !finishedShown;

            let tone = 'border-hairline bg-slip';
            let badge = 'border border-hairline bg-slip text-muted';
            let marker = step.order_number;
            let status = step.guidance_label || '';

            if (step.completed) {

                tone = 'border-ledger/30 bg-ledger-soft';
                badge = 'bg-ledger text-white';
                marker = '✓';
                status = 'Saved';

            } else if (step.queued) {

                tone = 'border-amber/60 bg-amber-soft';
                badge = 'bg-amber text-ink';
                marker = '⏳';
                status = 'Saved on this device, waiting to sync';

            } else if (isCurrent) {

                tone = 'border-cobalt bg-cobalt-soft';
                badge = 'bg-cobalt text-white';
                status = 'You are here';

            }

            item.className = `flex items-start gap-3 rounded-xl border p-3 ${tone}`;

            item.innerHTML = `
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-bold ${badge}">
                    ${marker}
                </div>

                <div class="min-w-0">
                    <div class="text-[15px] font-semibold leading-snug">${step.title}</div>
                    <div class="mt-0.5 text-sm leading-snug text-muted">${status}</div>
                </div>
            `;

            stepsList.appendChild(item);

        });

    }


    // ============================================================
    // RENDER CURRENT STEP
    // ============================================================

    function esc(value) {
        const div = document.createElement('div');
        div.textContent = value == null ? '' : String(value);
        return div.innerHTML.replace(/"/g, '&quot;');
    }

    function renderStepInput(step) {

        const config = step.input_config || {};

        const selectClasses =
            'h-14 w-full rounded-xl border border-hairline bg-slip px-4 text-[16px]';

        const checkboxClasses =
            'mt-0.5 h-6 w-6 shrink-0 accent-[#0B7A52]';

        function confirmation(label, id) {
            return `
                <label class="flex min-h-[64px] cursor-pointer items-start gap-3 rounded-2xl border border-hairline bg-slip p-4 has-[:checked]:border-ledger has-[:checked]:bg-ledger-soft">
                    <input type="checkbox" id="${id || 'step-input'}" class="${checkboxClasses}">
                    <span class="text-[16px] leading-snug">${esc(label)}</span>
                </label>
            `;
        }

        let html = '';

        switch (step.input_type) {

            case 'photo':

                stepInputLabel.textContent = config.label || 'Photo';

                html = `
                    <p class="mb-3 text-[15px] leading-snug text-muted">
                        ${esc(config.description || '')}
                    </p>

                    <label class="relative flex min-h-[200px] cursor-pointer flex-col items-center justify-center overflow-hidden rounded-2xl border-2 border-dashed border-hairline bg-slip text-center focus-within:border-cobalt">
                        <input type="file" id="step-input"
                            accept="image/*"
                            capture="${esc(config.capture || 'environment')}"
                            class="sr-only">

                        <img id="capture-preview" alt="Preview of the selected photo"
                            class="absolute inset-0 hidden h-full w-full object-cover">

                        <span id="capture-prompt" class="px-6 text-[17px] font-bold">Take photo</span>
                        <span id="capture-sub" class="mt-1 px-6 text-sm text-muted">Opens your camera</span>

                        <span id="capture-retake"
                            class="absolute bottom-3 hidden rounded-full bg-ink/80 px-4 py-1.5 text-sm font-semibold text-white">
                            Tap to retake
                        </span>
                    </label>
                `;
                break;

            case 'confirm':

                stepInputLabel.textContent = config.label || 'Confirmation';
                html = confirmation(config.statement || 'I have completed this step.');
                break;

            case 'text':

                stepInputLabel.textContent = config.label || 'Details';

                html = `
                    <input type="text" id="step-input"
                        placeholder="${esc(config.placeholder || '')}"
                        maxlength="${esc(config.max_length || 255)}"
                        autocomplete="off"
                        autocorrect="off"
                        spellcheck="false"
                        class="h-14 w-full rounded-xl border border-hairline bg-slip px-4 text-[18px] tracking-wide">
                `;
                break;

            case 'select': {

                stepInputLabel.textContent = config.label || 'Choose an option';

                const options = (config.options || []).map(option =>
                    `<option value="${esc(option.value)}">${esc(option.label)}</option>`
                ).join('');

                html = `
                    ${config.description ? `
                        <p class="mb-3 text-[15px] leading-snug text-muted">${esc(config.description)}</p>
                    ` : ''}

                    ${config.select_label ? `
                        <label for="step-input" class="mb-2 block text-[15px] text-muted">${esc(config.select_label)}</label>
                    ` : ''}

                    <select id="step-input" class="${selectClasses} ${config.confirm_statement ? 'mb-4' : ''}">
                        <option value="">${esc(config.placeholder || 'Choose an option')}</option>
                        ${options}
                    </select>

                    ${config.confirm_statement
                        ? confirmation(config.confirm_statement, 'step-confirm')
                        : ''}
                `;
                break;
            }

            default:

                stepInputLabel.textContent = 'Step submission';

                html = `
                    <textarea id="step-input" rows="3"
                        placeholder="Enter your response"
                        class="w-full rounded-xl border border-hairline bg-slip px-4 py-3 text-[16px]"></textarea>
                `;

        }

        stepInputContainer.innerHTML = html;
        stepInput = stepInputContainer.querySelector('#step-input');

        // Show a preview of the captured photo
        if (step.input_type === 'photo' && stepInput) {

            stepInput.addEventListener('change', () => {

                const file = stepInput.files?.[0];

                if (!file) return;

                if (previewUrl) URL.revokeObjectURL(previewUrl);

                previewUrl = URL.createObjectURL(file);

                const preview = stepInputContainer.querySelector('#capture-preview');

                preview.src = previewUrl;
                preview.classList.remove('hidden');

                stepInputContainer.querySelector('#capture-prompt').classList.add('hidden');
                stepInputContainer.querySelector('#capture-sub').classList.add('hidden');
                stepInputContainer.querySelector('#capture-retake').classList.remove('hidden');

            });

        }

    }

    function updateNextLabel() {

        nextButton.textContent =
            currentStepIndex === steps.length - 1
                ? 'Finish onboarding'
                : 'Save and continue';

    }

    function renderCurrentStep() {

        const step = steps[currentStepIndex];

        if (!step) return;

        finishedShown = false;
        nextButton.disabled = false;
        clearError();


        const totalSteps = steps.length;

        const currentNumber = currentStepIndex + 1;


        // Header
        stepCounter.textContent =
            `Step ${currentNumber} of ${totalSteps}`;


        // Main content
        stepTitle.textContent =
            step.title;

        stepInstructions.textContent =
            step.instructions || 'Follow the instructions for this step.';


        // Guidance: the step's own label first, then AI tips for this step
        showTip(step);


        // Validation information
        validationHint.textContent =
            step.validation_rules
                ? `Requirement: ${step.validation_rules}`
                : '';


        // render the input field for the current step
        renderStepInput(step);


        updateNextLabel();


        renderStepsList();


        window.scrollTo(0, 0);

    }


    // ============================================================
    // FINISHED SCREEN
    // Shown when every step is either synced or saved on this device.
    // ============================================================

    function showFinishedScreen() {

        const pending = steps.filter(step => step.queued).length;

        finishedShown = true;
        clearError();

        if (pending) {

            stepCounter.textContent = 'All steps captured';
            stepTitle.textContent = 'Waiting to sync';
            stepInstructions.textContent =
                `${pending} step(s) are saved on this device and will be sent ` +
                'automatically when you reconnect.';
            guidanceLabel.textContent =
                'Keep this app open or come back later. Nothing will be lost.';
            nextButton.textContent = 'Waiting to sync';

        } else {

            stepCounter.textContent = 'Onboarding complete';
            stepTitle.textContent = 'Merchant onboarding complete';
            stepInstructions.textContent =
                'You have reached the end of the onboarding workflow.';
            guidanceLabel.textContent =
                'All onboarding steps have been submitted.';
            nextButton.textContent = 'Back to dashboard';

        }

        validationHint.textContent = '';
        stepInputContainer.innerHTML = '';
        stepInputLabel.textContent = 'Submission';
        stepInput = null;

        nextButton.disabled = pending > 0;

        renderStepsList();

    }

    function goToNextStep() {

        const nextIndex = steps.findIndex(
            (step, index) =>
                index > currentStepIndex && !step.completed && !step.queued
        );

        if (nextIndex === -1) {
            showFinishedScreen();
            return;
        }

        currentStepIndex = nextIndex;
        renderCurrentStep();

    }


    // ============================================================
    // SUBMIT CURRENT STEP
    // ============================================================

    function sendSubmission(stepId, payload) {

        return fetch(
            `/wizard/sessions/${SESSION_ID}/steps/${stepId}/submit`,
            {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN
                },
                body: JSON.stringify({
                    submission_data: payload
                })
            }
        );

    }

    nextButton.addEventListener('click', async () => {

        if (finishedShown) {
            window.location.href = DASHBOARD_URL;
            return;
        }

        const step = steps[currentStepIndex];

        if (!step || !stepInput) return;

        clearError();

        const config = step.input_config || {};
        const payload = { type: config.submission_type };

        if (step.input_type === 'photo') {

            const file = stepInput.files?.[0];

            if (!file) {
                showError('Take or choose the required photo first.');
                return;
            }

            if (!file.type.startsWith('image/')) {
                showError('That file is not an image. Choose a photo instead.');
                return;
            }

            if (file.size > 5 * 1024 * 1024) {
                showError('That photo is larger than 5 MB. Retake it at a lower quality.');
                return;
            }

            try {
                payload.filename = file.name;
                payload.mime_type = file.type;
                payload.response = await new Promise((resolve, reject) => {
                    const reader = new FileReader();
                    reader.onload = () => resolve(reader.result);
                    reader.onerror = () => reject(
                        new Error('Could not read the selected image.')
                    );
                    reader.readAsDataURL(file);
                });
            } catch (error) {
                showError(error.message);
                return;
            }

        } else if (step.input_type === 'text') {

            const value = stepInput.value.trim();

            if (!value) {
                showError(`Enter the ${(config.label || 'required value').toLowerCase()}.`);
                return;
            }

            payload.response = value;

        } else if (step.input_type === 'select') {

            const value = stepInput.value;
            const field = config.field || 'response';

            if (!value) {
                showError('Choose an option first.');
                return;
            }

            if (config.confirm_statement) {

                const confirmed = stepInputContainer.querySelector('#step-confirm')?.checked;

                if (!confirmed) {
                    showError('Tick the box to confirm before continuing.');
                    return;
                }

            }

            payload[field] = value;

            if (field !== 'response') {
                payload.response = config.response_text || value;
            }

        } else {

            // Plain confirmation
            if (!stepInput.checked) {
                showError('Tick the box to confirm you completed this action.');
                return;
            }

            payload.response = 'Confirmed by agent';

        }

        nextButton.disabled = true;
        nextButton.textContent = 'Saving...';

        try {

            let response = null;

            if (navigator.onLine) {
                try {
                    response = await sendSubmission(step.id, payload);
                } catch (networkError) {
                    response = null; // server unreachable: treat like offline
                }
            }

            // Offline (or server unreachable): keep the work on this device
            if (response === null) {
                try {
                    await queueSubmission(step, payload);
                } catch (storageError) {
                    showError('Could not save this step on the device. Reconnect and try again.');
                    return;
                }

                updateBanner();
                showToast('Saved on this device. It will sync when you reconnect.');
                goToNextStep();
                return;
            }

            const data = await response.json();

            if (!response.ok) {
                throw new Error(
                    data.message || 'Could not submit this step.'
                );
            }

            step.completed = true;
            step.queued = false;

            saveStepsCache();

            if (data.onboarding_complete) {
                showFinishedScreen();
                return;
            }

            goToNextStep();

        } catch (error) {
            showError(error.message);
        } finally {
            if (!finishedShown) {
                nextButton.disabled = false;
                updateNextLabel();
            }
        }

    });


    // ============================================================
    // SYNC SAVED SUBMISSIONS WHEN BACK ONLINE
    // ============================================================

    let flushing = false;

    async function flushQueue() {

        if (flushing || !navigator.onLine) return;

        const queue = await getQueue();

        if (!queue.length) return;

        flushing = true;

        const synced = [];

        // Oldest step first: the server rejects a step if earlier ones are missing.
        for (const item of queue) {

            let response;

            try {
                response = await sendSubmission(item.stepId, item.payload);
            } catch (networkError) {
                break; // still no connection; retry later
            }

            if (response.ok) {
                await dbDelete(item.key);
                synced.push({ id: item.stepId, title: item.title });
                continue;
            }

            if (response.status >= 500) {
                break; // server trouble: keep everything and retry later
            }

            // The server refused it (e.g. validation or ordering). Keep it,
            // record why, and stop so later steps are not sent out of order.
            let message = 'The server could not accept this submission.';

            try {
                message = (await response.json()).message || message;
            } catch (parseError) {}

            item.error = message;
            await dbPut(item);
            break;

        }

        flushing = false;

        await loadSteps();
        await updateBanner();

    }

    window.addEventListener('online', async () => {
        await updateBanner();
        flushQueue();
    });

    window.addEventListener('offline', updateBanner);

    // Retry now and then, in case the server came back without a network event
    setInterval(flushQueue, 30000);


    // ============================================================
    // AI TIPS FOR THE CURRENT STEP
    // The tip depends only on the step (not on what the agent entered), so the
    // server generates it once and every device keeps a copy for offline use.
    // Bump TIP_PREFIX to force devices to fetch fresh tips.
    // ============================================================

    const TIP_PREFIX = 'stepwise_tip_v1_';

    let prefetching = false;

    function cachedTip(stepId) {

        try {
            return localStorage.getItem(TIP_PREFIX + stepId);
        } catch (error) {
            return null;
        }

    }

    async function fetchTip(step) {

        const cached = cachedTip(step.id);

        if (cached) return cached;

        if (!navigator.onLine) return null;

        try {

            const response = await fetch(
                `/wizard/steps/${step.id}/guidance`,
                { headers: { 'Accept': 'application/json' } }
            );

            if (!response.ok) return null;

            const data = await response.json();

            if (data.tip) {
                try {
                    localStorage.setItem(TIP_PREFIX + step.id, data.tip);
                } catch (error) {}
            }

            return data.tip || null;

        } catch (error) {

            return null;

        }

    }

    // Warm up tips for the next couple of steps so they are ready when needed
    async function prefetchTips(fromStep) {

        if (prefetching || !navigator.onLine) return;

        prefetching = true;

        try {

            const upcoming = steps
                .filter(step =>
                    Number(step.order_number) > Number(fromStep.order_number) &&
                    !step.completed &&
                    !step.queued
                )
                .slice(0, 2);

            for (const step of upcoming) {
                await fetchTip(step);
            }

        } finally {

            prefetching = false;

        }

    }

    async function showTip(step) {

        const fallback =
            step.guidance_label ||
            'Follow the instructions carefully before continuing.';

        const cached = cachedTip(step.id);

        if (cached) {
            guidanceLabel.textContent = cached;
            prefetchTips(step);
            return;
        }

        // Show the step's own label straight away, then upgrade it to the AI tip
        guidanceLabel.textContent = navigator.onLine
            ? fallback + '\n\nPreparing tips for this step...'
            : fallback;

        const tip = await fetchTip(step);

        // Only update the screen if the agent is still on this step
        if (steps[currentStepIndex]?.id === step.id) {
            guidanceLabel.textContent = tip || fallback;
        }

        prefetchTips(step);

    }


    // ============================================================
    // OPEN AI HELP
    // ============================================================

    helpButton.addEventListener('click', () => {

        const step = steps[currentStepIndex];

        if (!step) return;


        const question =
            `I am currently on the onboarding step "${step.title}". ` +
            `Please explain how to complete this step and what I should check before submitting it.`;


        openSheet(chatSheet);

        input.value = question;

        setTimeout(() => input.focus(), 80);

    });


    // ============================================================
    // CHAT
    // ============================================================

    const form =
        document.getElementById('chat-form');

    const input =
        document.getElementById('question-input');

    const chatWindow =
        document.getElementById('chat-window');

    const chatEmpty =
        document.getElementById('chat-empty');


    function addMessage(text, sender) {

        if (chatEmpty) chatEmpty.classList.add('hidden');

        const bubble =
            document.createElement('div');

        bubble.className =
            sender === 'user'
                ? 'ml-auto max-w-[85%] rounded-2xl rounded-br-md bg-cobalt px-4 py-2.5 text-[15px] leading-snug text-white'
                : 'mr-auto max-w-[85%] rounded-2xl rounded-bl-md bg-counter px-4 py-2.5 text-[15px] leading-snug text-ink';


        bubble.textContent = text;


        const wrapper =
            document.createElement('div');

        wrapper.className =
            'flex';


        wrapper.appendChild(bubble);

        chatWindow.appendChild(wrapper);

        chatWindow.scrollTop =
            chatWindow.scrollHeight;

        return bubble;

    }


    // Ask the server, trying once more if it fails or times out
    async function askWithRetry(question, setNote) {

        const MAX_ATTEMPTS = 2;

        for (let attempt = 1; attempt <= MAX_ATTEMPTS; attempt++) {

            let response = null;

            try {

                const currentStep = steps[currentStepIndex];

                response = await fetch('/wizard/ask', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN
                    },
                    body: JSON.stringify({
                        session_id: SESSION_ID,
                        step_id: currentStep ? currentStep.id : null,
                        question: question
                    })
                });

            } catch (networkError) {

                response = null;

            }

            if (response && response.ok) {
                return await response.json();
            }

            // A 4xx means the request itself is wrong, so trying again won't help
            if (response && response.status >= 400 && response.status < 500) {
                const data = await response.json().catch(() => ({}));
                throw new Error(data.message || data.error || 'The question could not be sent.');
            }

            // Network failure or server error: try again
            if (attempt < MAX_ATTEMPTS) {
                setNote('That took too long. Trying again, hang on tight...');
                await new Promise(resolve => setTimeout(resolve, 2000));
            }

        }

        throw new Error('StepWise is having trouble right now. Please try again in a minute.');

    }

    form.addEventListener('submit', async (e) => {

        e.preventDefault();

        const question = input.value.trim();

        if (!question) return;

        // AI answers need the server, so be clear when offline
        if (!navigator.onLine) {

            addMessage(
                "You're offline. StepWise's AI answers need a connection, " +
                "but you can keep completing steps. They will sync when you reconnect.",
                'bot'
            );

            return;

        }

        addMessage(question, 'user');

        input.value = '';
        input.disabled = true;

        const loadingBubble = document.createElement('div');
        loadingBubble.className = 'flex';

        const loadingText = document.createElement('div');
        loadingText.className =
            'mr-auto max-w-[85%] rounded-2xl rounded-bl-md bg-counter px-4 py-2.5 text-[15px] italic text-muted';
        loadingText.textContent = 'StepWise is thinking...';

        loadingBubble.appendChild(loadingText);
        chatWindow.appendChild(loadingBubble);
        chatWindow.scrollTop = chatWindow.scrollHeight;

        // Reassure the agent while a slow answer is on its way
        const startedAt = Date.now();
        let retryNote = null;

        const ticker = setInterval(() => {

            if (retryNote) {
                loadingText.textContent = retryNote;
                return;
            }

            const seconds = (Date.now() - startedAt) / 1000;

            loadingText.textContent =
                seconds < 6 ? 'StepWise is thinking...' :
                seconds < 20 ? 'Hang on tight, this is taking a little longer...' :
                'Still working on it. The first answer after a pause is the slowest.';

        }, 1000);

        try {

            const data = await askWithRetry(question, (note) => { retryNote = note; });

            addMessage(data.answer, 'bot');

        } catch (error) {

            addMessage(error.message, 'bot');

        } finally {

            clearInterval(ticker);
            loadingBubble.remove();

            input.disabled = false;
            input.focus();

        }

    });


    document.getElementById('back-link').addEventListener('click', (event) => {

        const unsaved = stepInput && (
            (stepInput.type === 'file' && stepInput.files && stepInput.files.length) ||
            (stepInput.type === 'checkbox' && stepInput.checked) ||
            (stepInput.type !== 'file' && stepInput.type !== 'checkbox' && stepInput.value)
        );

        if (unsaved && !confirm('You have not saved this step yet. Leave anyway?')) {
            event.preventDefault();
        }

    });


    // ============================================================
    // START
    // ============================================================

    updateBanner();

    loadSteps().then(flushQueue);

</script>

<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js');
        });
    }
</script>
</body>
</html>