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
     * Overview: summary, open alerts, and every onboarding across agents.
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

        $alerts = Alert::with(['session.agent', 'session.gig', 'step'])
            ->where('is_resolved', false)
            ->when($selectedGig, fn ($q) => $q->whereHas(
                'session',
                fn ($s) => $s->where('gig_id', $selectedGig->id)
            ))
            ->orderByDesc('created_at')
            ->get();

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
            'alerts' => $alerts->count(),
            'agents' => Agent::where('status', 'active')->count(),
        ];

        return view('manager.dashboard', compact(
            'alerts', 'sessions', 'stepTotals', 'counts', 'gigs', 'selectedGig'
        ));
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
}
