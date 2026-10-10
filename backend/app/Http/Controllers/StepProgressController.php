<?php

namespace App\Http\Controllers;

use App\Models\Step;
use App\Models\StepProgress;
use Illuminate\Http\Request;

class StepProgressController extends Controller
{
    /**
     * Get all onboarding steps and their progress for a session.
     */
    public function index(Request $request, $sessionId)
    {
        $steps = Step::orderBy('order_number')->get();

        $progress = StepProgress::where('session_id', $sessionId)
            ->get()
            ->keyBy('step_id');

        $result = $steps->map(function ($step) use ($progress) {
            $p = $progress->get($step->id);

            return [
                'id' => $step->id,
                'title' => $step->title,
                'instructions' => $step->instructions,
                'guidance_label' => $step->guidance_label,
                'validation_rules' => $step->validation_rules,
                'order_number' => $step->order_number,
                'completed' => $p && $p->completed_at !== null,
                'progress_id' => $p?->id,
            ];
        });

        return response()->json($result);
    }

    /**
     * Submit the current onboarding step.
     */
    public function submit(Request $request, $sessionId, $stepId)
    {
        /*
         * Make sure the submitted data exists.
         *
         * We are deliberately keeping this flexible for now because
         * different onboarding steps will collect different information.
         */
        $validated = $request->validate([
            'submission_data' => 'required|array',
        ]);

        $step = Step::findOrFail($stepId);

        /*
         * Find existing progress for this step or create it.
         */
        $progress = StepProgress::firstOrCreate(
            [
                'session_id' => $sessionId,
                'step_id' => $stepId,
            ],
            [
                'started_at' => now(),
                'attempts' => 0,
            ]
        );

        /*
         * Record the submission.
         */
        $progress->submission_data = $validated['submission_data'];
        $progress->attempts = ($progress->attempts ?? 0) + 1;
        $progress->completed_at = now();

        $progress->save();

        /*
         * Find the next step in the onboarding sequence.
         */
        $nextStep = Step::where('order_number', '>', $step->order_number)
            ->orderBy('order_number')
            ->first();

        return response()->json([
            'success' => true,
            'message' => 'Step completed successfully.',
            'completed_step' => [
                'id' => $step->id,
                'title' => $step->title,
            ],
            'next_step' => $nextStep ? [
                'id' => $nextStep->id,
                'title' => $nextStep->title,
                'instructions' => $nextStep->instructions,
                'guidance_label' => $nextStep->guidance_label,
                'validation_rules' => $nextStep->validation_rules,
                'order_number' => $nextStep->order_number,
            ] : null,
            'onboarding_complete' => $nextStep === null,
        ]);
    }
}