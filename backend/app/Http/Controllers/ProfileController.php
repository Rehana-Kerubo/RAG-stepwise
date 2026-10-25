<?php

namespace App\Http\Controllers;

use App\Models\OnboardingSession;
use App\Models\StepProgress;
use App\Services\SessionStatus;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * The Profile tab for the logged-in user (agent or manager).
     * Agents also see their own numbers, counted from existing tables.
     */
    public function show(Request $request)
    {
        $user = $request->user();
        $agent = $user->agent;
        $stats = null;

        if ($agent) {
            SessionStatus::reconcile();

            $sessions = OnboardingSession::where('agent_id', $agent->id);

            $stats = [
                'completed' => (clone $sessions)->where('status', 'completed')->count(),
                'in_progress' => (clone $sessions)->where('status', 'in_progress')->count(),

                // Steps saved on the server across all of this agent's onboardings
                'steps_saved' => StepProgress::whereNotNull('completed_at')
                    ->whereIn('session_id', (clone $sessions)->select('id'))
                    ->count(),
            ];
        }

        return view('profile', compact('user', 'agent', 'stats'));
    }

    /**
     * An agent changes their own region.
     */
    public function updateRegion(Request $request)
    {
        $validated = $request->validate([
            'region' => ['nullable', 'string', 'max:100'],
        ]);

        $agent = $request->user()->agent;
        $agent->region = $validated['region'] ?: null;
        $agent->save();

        return redirect('/profile')->with('status', 'Region saved.');
    }
}
