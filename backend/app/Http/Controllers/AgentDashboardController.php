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
     * Home tab: summary, the onboarding to continue, and the gig picker.
     */
    public function show(Request $request)
    {
        SessionStatus::reconcile();
        $agent = $request->user()->agent;

        $stepCounts = $this->stepCounts();
        $sessions = $this->sessionsFor($agent);

        // Gigs the agent can start (a gig with no steps cannot be run).
        $gigs = Gig::has('steps')->orderBy('name')->get();

        $counts = [
            'in_progress' => $sessions->where('status', 'in_progress')->count(),
            'completed' => $sessions->where('status', 'completed')->count(),
            'alerts' => (int) $sessions->sum('open_alerts'),
        ];

        // The newest unfinished onboarding, offered as "Continue where you left off".
        $current = $sessions->firstWhere('status', 'in_progress');

        return view('agent.dashboard', compact('agent', 'stepCounts', 'gigs', 'counts', 'current'));
    }

    /**
     * My Onboardings tab: every onboarding of the logged-in agent.
     */
    public function onboardings(Request $request)
    {
        SessionStatus::reconcile();
        $agent = $request->user()->agent;

        $stepCounts = $this->stepCounts();
        $sessions = $this->sessionsFor($agent);

        return view('agent.onboardings', compact('agent', 'sessions', 'stepCounts'));
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

    /**
     * How many steps each gig has, e.g. [1 => 13, 2 => 8], so every
     * session's ruler matches its own gig.
     */
    private function stepCounts()
    {
        return Step::selectRaw('gig_id, COUNT(*) as total')
            ->groupBy('gig_id')
            ->pluck('total', 'gig_id');
    }

    /**
     * The agent's onboardings, newest first, with saved steps, open alerts
     * and the time of the last saved step.
     */
    private function sessionsFor($agent)
    {
        return OnboardingSession::where('agent_id', $agent->id)
            ->with('gig')
            ->withCount([
                'stepProgress as saved_steps' => fn ($q) => $q->whereNotNull('completed_at'),
                'alerts as open_alerts' => fn ($q) => $q->where('is_resolved', false),
            ])
            ->withMax('stepProgress as last_activity', 'updated_at')
            ->orderByDesc('started_at')
            ->get();
    }
}
