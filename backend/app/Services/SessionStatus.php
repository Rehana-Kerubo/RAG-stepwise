<?php

namespace App\Services;

use App\Models\OnboardingSession;
use App\Models\Step;
use App\Models\StepProgress;

class SessionStatus
{
    /**
     * Mark an onboarding as completed once every step of ITS OWN gig has a
     * completed record. Normally the status is set when the last step is
     * submitted; this repairs any onboarding that finished some other way
     * (older data, manual edits).
     */
    public static function reconcile(): void
    {
        // Step count per gig, e.g. [1 => 13, 2 => 7]
        $totals = Step::selectRaw('gig_id, COUNT(*) as total')
            ->groupBy('gig_id')
            ->pluck('total', 'gig_id');

        if ($totals->isEmpty()) {
            return;
        }

        $sessions = OnboardingSession::where('status', 'in_progress')
            ->withCount(['stepProgress as saved_steps' => fn ($q) => $q->whereNotNull('completed_at')])
            ->get();

        foreach ($sessions as $session) {
            $total = (int) ($totals[$session->gig_id] ?? 0);

            if ($total > 0 && $session->saved_steps >= $total) {
                $session->status = 'completed';
                $session->completed_at = StepProgress::where('session_id', $session->id)
                    ->whereNotNull('completed_at')
                    ->max('completed_at') ?? now();
                $session->save();
            }
        }
    }
}