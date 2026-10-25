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
use Carbon\Carbon;
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
            // With a gig chosen: active agents assigned to that gig.
            'agents' => Agent::where('status', 'active')
                ->when($selectedGig, fn ($q) => $q->whereHas('gigs', fn ($g) => $g->whereKey($selectedGig->id)))
                ->count(),
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
                'gigs',
                'sessions as in_progress' => fn ($q) => $q->where('status', 'in_progress'),
                'sessions as completed' => fn ($q) => $q->where('status', 'completed'),
            ])
            ->orderBy('name')
            ->get();

        return view('manager.agents', compact('agents'));
    }

    /**
     * One agent: every gig with an assign/unassign switch and the agent's progress on it.
     */
    public function agent($agentId)
    {
        SessionStatus::reconcile();

        $agent = Agent::findOrFail($agentId);
        $gigs = Gig::withCount('steps')->orderBy('name')->get();
        $assignedIds = $agent->gigs()->pluck('gigs.id');

        // This agent's onboardings, grouped per gig: [gig_id => [session, ...]]
        $sessionsByGig = OnboardingSession::where('agent_id', $agent->id)
            ->withCount(['stepProgress as saved_steps' => fn ($q) => $q->whereNotNull('completed_at')])
            ->orderByDesc('started_at')
            ->get()
            ->groupBy('gig_id');

        return view('manager.agent', compact('agent', 'gigs', 'assignedIds', 'sessionsByGig'));
    }

    /**
     * Assign (assign=1) or unassign (assign=0) a gig for an agent.
     * Sending the wanted state, not "toggle", means a double tap cannot flip it back.
     * Unassigning only stops NEW onboardings; ones already started keep working.
     */
    public function assignGig(Request $request, $agentId, $gigId)
    {
        $agent = Agent::findOrFail($agentId);
        $gig = Gig::findOrFail($gigId);

        if ($request->boolean('assign')) {
            // Already assigned: leave the original row (and date) alone.
            if (! $agent->gigs()->whereKey($gig->id)->exists()) {
                $agent->gigs()->attach($gig->id, [
                    'assigned_by' => $request->user()->id,
                    'assigned_at' => now(),
                ]);
            }
            $message = "{$gig->name} assigned to {$agent->name}.";
        } else {
            $agent->gigs()->detach($gig->id);
            $message = "{$gig->name} removed from {$agent->name}.";
        }

        return redirect('/manager/agents/' . $agent->id)->with('status', $message);
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
     * Step difficulty for one gig: per step, how many AI questions were asked,
     * how many alerts it caused and how long agents typically spend on it.
     * Uses only chat_logs, alerts and step_progress.
     */
    public function insights(Request $request)
    {
        $gigs = Gig::has('steps')->orderBy('name')->get();
        $gig = $gigs->firstWhere('slug', $request->query('gig')) ?? $gigs->first();

        if (! $gig) {
            return view('manager.insights', ['gigs' => $gigs, 'gig' => null, 'rows' => collect(), 'worst' => null]);
        }

        $steps = Step::where('gig_id', $gig->id)->orderBy('order_number')->get();

        $questions = ChatLog::whereIn('step_id', $steps->pluck('id'))
            ->selectRaw('step_id, COUNT(*) as total')
            ->groupBy('step_id')
            ->pluck('total', 'step_id');

        $alerts = Alert::whereIn('step_id', $steps->pluck('id'))
            ->selectRaw('step_id, COUNT(*) as total')
            ->groupBy('step_id')
            ->pluck('total', 'step_id');

        $minutes = $this->typicalMinutesPerStep($steps);

        $rows = $steps->map(fn ($step) => [
            'step' => $step,
            'questions' => (int) ($questions[$step->id] ?? 0),
            'alerts' => (int) ($alerts[$step->id] ?? 0),
            'minutes' => $minutes[$step->id] ?? null,
        ]);

        // Hardest step: the most AI questions + alerts; the longer time breaks a tie.
        $worst = $rows
            ->sort(fn ($a, $b) => [$b['questions'] + $b['alerts'], $b['minutes'] ?? 0]
                              <=> [$a['questions'] + $a['alerts'], $a['minutes'] ?? 0])
            ->first();

        // No data at all: nothing to call "hardest".
        if ($worst && $worst['questions'] + $worst['alerts'] === 0 && ! $worst['minutes']) {
            $worst = null;
        }

        return view('manager.insights', compact('gigs', 'gig', 'rows', 'worst'));
    }

    /**
     * Typical minutes spent on each step, as [step_id => minutes].
     *
     * step_progress.started_at is written when the step is submitted, so time on a
     * step is measured from the PREVIOUS step's save (or the onboarding's start, for
     * the first step) to this step's save. The median is used, not the mean, so one
     * agent who left a step open overnight does not distort the number.
     */
    private function typicalMinutesPerStep($steps)
    {
        $orderOf = $steps->pluck('order_number', 'id');

        $bySession = StepProgress::whereIn('step_id', $steps->pluck('id'))
            ->whereNotNull('completed_at')
            ->with('session:id,started_at,created_at')
            ->get()
            ->groupBy('session_id');

        $durations = [];

        foreach ($bySession as $progress) {
            $session = $progress->first()->session;
            $previous = Carbon::parse($session->started_at ?? $session->created_at);

            foreach ($progress->sortBy(fn ($p) => $orderOf[$p->step_id]) as $p) {
                $saved = Carbon::parse($p->completed_at);
                $durations[$p->step_id][] = $previous->diffInSeconds($saved, true) / 60;
                $previous = $saved;
            }
        }

        return collect($durations)->map(fn ($list) => round(collect($list)->median(), 1));
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
