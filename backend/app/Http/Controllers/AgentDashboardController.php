<?php

namespace App\Http\Controllers;

use App\Models\Gig;
use App\Models\OnboardingSession;
use App\Models\Step;
use App\Services\SessionStatus;
use Illuminate\Http\Request;

class AgentDashboardController extends Controller
{
    /**
     * The logged-in agent's onboardings, with progress and open alerts.
     */
    public function show(Request $request)
    {
        SessionStatus::reconcile();
        $agent = $request->user()->agent;

        // How many steps each gig has, so every session's ruler matches its own gig.
        $stepCounts = Step::selectRaw('gig_id, COUNT(*) as total')
            ->groupBy('gig_id')
            ->pluck('total', 'gig_id');

        // Gigs the agent can start (a gig with no steps cannot be run).
        $gigs = Gig::has('steps')->orderBy('name')->get();

        $sessions = OnboardingSession::where('agent_id', $agent->id)
            ->with('gig')
            ->withCount([
                'stepProgress as saved_steps' => fn ($q) => $q->whereNotNull('completed_at'),
                'alerts as open_alerts' => fn ($q) => $q->where('is_resolved', false),
            ])
            ->withMax('stepProgress as last_activity', 'updated_at')
            ->orderByDesc('started_at')
            ->get();

        $counts = [
            'in_progress' => $sessions->where('status', 'in_progress')->count(),
            'completed' => $sessions->where('status', 'completed')->count(),
            'alerts' => (int) $sessions->sum('open_alerts'),
        ];

        return view('agent.dashboard', compact('agent', 'sessions', 'stepCounts', 'gigs', 'counts'));
    }

    /**
     * Open the wizard for one of this agent's own onboardings.
     */
    public function onboarding(Request $request, $sessionId)
    {
        $session = OnboardingSession::ownedBy($request->user())->findOrFail($sessionId);

        return view('chat', ['sessionId' => $session->id]);
    }

    /**
     * Start a new onboarding of the chosen gig for the logged-in agent and open it.
     */
    public function startSession(Request $request)
    {
        $agent = $request->user()->agent;

        $validated = $request->validate([
            'gig_id' => ['required', 'exists:gigs,id'],
        ]);

        $session = OnboardingSession::create([
            'agent_id' => $agent->id,
            'gig_id' => $validated['gig_id'],
            'started_at' => now(),
            'status' => 'in_progress',
        ]);

        return redirect('/onboarding/' . $session->id);
    }
}