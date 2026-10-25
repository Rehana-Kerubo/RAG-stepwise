<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Alert;
use App\Models\ChatLog;
use App\Models\Gig;
use App\Models\OnboardingSession;
use App\Models\Step;
use App\Models\StepProgress;
use App\Services\AlertRules;
use App\Services\SessionStatus;
use Illuminate\Http\Request;

class ManagerDashboardController extends Controller
{
    /**
     * Overview tab: summary and every onboarding across agents.
     * An optional ?gig=<slug> narrows everything to one gig.
     */
    public function index(Request $request)
    {
        // Look for stalled onboardings each time the dashboard is opened.
        SessionStatus::reconcile();
        AlertRules::checkStalled();

        $gigs = Gig::orderBy('name')->get();

        // Unknown or empty slug means "all gigs".
        $selectedGig = $gigs->firstWhere('slug', $request->query('gig'));

        // Step count per gig, e.g. [1 => 13, 2 => 8], so each onboarding is
        // measured against its own gig.
        $stepTotals = Step::selectRaw('gig_id, COUNT(*) as total')
            ->groupBy('gig_id')
            ->pluck('total', 'gig_id');

        $sessions = OnboardingSession::with(['agent', 'gig'])
            ->when($selectedGig, fn ($q) => $q->where('gig_id', $selectedGig->id))
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
            'alerts' => $this->openAlerts($selectedGig)->count(),
            'agents' => Agent::where('status', 'active')->count(),
        ];

        return view('manager.dashboard', compact(
            'sessions', 'stepTotals', 'counts', 'gigs', 'selectedGig'
        ));
    }

    /**
     * Alerts tab: open alerts, newest first. Same ?gig=<slug> filter as Overview.
     */
    public function alerts(Request $request)
    {
        AlertRules::checkStalled();

        $gigs = Gig::orderBy('name')->get();
        $selectedGig = $gigs->firstWhere('slug', $request->query('gig'));

        $alerts = $this->openAlerts($selectedGig)
            ->with(['session.agent', 'session.gig', 'step'])
            ->orderByDesc('created_at')
            ->get();

        return view('manager.alerts', compact('alerts', 'gigs', 'selectedGig'));
    }

    /**
     * Agents tab: every agent with how many onboardings they have running and finished.
     */
    public function agents()
    {
        SessionStatus::reconcile();

        $agents = Agent::withCount([
                'sessions as in_progress' => fn ($q) => $q->where('status', 'in_progress'),
                'sessions as completed' => fn ($q) => $q->where('status', 'completed'),
            ])
            ->orderBy('name')
            ->get();

        return view('manager.agents', compact('agents'));
    }

    /**
     * One onboarding in detail: steps, alerts, and what the agent asked the AI.
     */
    public function session($sessionId)
    {
        $session = OnboardingSession::with(['agent', 'gig'])->findOrFail($sessionId);

        // Only the steps of this onboarding's own gig.
        $steps = Step::where('gig_id', $session->gig_id)
            ->orderBy('order_number')
            ->get();

        $progress = StepProgress::where('session_id', $session->id)
            ->get()
            ->keyBy('step_id');

        $alerts = Alert::with('step')
            ->where('session_id', $session->id)
            ->orderByDesc('created_at')
            ->get();

        $chat = ChatLog::with('step')
            ->where('session_id', $session->id)
            ->orderBy('created_at')
            ->get();

        return view('manager.session', compact('session', 'steps', 'progress', 'alerts', 'chat'));
    }

    /**
     * Mark an alert as dealt with.
     */
    public function resolve($alertId)
    {
        $alert = Alert::findOrFail($alertId);

        $alert->is_resolved = true;
        $alert->save();

        return redirect()->back();
    }

    /**
     * Query for unresolved alerts, limited to one gig when one is chosen.
     */
    private function openAlerts($gig)
    {
        return Alert::where('is_resolved', false)
            ->when($gig, fn ($q) => $q->whereHas(
                'session',
                fn ($s) => $s->where('gig_id', $gig->id)
            ));
    }
}
