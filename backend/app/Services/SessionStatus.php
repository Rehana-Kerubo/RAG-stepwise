<?php

namespace App\Services;

use App\Models\OnboardingSession;
use App\Models\Step;
use App\Models\StepProgress;

class SessionStatus
{
    /**
     * Mark an onboarding as completed once every step has a completed record.
     * Normally the status is set when the last step is submitted; this repairs
     * any onboarding that finished some other way (older data, manual edits).
     */
    public static function reconcile(): void
    {
        $totalSteps = Step::count();

        if ($totalSteps === 0) {
            return;
        }

        $sessions = OnboardingSession::where('status', 'in_progress')
            ->withCount(['stepProgress as saved_steps' => fn ($q) => $q->whereNotNull('completed_at')])
            ->get();

        foreach ($sessions as $session) {
            if ($session->saved_steps >= $totalSteps) {
                $session->status = 'completed';
                $session->completed_at = StepProgress::where('session_id', $session->id)
                    ->whereNotNull('completed_at')
                    ->max('completed_at') ?? now();
                $session->save();
            }
        }
    }
}