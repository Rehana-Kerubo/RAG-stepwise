<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StepWise - Onboarding Assistant</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 min-h-screen p-4">

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
                        class="bg-indigo-600 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700">
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

    const SESSION_ID = 2;

    let steps = [];
    let currentStepIndex = 0;


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


    // ============================================================
    // LOAD STEPS
    // ============================================================

    async function loadSteps() {

        try {

            const response = await fetch(
                `/api/sessions/${SESSION_ID}/steps`
            );

            if (!response.ok) {
                throw new Error('Could not load onboarding steps.');
            }

            steps = await response.json();

            if (!steps.length) {
                throw new Error('No onboarding steps were found.');
            }

            // Find the first incomplete step.
            const firstIncomplete = steps.findIndex(
                step => !step.completed
            );

            currentStepIndex =
                firstIncomplete === -1
                    ? steps.length - 1
                    : firstIncomplete;

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

            } else if (isCurrent) {

                classes +=
                    'bg-indigo-50 border-indigo-300 text-indigo-800';

            } else {

                classes +=
                    'bg-gray-50 border-gray-200 text-gray-500';

            }

            item.className = classes;

            item.innerHTML = `
                <div class="flex items-start gap-3">

                    <div class="mt-0.5 font-semibold">
                        ${step.completed ? '✓' : step.order_number}
                    </div>

                    <div>

                        <div class="font-medium
                            ${step.completed ? 'line-through' : ''}">
                            ${step.title}
                        </div>

                        <div class="text-xs mt-1 opacity-75">
                            ${step.guidance_label || ''}
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

    let html = '';

    // Step 1 — Customer ID / Passport
    if (step.order_number === 1) {

        stepInputLabel.textContent =
            'Customer ID / Passport';

        html = `
            <input
                type="file"
                id="step-input"
                accept="image/*"
                capture="environment"
                class="block w-full text-sm text-gray-600
                       border border-gray-300 rounded-xl
                       px-3 py-3
                       file:mr-3
                       file:rounded-lg
                       file:border-0
                       file:px-3
                       file:py-2
                       file:bg-indigo-50
                       file:text-indigo-700"
            >
        `;

    }

    // Step 2 — Customer Photo
    else if (step.order_number === 2) {

        stepInputLabel.textContent =
            'Customer Photo';

        html = `
            <input
                type="file"
                id="step-input"
                accept="image/*"
                capture="user"
                class="block w-full text-sm text-gray-600
                       border border-gray-300 rounded-xl
                       px-3 py-3
                       file:mr-3
                       file:rounded-lg
                       file:border-0
                       file:px-3
                       file:py-2
                       file:bg-indigo-50
                       file:text-indigo-700"
            >
        `;

    }

    // Step 3 — Business Location Proof
    else if (step.order_number === 3) {

        stepInputLabel.textContent =
            'Proof of Business Location';

        html = `
            <input
                type="file"
                id="step-input"
                accept="image/*"
                capture="environment"
                class="block w-full text-sm text-gray-600
                       border border-gray-300 rounded-xl
                       px-3 py-3
                       file:mr-3
                       file:rounded-lg
                       file:border-0
                       file:px-3
                       file:py-2
                       file:bg-indigo-50
                       file:text-indigo-700"
            >
        `;

    }

    // Temporary fallback for other steps
    else {

        stepInputLabel.textContent =
            'Step submission';

        html = `
            <textarea
                id="step-input"
                rows="4"
                placeholder="Enter the information or confirmation for this step..."
                class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
            </textarea>
        `;
    }

    stepInputContainer.innerHTML = html;

    stepInput =
        document.getElementById('step-input');
}

    function renderCurrentStep() {

        const step = steps[currentStepIndex];

        if (!step) return;


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


        // Final step
        if (currentNumber === totalSteps) {

            nextButton.textContent =
                'COMPLETE ONBOARDING';

        } else {

            nextButton.textContent =
                'NEXT';

        }


        renderStepsList();

    }


    // ============================================================
    // SUBMIT CURRENT STEP
    // ============================================================

    nextButton.addEventListener('click', async () => {

    const step = steps[currentStepIndex];

    if (!step) return;

    const file = stepInput.files?.[0];

    if (!file) {

        alert(
            'Please capture or select the customer ID or passport before continuing.'
        );

        return;

    }

    nextButton.disabled = true;
    nextButton.textContent = 'Saving...';

    try {

        const submission = await new Promise((resolve, reject) => {

            const reader = new FileReader();

            reader.onload = () => resolve(reader.result);

            reader.onerror = () =>
                reject(new Error('Could not read the selected document.'));

            reader.readAsDataURL(file);

        });

        const response = await fetch(
            `/api/sessions/${SESSION_ID}/steps/${step.id}/submit`,
            {
                method: 'POST',

                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },

                body: JSON.stringify({

                    submission_data: {
                        type: 'document',
                        filename: file.name,
                        mime_type: file.type,
                        response: submission
                    }

                })
            }
        );

        const data = await response.json();

        if (!response.ok) {

            throw new Error(
                data.message ||
                'Could not submit this step.'
            );

        }

        // Mark current step complete locally.
        steps[currentStepIndex].completed = true;

        // Move to next step.
        if (data.onboarding_complete) {

            stepCounter.textContent =
                'Onboarding Complete';

            stepTitle.textContent =
                'Merchant onboarding completed';

            stepInstructions.textContent =
                'All onboarding steps have been completed successfully.';

            guidanceLabel.textContent =
                'You have completed the StepWise onboarding process.';

            stepInput.value = '';

            nextButton.textContent =
                'COMPLETED';

            nextButton.disabled = true;

            renderStepsList();

            return;

        }

        currentStepIndex++;

        renderCurrentStep();

    } catch (error) {

        alert(error.message);

        nextButton.textContent =
            'NEXT';

    }

    nextButton.disabled = false;

});

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

    }


    form.addEventListener('submit', async (e) => {

        e.preventDefault();


        const question =
            input.value.trim();


        if (!question) return;


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

    loadSteps();

</script>

</body>
</html>
