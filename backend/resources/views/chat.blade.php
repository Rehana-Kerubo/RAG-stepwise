<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Stepwise - Onboarding Assistant</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 h-screen flex items-center justify-center">

    <div class="w-full max-w-lg bg-white rounded-xl shadow-lg flex flex-col h-[80vh]">

        <div class="bg-indigo-600 text-white p-4 rounded-t-xl">
            <h1 class="text-lg font-semibold">Stepwise</h1>
            <p class="text-sm text-indigo-100">Ask anything about onboarding this merchant</p>
        </div>

        <div id="chat-window" class="flex-1 overflow-y-auto p-4 space-y-3">
            <div class="text-sm text-gray-400 text-center">Start by asking a question below</div>
        </div>

        <form id="chat-form" class="p-4 border-t flex gap-2">
            <input
                id="question-input"
                type="text"
                placeholder="e.g. How do I link a Till number?"
                class="flex-1 border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                autocomplete="off"
            />
            <button
                type="submit"
                class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-indigo-700"
            >
                Send
            </button>
        </form>

    </div>

    <script>
        const SESSION_ID = 1; // hardcoded test session for the demo

        const form = document.getElementById('chat-form');
        const input = document.getElementById('question-input');
        const chatWindow = document.getElementById('chat-window');

        function addMessage(text, sender) {
            const bubble = document.createElement('div');
            bubble.className = sender === 'user'
                ? 'ml-auto bg-indigo-600 text-white rounded-lg px-3 py-2 text-sm max-w-[80%]'
                : 'mr-auto bg-gray-100 text-gray-800 rounded-lg px-3 py-2 text-sm max-w-[80%]';
            bubble.textContent = text;

            const wrapper = document.createElement('div');
            wrapper.className = 'flex';
            wrapper.appendChild(bubble);

            chatWindow.appendChild(wrapper);
            chatWindow.scrollTop = chatWindow.scrollHeight;
        }

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const question = input.value.trim();
            if (!question) return;

            addMessage(question, 'user');
            input.value = '';
            input.disabled = true;

            const loadingBubble = document.createElement('div');
            loadingBubble.className = 'flex';
            loadingBubble.innerHTML = '<div class="mr-auto bg-gray-100 text-gray-400 rounded-lg px-3 py-2 text-sm italic">Stepwise is thinking...</div>';
            chatWindow.appendChild(loadingBubble);
            chatWindow.scrollTop = chatWindow.scrollHeight;

            try {
                const res = await fetch('/api/ask', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ session_id: SESSION_ID, question }),
                });

                const data = await res.json();
                loadingBubble.remove();

                if (!res.ok) {
                    addMessage('Something went wrong: ' + (data.error || 'unknown error'), 'bot');
                } else {
                    addMessage(data.answer, 'bot');
                }
            } catch (err) {
                loadingBubble.remove();
                addMessage('Could not reach the server.', 'bot');
            }

            input.disabled = false;
            input.focus();
        });
    </script>

</body>
</html>