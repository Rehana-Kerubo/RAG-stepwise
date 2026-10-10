<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>StepWise - Onboarding Assistant</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#4f46e5">
    <link rel="apple-touch-icon" href="/icons/icon-192.png">
</head>

<body class="bg-gray-100 min-h-screen p-4">

    <!-- Offline / sync status banner (hidden when there is nothing to report) -->
    <div id="offline-banner" class="hidden"></div>

    <div class="max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-4">

        <!-- =========================================================
             LEFT: ONBOARDING STEPS
        ========================================================== -->
        <div class="bg-white rounded-xl shadow-lg flex flex-col h-[85vh]">

            <div class="bg-emerald-600 text-white p-4 rounded-t-xl">
                <h1 class="text-lg font-semibold">StepWise</h1>
                <p class="text-sm text-emerald-100">
                    Merchant Onboarding
                </p>
            </div>

            <div class="p-4 border-b">
                <div class="flex justify-between text-sm mb-2">
                    <span class="font-medium">Progress</span>
                    <span id="progress-text">0 of 0</span>
                </div>

                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div
                        id="progress-bar"
                        class="bg-emerald-500 h-2 rounded-full transition-all duration-300"
                        style="width: 0%">
                    </div>
                </div>
            </div>

            <div id="steps-list" class="flex-1 overflow-y-auto p-4 space-y-2">
                <div class="text-sm text-gray-400 text-center">
                    Loading steps...
                </div>
            </div>

        </div>


        <!-- =========================================================
             CENTRE: CURRENT ONBOARDING STEP
        ========================================================== -->
        <div class="bg-white rounded-xl shadow-lg flex flex-col min-h-[85vh]">

            <div class="bg-indigo-600 text-white p-4 rounded-t-xl">
                <p class="text-sm text-indigo-100">
                    Current onboarding step
                </p>

                <h2 id="step-counter" class="text-lg font-semibold">
                    Loading...
                </h2>
            </div>


            <div class="flex-1 p-6">

                <!-- Step title -->
                <div class="mb-6">
                    <p class="text-sm text-gray-500 mb-1">
                        StepWise Guidance
                    </p>

                    <h1
                        id="step-title"
                        class="text-2xl font-bold text-gray-800">
                        Loading step...
                    </h1>
                </div>


                <!-- Instructions -->
                <div class="bg-gray-50 border rounded-xl p-4 mb-6">

                    <h3 class="font-semibold text-gray-800 mb-2">
                        What to do
                    </h3>

                    <p
                        id="step-instructions"
                        class="text-sm text-gray-600 leading-relaxed">
                        Loading instructions...
                    </p>

                </div>


                <!-- Submission area -->
                <div class="mb-6">

                    <label
                        id="step-input-label"
                        class="block text-sm font-medium text-gray-700 mb-2">
                        Step submission
                    </label>

                    <div id="step-input-container"></div>

                    <p
                        id="validation-hint"
                        class="text-xs text-gray-400 mt-2">
                    </p>

                </div>

                <!-- AI contextual guidance -->
                <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-4 mb-6">

                    <div class="flex items-start gap-3">

                        <div class="text-xl">
                            💡
                        </div>

                        <div>
                            <h3 class="font-semibold text-indigo-900">
                                AI Guidance
                            </h3>

                            <p
                                id="guidance-label"
                                class="text-sm text-indigo-700 mt-1">
                                Guidance for the current step will appear here.
                            </p>
                        </div>

                    </div>

                </div>


                <!-- Buttons -->
                <div class="flex justify-between items-center">

                    <button
                        id="help-button"
                        type="button"
                        class="border border-indigo-300 text-indigo-600 px-4 py-2 rounded-lg text-sm hover:bg-indigo-50">
                        Need more help?
                    </button>

                    <button
                        id="next-button"
                        type="button"
                        class="bg-indigo-600 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700 disabled:opacity-60">
                        NEXT
                    </button>

                </div>

            </div>

        </div>


        <!-- =========================================================
             RIGHT: AI CHATBOT
        ========================================================== -->
        <div
            id="chat-panel"
            class="bg-white rounded-xl shadow-lg flex flex-col h-[85vh]">

            <div class="bg-gray-800 text-white p-4 rounded-t-xl">

                <h1 class="text-lg font-semibold">
                    StepWise AI
                </h1>

                <p class="text-sm text-gray-300">
                    Ask for detailed onboarding help
                </p>

            </div>


            <div
                id="chat-window"
                class="flex-1 overflow-y-auto p-4 space-y-3">

                <div class="text-sm text-gray-400 text-center">
                    Ask StepWise if you need more detailed guidance.
                </div>

            </div>


            <form
                id="chat-form"
                class="p-4 border-t flex gap-2">

                <input
                    id="question-input"
                    type="text"
                    placeholder="Ask about this step..."
                    class="flex-1 border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                    autocomplete="off"
                />

                <button
                    type="submit"
                    class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-indigo-700">
                    Send
                </button>

            </form>

        </div>

    </div>


