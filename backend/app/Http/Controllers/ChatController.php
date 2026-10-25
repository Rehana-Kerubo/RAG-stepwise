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
            'question'   => 'required|string|max:500',
        ]);

        // The agent may only ask about their own onboarding (404 otherwise).
        $session = OnboardingSession::ownedBy($request->user())->findOrFail($validated['session_id']);

        // step_id may be missing from the request entirely, not only null
        $stepId = $validated['step_id'] ?? null;
        $step = $stepId ? \App\Models\Step::find($stepId) : null;

        // The step must be part of this onboarding's gig, or the question would be
        // logged (and counted for alerts and step difficulty) against the wrong gig.
        if ($step && (int) $step->gig_id !== (int) $session->gig_id) {
            return response()->json([
                'message' => 'This step does not belong to this onboarding.',
            ], 422);
        }

        // Call the Flask RAG service
    try {
        $response = Http::timeout(120)->post(
        config('services.flask.url') . '/ask',
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