<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Gig;
use App\Models\OnboardingSession;
use App\Models\Step;
use App\Services\SessionStatus;
use Illuminate\Http\Request;

class AgentDashboardController extends Controller
{
    /**
     * "Who is using the app?" screen. There is no login in the demo,
     * so the agent is chosen from a list.
     */
    public function index()
    {
        SessionStatus::reconcile();
        $agents = Agent::where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('agent.select', compact('agents'));
    }

    /**
     * One agent's onboardings, with progress and open alerts.
     */
    public function show($agentId)
    {
        $agent = Agent::findOrFail($agentId);

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
     * Start a new onboarding of the chosen gig for this agent and open it.
     */
    public function startSession(Request $request, $agentId)
    {
        $agent = Agent::findOrFail($agentId);

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