<script>

    // ============================================================
    // SESSION
    // ============================================================

    const SESSION_ID = 3;
    const STEPS_CACHE_KEY = 'stepwise_steps_' + SESSION_ID;

    let steps = [];
    let currentStepIndex = 0;
    let finishedShown = false;


    // ============================================================
    // ELEMENTS
    // ============================================================

    const stepsList = document.getElementById('steps-list');

    const progressText = document.getElementById('progress-text');
    const progressBar = document.getElementById('progress-bar');

    const stepCounter = document.getElementById('step-counter');
    const stepTitle = document.getElementById('step-title');
    const stepInstructions = document.getElementById('step-instructions');

    const stepInputContainer = document.getElementById('step-input-container');
    const stepInputLabel = document.getElementById('step-input-label');
    let stepInput = null;
    const validationHint = document.getElementById('validation-hint');

    const guidanceLabel = document.getElementById('guidance-label');

    const nextButton = document.getElementById('next-button');
    const helpButton = document.getElementById('help-button');

    const offlineBanner = document.getElementById('offline-banner');


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

        const base = 'max-w-6xl mx-auto mb-4 rounded-lg px-4 py-2 text-sm text-white ';

        if (stuck) {

            offlineBanner.className = base + 'bg-red-600';
            offlineBanner.textContent =
                `Sync paused on "${stuck.title}": ${stuck.error}`;

        } else if (!navigator.onLine) {

            offlineBanner.className = base + 'bg-amber-500';
            offlineBanner.textContent =
                "You're offline. You can keep working through the steps; " +
                "submissions are saved on this device and will sync when you reconnect." +
                (queue.length ? ` (${queue.length} waiting)` : '');

        } else if (queue.length) {

            offlineBanner.className = base + 'bg-amber-500';
            offlineBanner.textContent =
                `Syncing ${queue.length} saved submission(s)...`;

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

            response = await fetch(`/api/sessions/${SESSION_ID}/steps`);

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
                <div class="text-sm text-red-500 text-center">
                    ${error.message}
                </div>
            `;

        }

    }


    // ============================================================
    // RENDER LEFT STEP LIST
    // ============================================================

    function renderStepsList() {

        stepsList.innerHTML = '';

        steps.forEach((step, index) => {

            const item = document.createElement('div');

            const isCurrent = index === currentStepIndex;

            let classes =
                'p-3 rounded-lg border text-sm transition ';

            if (step.completed) {

                classes +=
                    'bg-emerald-50 border-emerald-200 text-gray-500';

            } else if (step.queued) {

                classes +=
                    'bg-amber-50 border-amber-200 text-amber-800';

            } else if (isCurrent) {

                classes +=
                    'bg-indigo-50 border-indigo-300 text-indigo-800';

            } else {

                classes +=
                    'bg-gray-50 border-gray-200 text-gray-500';

            }

            item.className = classes;

            const marker =
                step.completed ? '✓' :
                step.queued ? '⏳' :
                step.order_number;

            item.innerHTML = `
                <div class="flex items-start gap-3">

                    <div class="mt-0.5 font-semibold">
                        ${marker}
                    </div>

                    <div>

                        <div class="font-medium
                            ${step.completed ? 'line-through' : ''}">
                            ${step.title}
                        </div>

                        <div class="text-xs mt-1 opacity-75">
                            ${step.queued
                                ? 'Saved on this device, waiting to sync'
                                : (step.guidance_label || '')}
                        </div>

                    </div>

                </div>
            `;

            stepsList.appendChild(item);

        });

    }


    // ============================================================
    // RENDER CURRENT STEP
    // ============================================================
    function renderStepInput(step) {
    const order = Number(step.order_number);
    let html = '';

    const fileClasses = `
        block w-full text-sm text-gray-600
        border border-gray-300 rounded-xl px-3 py-3
        file:mr-3 file:rounded-lg file:border-0 file:px-3
        file:py-2 file:bg-indigo-50 file:text-indigo-700
    `;

    const checkboxClasses =
        'h-5 w-5 rounded border-gray-300 text-indigo-600';

    function confirmation(label) {
        return `
            <label class="flex items-start gap-3 border border-gray-200 rounded-xl p-4">
                <input type="checkbox" id="step-input"
                    class="${checkboxClasses} mt-1">
                <span class="text-sm text-gray-700">${label}</span>
            </label>
        `;
    }

    // Steps 1–3: Capture documents and photos
    if (order >= 1 && order <= 3) {
        const configs = {
            1: {
                label: 'Customer ID / Passport',
                description: 'Capture a clear image of the customer’s national ID or passport.',
                capture: 'environment',
                accept: 'image/*'
            },
            2: {
                label: 'Customer Photo',
                description: 'Take a clear passport-size photo of the customer.',
                capture: 'user',
                accept: 'image/*'
            },
            3: {
                label: 'Proof of Business Location',
                description: 'Capture the business permit or utility bill showing the business location.',
                capture: 'environment',
                accept: 'image/*'
            }
        };

        const config = configs[order];
        stepInputLabel.textContent = config.label;

        html = `
            <p class="text-sm text-gray-500 mb-3">
                ${config.description}
            </p>
            <input type="file" id="step-input"
                accept="${config.accept}"
                capture="${config.capture}"
                class="${fileClasses}">
        `;
    }

    // Step 4: Review and submit KYC documents
    else if (order === 4) {
        stepInputLabel.textContent = 'KYC Submission';
        html = confirmation(
            'I have reviewed the required KYC documents and submitted them for verification.'
        );
    }

    // Step 5: Power on POS
    else if (order === 5) {
        stepInputLabel.textContent = 'POS Device';
        html = confirmation(
            'I have powered on the POS device and confirmed that it starts successfully.'
        );
    }

    // Step 6: Connect POS
    else if (order === 6) {
        stepInputLabel.textContent = 'Network Connection';
        html = confirmation(
            'I have connected the POS device to Wi-Fi or mobile data and confirmed connectivity.'
        );
    }

    // Step 7: Activation code
    else if (order === 7) {
        stepInputLabel.textContent = 'POS Activation Code';
        html = `
            <input type="text" id="step-input"
                placeholder="Enter activation code"
                autocomplete="off"
                class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
        `;
    }

    // Step 8: Test transaction
    else if (order === 8) {
        stepInputLabel.textContent = 'Test Transaction';
        html = confirmation(
            'I performed the KES 1 test transaction and confirmed it appeared on the merchant dashboard within 2 minutes.'
        );
    }

    // Step 9: Till or Paybill request
    else if (order === 9) {
        stepInputLabel.textContent = 'Till / Paybill Request';
        html = `
            <label for="request-type" class="block text-sm text-gray-600 mb-2">
                Request type
            </label>
            <select id="request-type"
                class="w-full border border-gray-300 rounded-xl px-4 py-3 mb-4 text-sm">
                <option value="">Choose request type</option>
                <option value="Till">Till</option>
                <option value="Paybill">Paybill</option>
            </select>
            ${confirmation(
                'I have checked the applicable requirements and submitted the request.'
            )}
        `;
    }

    // Step 10: Confirmation
    else if (order === 10) {
        stepInputLabel.textContent = 'Activation Confirmation';
        html = confirmation(
            'I have confirmed that the merchant received the SMS confirming Till or Paybill activation.'
        );
    }

    // Step 11: First transaction
    else if (order === 11) {
        stepInputLabel.textContent = 'First Transaction';
        html = confirmation(
            'I guided the merchant through a transaction and confirmed it completed successfully.'
        );
    }

    // Step 12: Transaction history
    else if (order === 12) {
        stepInputLabel.textContent = 'Transaction History';
        html = confirmation(
            'I showed the merchant how to view their balance and transaction history.'
        );
    }

    // Step 13: Staff accounts
    else if (order === 13) {
        stepInputLabel.textContent = 'Staff Account Setup';
        html = `
            <p class="text-sm text-gray-500 mb-3">
                Does the merchant have employees who need individual accounts?
            </p>
            <select id="step-input"
                class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm">
                <option value="">Choose an option</option>
                <option value="setup_required">Yes — staff accounts have been set up</option>
                <option value="not_applicable">No additional staff accounts are needed</option>
            </select>
        `;
    }

    else {
        stepInputLabel.textContent = 'Step submission';
        html = `
            <textarea id="step-input" rows="3"
                placeholder="Enter your response"
                class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm"></textarea>
        `;
    }

    stepInputContainer.innerHTML = html;
    stepInput = stepInputContainer.querySelector('#step-input');
}

    function updateNextLabel() {

        nextButton.textContent =
            currentStepIndex === steps.length - 1
                ? 'COMPLETE ONBOARDING'
                : 'NEXT';

    }

    function renderCurrentStep() {

        const step = steps[currentStepIndex];

        if (!step) return;

        finishedShown = false;
        nextButton.disabled = false;


        const totalSteps = steps.length;

        const currentNumber = currentStepIndex + 1;

        const percentage =
            (currentNumber / totalSteps) * 100;


        // Header
        stepCounter.textContent =
            `Step ${currentNumber} of ${totalSteps}`;


        // Main content
        stepTitle.textContent =
            step.title;

        stepInstructions.textContent =
            step.instructions || 'Follow the instructions for this step.';


        // Guidance
        guidanceLabel.textContent =
            step.guidance_label ||
            'Follow the instructions carefully before continuing.';


        // Validation information
        validationHint.textContent =
            step.validation_rules
                ? `Requirement: ${step.validation_rules}`
                : '';


        // Progress
        progressText.textContent =
            `${currentNumber} of ${totalSteps}`;

        progressBar.style.width =
            `${percentage}%`;


        // render the input field for the current step
        renderStepInput(step);


        updateNextLabel();


        renderStepsList();

    }


    // ============================================================
    // FINISHED SCREEN
    // Shown when every step is either synced or saved on this device.
    // ============================================================

    function showFinishedScreen() {

        const pending = steps.filter(step => step.queued).length;

        finishedShown = true;

        if (pending) {

            stepCounter.textContent = 'All steps captured';
            stepTitle.textContent = 'Waiting to sync';
            stepInstructions.textContent =
                `${pending} step(s) are saved on this device and will be sent ` +
                'automatically when you reconnect.';
            guidanceLabel.textContent =
                'Keep this app open or come back later; nothing will be lost.';
            nextButton.textContent = 'WAITING TO SYNC';

        } else {

            stepCounter.textContent = 'Onboarding Complete';
            stepTitle.textContent = 'Merchant onboarding completed';
            stepInstructions.textContent =
                'You have reached the end of the onboarding workflow.';
            guidanceLabel.textContent =
                'All onboarding steps have been submitted.';
            nextButton.textContent = 'COMPLETED';

        }

        validationHint.textContent = '';
        stepInputContainer.innerHTML = '';
        stepInput = null;

        progressText.textContent = `${steps.length} of ${steps.length}`;
        progressBar.style.width = '100%';

        nextButton.disabled = true;

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
            `/api/sessions/${SESSION_ID}/steps/${stepId}/submit`,
            {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    submission_data: payload
                })
            }
        );

    }

   nextButton.addEventListener('click', async () => {
    const step = steps[currentStepIndex];

    if (!step || !stepInput) return;

    const order = Number(step.order_number);
    const payload = {};

    // Steps 1–3: Read selected image
    if (order >= 1 && order <= 3) {
        const file = stepInput.files?.[0];

        if (!file) {
            alert('Please capture or select the required image.');
            return;
        }

        if (!file.type.startsWith('image/')) {
            alert('Please select a valid image file.');
            return;
        }

        if (file.size > 5 * 1024 * 1024) {
            alert('Please select an image smaller than 5 MB.');
            return;
        }

        try {
            payload.type = 'document';
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
            alert(error.message);
            return;
        }
    }

    // Step 7: Activation code
    else if (order === 7) {
        const code = stepInput.value.trim();

        if (!code) {
            alert('Please enter the POS activation code.');
            return;
        }

        payload.type = 'activation_code';
        payload.response = code;
    }

    // Step 9: Till or Paybill request
    else if (order === 9) {
        const requestType =
            stepInputContainer.querySelector('#request-type')?.value;

        const confirmed =
            stepInputContainer.querySelector('input[type="checkbox"]')?.checked;

        if (!requestType) {
            alert('Please choose Till or Paybill.');
            return;
        }

        if (!confirmed) {
            alert('Please confirm that you submitted the request.');
            return;
        }

        payload.type = 'linking_request';
        payload.request_type = requestType;
        payload.response = 'Request requirements checked and request submitted';
    }

    // Step 13: Staff accounts
    else if (order === 13) {
        const choice = stepInput.value;

        if (!choice) {
            alert('Please select an option for staff account setup.');
            return;
        }

        payload.type = 'staff_accounts';
        payload.response = choice;
    }

    // Steps 4–6 and 8, 10–12: Confirmation
    else {
        if (!stepInput.checked) {
            alert('Please confirm that you completed this action.');
            return;
        }

        payload.type = 'confirmation';
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
                alert('Could not save this step on the device. Please reconnect and try again.');
                return;
            }

            updateBanner();
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
        
        // Ask the AI for guidance on this step in the background
        requestFeedback(step);

        if (data.onboarding_complete) {
            showFinishedScreen();
            return;
        }

        goToNextStep();

    } catch (error) {
        alert(error.message);
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

        // Feedback for synced steps, one at a time (the model is slow)
        for (const syncedStep of synced) {
            await requestFeedback(syncedStep);
        }

    }

    window.addEventListener('online', async () => {
        await updateBanner();
        flushQueue();
    });

    window.addEventListener('offline', updateBanner);

    // Retry now and then, in case the server came back without a network event
    setInterval(flushQueue, 30000);


    // ============================================================
    // AI FEEDBACK AFTER A STEP IS SUBMITTED
    // ============================================================

    async function requestFeedback(step) {

        if (!navigator.onLine) return;

        const bubble = addMessage(
            `Checking your "${step.title}" submission...`,
            'bot'
        );

        try {

            const response = await fetch(
                `/api/sessions/${SESSION_ID}/steps/${step.id}/feedback`,
                {
                    method: 'POST',
                    headers: { 'Accept': 'application/json' }
                }
            );

            const data = await response.json();

            bubble.textContent = data.feedback
                ? `Feedback on "${step.title}": ${data.feedback}`
                : `"${step.title}" was saved. No AI feedback is available right now.`;

        } catch (error) {

            bubble.textContent =
                `"${step.title}" was saved. Feedback is unavailable because the AI service could not be reached.`;

        }

        chatWindow.scrollTop = chatWindow.scrollHeight;

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


        input.value = question;

        input.focus();

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


    function addMessage(text, sender) {

        const bubble =
            document.createElement('div');

        bubble.className =
            sender === 'user'
                ? 'ml-auto bg-indigo-600 text-white rounded-lg px-3 py-2 text-sm max-w-[80%]'
                : 'mr-auto bg-gray-100 text-gray-800 rounded-lg px-3 py-2 text-sm max-w-[80%]';


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


    form.addEventListener('submit', async (e) => {

        e.preventDefault();


        const question =
            input.value.trim();


        if (!question) return;


        // AI answers need the server, so be clear when offline
        if (!navigator.onLine) {

            addMessage(
                "You're offline. StepWise's AI answers need a connection, " +
                "but you can keep completing steps; they will sync when you reconnect.",
                'bot'
            );

            return;

        }


        addMessage(
            question,
            'user'
        );


        input.value = '';

        input.disabled = true;


        const loadingBubble =
            document.createElement('div');

        loadingBubble.className =
            'flex';


        loadingBubble.innerHTML =
            '<div class="mr-auto bg-gray-100 text-gray-400 rounded-lg px-3 py-2 text-sm italic">StepWise is thinking...</div>';


        chatWindow.appendChild(
            loadingBubble
        );


        chatWindow.scrollTop =
            chatWindow.scrollHeight;


        try {

            const currentStep =
                steps[currentStepIndex];


            const response =
                await fetch(
                    '/api/ask',
                    {
                        method: 'POST',

                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },

                        body: JSON.stringify({

                            session_id: SESSION_ID,

                            step_id:
                                currentStep
                                    ? currentStep.id
                                    : null,

                            question:
                                question

                        })
                    }
                );


            const data =
                await response.json();


            loadingBubble.remove();


            if (!response.ok) {

                addMessage(
                    'Something went wrong: ' +
                    (data.error ||
                        data.message ||
                        'unknown error'),
                    'bot'
                );

            } else {

                addMessage(
                    data.answer,
                    'bot'
                );

            }


        } catch (error) {

            loadingBubble.remove();

            addMessage(
                'Could not reach the server.',
                'bot'
            );

        }


        input.disabled = false;

        input.focus();

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