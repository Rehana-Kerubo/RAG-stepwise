<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\ChatLog;
use App\Models\OnboardingSession;
use App\Models\Step;
use App\Models\StepProgress;
use Carbon\Carbon;

/**
 * Rules that raise alerts for the manager.
 *
 * The alerts table has no "type" column, so each rule writes its reason with a
 * fixed beginning ("Asked the AI..." / "No activity...") and uses that to avoid
 * raising the same alert twice.
 */
class AlertRules
{
    // Change these to tune how sensitive the alerts are.
    public const HELP_THRESHOLD = 3;   // questions about one step before an alert
    public const STALL_MINUTES = 30;   // minutes without activity before an alert

    /**
     * Rule 1: the agent keeps asking the AI about the same step.
     * Called right after a chat question is saved.
     */
    public static function checkRepeatedHelp(int $sessionId, ?int $stepId): void
    {
        if (! $stepId) {
            return;
        }

        $count = ChatLog::where('session_id', $sessionId)
            ->where('step_id', $stepId)
            ->count();

        // Fires once, when the threshold is reached, so one stuck step is one alert.
        if ($count !== self::HELP_THRESHOLD) {
            return;
        }

        if (self::openAlertExists($sessionId, $stepId, 'Asked the AI%')) {
            return;
        }

        Alert::create([
            'session_id' => $sessionId,
            'step_id' => $stepId,
            'reason' => "Asked the AI {$count} times about this step",
        ]);
    }

    /**
     * Rule 2: an unfinished onboarding has gone quiet.
     * Checked when the manager dashboard is opened.
     */
    public static function checkStalled(): void
    {
        $cutoff = now()->subMinutes(self::STALL_MINUTES);

        $sessions = OnboardingSession::where('status', 'in_progress')
            ->withMax('stepProgress as last_progress', 'updated_at')
            ->withMax('chatLogs as last_chat', 'created_at')
            ->get();

        foreach ($sessions as $session) {

            $lastActivity = collect([
                $session->started_at,
                $session->last_progress,
                $session->last_chat,
            ])
                ->filter()
                ->map(fn ($time) => Carbon::parse($time))
                ->max();

            if (! $lastActivity || $lastActivity->gt($cutoff)) {
                continue;
            }

            // The step the agent is stuck on: the first step of THIS session's gig
            // that is not yet completed.
            $doneIds = StepProgress::where('session_id', $session->id)
                ->whereNotNull('completed_at')
                ->pluck('step_id');

            $currentStep = Step::where('gig_id', $session->gig_id)
                ->whereNotIn('id', $doneIds)
                ->orderBy('order_number')
                ->first();

            if (! $currentStep) {
                continue;
            }

            if (self::openAlertExists($session->id, $currentStep->id, 'No activity%')) {
                continue;
            }

            $minutes = (int) round($lastActivity->diffInMinutes(now(), true));

            Alert::create([
                'session_id' => $session->id,
                'step_id' => $currentStep->id,
                'reason' => "No activity for {$minutes} minutes",
            ]);
        }
    }

    private static function openAlertExists(int $sessionId, int $stepId, string $reasonPattern): bool
    {
        return Alert::where('session_id', $sessionId)
            ->where('step_id', $stepId)
            ->where('is_resolved', false)
            ->where('reason', 'like', $reasonPattern)
            ->exists();
    }
}