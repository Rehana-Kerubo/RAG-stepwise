<?php

namespace App\Http\Controllers;

use App\Models\OnboardingSession;
use App\Models\Step;
use App\Models\StepProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StepProgressController extends Controller
{
    /**
     * Get all onboarding steps and their progress for a session.
     */
    public function index(Request $request, $sessionId)
    {
        $session = OnboardingSession::findOrFail($sessionId);

        $steps = Step::orderBy('order_number')->get();

        $progress = StepProgress::where('session_id', $session->id)
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
     * Validate and submit an onboarding step.
     */
    public function submit(Request $request, $sessionId, $stepId)
    {
        $session = OnboardingSession::findOrFail($sessionId);
        $step = Step::findOrFail($stepId);

        // Do not accept submissions for inactive sessions.
        if (strtolower($session->status) !== 'in_progress') {
            return response()->json([
                'message' => 'This onboarding session is not active.',
            ], 422);
        }

        // A step cannot be submitted before earlier steps are completed.
        $completedStepIds = StepProgress::where('session_id', $sessionId)
            ->whereNotNull('completed_at')
            ->pluck('step_id');

        $hasIncompletePreviousStep = Step::where(
            'order_number',
            '<',
            $step->order_number
        )
            ->whereNotIn('id', $completedStepIds)
            ->exists();

        if ($hasIncompletePreviousStep) {
            return response()->json([
                'message' => 'Please complete the earlier onboarding steps first.',
            ], 422);
        }

        // Every submission must contain an array.
        $request->validate([
            'submission_data' => 'required|array',
        ]);

        $rules = [];

        // Steps 1–3: ID, customer photo, and business-location evidence.
        if (in_array((int) $step->order_number, [1, 2, 3], true)) {
            $rules = [
                'submission_data.type' => 'required|in:document',
                'submission_data.filename' => 'required|string|max:255',
                'submission_data.mime_type' => 'required|in:image/jpeg,image/png,image/webp',
                'submission_data.response' => [
                     'required',
                     'string',
                     'max:11000000',
                     'regex:/^data:image\/(jpeg|png|webp);base64,/',
                ],
            ];
        }

        // Steps 4–6, 8, and 10–12: agent confirmations.
        elseif (in_array(
            (int) $step->order_number,
            [4, 5, 6, 8, 10, 11, 12],
            true
        )) {
            $rules = [
                'submission_data.type' => 'required|in:confirmation',
                'submission_data.response' => 'required|string',
            ];
        }

        // Step 7: POS activation code.
        elseif ((int) $step->order_number === 7) {
            $rules = [
                'submission_data.type' => 'required|in:activation_code',
                'submission_data.response' => 'required|string|max:100',
            ];
        }

        // Step 9: Till or Paybill request.
        elseif ((int) $step->order_number === 9) {
            $rules = [
                'submission_data.type' => 'required|in:linking_request',
                'submission_data.request_type' => 'required|in:Till,Paybill',
                'submission_data.response' => 'required|string',
            ];
        }

        // Step 13: staff account setup.
        elseif ((int) $step->order_number === 13) {
            $rules = [
                'submission_data.type' => 'required|in:staff_accounts',
                'submission_data.response' =>
                    'required|in:setup_required,not_applicable',
            ];
        }

        else {
            return response()->json([
                'message' => 'This onboarding step is not configured.',
            ], 422);
        }

        $validated = $request->validate($rules);

        // Find existing progress or create it.
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

        // Save the submission and mark the step complete.
        $submissionData = $validated['submission_data'];

        if (in_array((int) $step->order_number, [1, 2, 3], true)) {
            $dataUrl = $submissionData['response'];

            if (!preg_match(
            '/^data:image\/(jpeg|png|webp);base64,(.+)$/s',
             $dataUrl,
        $matches
    )) {
        return response()->json([
            'message' => 'Invalid image data.',
        ], 422);
    }

    $image = base64_decode($matches[2], true);

    if ($image === false || strlen($image) > 8 * 1024 * 1024) {
        return response()->json([
            'message' => 'Image is invalid or exceeds the 8 MB limit.',
        ], 422);
    }

    $extension = match ($matches[1]) {
        'jpeg' => 'jpg',
        'png' => 'png',
        'webp' => 'webp',
    };

    $actualMime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($image);

    if ($actualMime !== 'image/' . $matches[1]) {
        return response()->json([
            'message' => 'The image content does not match its file type.',
        ], 422);
    }

    $path = 'onboarding/' . $sessionId . '/' . Str::uuid() . '.' . $extension;

    Storage::disk('local')->put($path, $image);

    unset($submissionData['response']);

    $submissionData['file_path'] = $path;
    $submissionData['filename'] = basename($submissionData['filename']);
    $submissionData['mime_type'] = $actualMime;
}

$progress->submission_data = $submissionData;        $progress->attempts = ($progress->attempts ?? 0) + 1;
        $progress->completed_at = now();
        $progress->save();

        // Find the next step.
        $nextStep = Step::where(
            'order_number',
            '>',
            $step->order_number
        )
            ->orderBy('order_number')
            ->first();

        // Update the session status when onboarding finishes.
        if ($nextStep === null) {
            $session->status = 'completed';
            $session->completed_at = now();
        } else {
            $session->status = 'in_progress';
        }

        $session->save();

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
