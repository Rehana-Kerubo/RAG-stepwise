<?php

namespace App\Http\Controllers;

use App\Models\ChatLog;
use App\Models\OnboardingSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ChatController extends Controller
{
    public function ask(Request $request)
    {
        $validated = $request->validate([
            'session_id' => 'required|exists:onboarding_sessions,id',
            'step_id'    => 'nullable|exists:steps,id',
            'question'   => 'required|string',
        ]);

        // Call the Flask RAG service
        $response = Http::timeout(60)->post(
            rtrim(env('FLASK_API_URL', 'http://localhost:5000'), '/') . '/ask',
            ['question' => $validated['question']]
        );

        if (! $response->successful()) {
            return response()->json([
                'error' => 'Stepwise AI service did not respond correctly.',
            ], 502);
        }

        $data = $response->json();

        // Save the exchange
        $chatLog = ChatLog::create([
            'session_id' => $validated['session_id'],
            'step_id'    => $validated['step_id'] ?? null,
            'question'   => $validated['question'],
            'answer'     => $data['answer'] ?? '',
            'sources'    => isset($data['sources']) ? implode(', ', $data['sources']) : null,
        ]);

        return response()->json([
            'answer'  => $chatLog->answer,
            'sources' => $data['sources'] ?? [],
            'log_id'  => $chatLog->id,
        ]);
    }
}