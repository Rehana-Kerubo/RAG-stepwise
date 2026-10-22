<?php

namespace App\Http\Controllers;

use App\Models\ChatLog;
use App\Models\OnboardingSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Services\AlertRules;

class ChatController extends Controller
{
    public function ask(Request $request)
    {   
        set_time_limit(180);
        $validated = $request->validate([
            'session_id' => 'required|exists:onboarding_sessions,id',
            'step_id'    => 'nullable|exists:steps,id',
            'question'   => 'required|string',
        ]);

        // The agent may only ask about their own onboarding (404 otherwise).
        $session = OnboardingSession::ownedBy($request->user())->findOrFail($validated['session_id']);

        // Call the Flask RAG service
        $step = null;

    if ($validated['step_id']) {
    $step = \App\Models\Step::find($validated['step_id']);
    }

    try {
        $response = Http::timeout(120)->post(
        rtrim(env('FLASK_API_URL', 'http://localhost:5000'), '/') . '/ask',
        [
            'question' => $validated['question'],
            'gig' => $session->gig?->slug,
         ]
    );
    } catch (\Illuminate\Http\Client\ConnectionException $e) {
        return response()->json([
            'error' => 'Stepwise AI service did not respond: ' . $e->getMessage(),
        ], 502);
    }

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

        AlertRules::checkRepeatedHelp((int) $validated['session_id'], isset($validated['step_id']) ? (int) $validated['step_id'] : null);

        return response()->json([
            'answer'  => $chatLog->answer,
            'sources' => $data['sources'] ?? [],
            'log_id'  => $chatLog->id,
        ]);
    }
}