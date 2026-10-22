<?php

namespace App\Http\Controllers;

use App\Models\ChatLog;
use App\Models\OnboardingSession;
use App\Models\Step;
use App\Models\StepProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StepProgressController extends Controller
{
    /**
     * Get the onboarding steps of the session's gig, with the agent's progress.
     */
    public function index(Request $request, $sessionId)
    {
        $session = OnboardingSession::ownedBy($request->user())->findOrFail($sessionId);

        $steps = Step::where('gig_id', $session->gig_id)
            ->orderBy('order_number')
            ->get();

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
                'input_type' => $step->input_type,
                'input_config' => $step->input_config,
                'completed' => $p && $p->completed_at !== null,
                'progress_id' => $p?->id,
            ];
        });

        return response()->json($result);
    }

    /**
     * Validation rules for a step, chosen from its input type and config.
     * Returns null when the step is not configured.
     */
    private function rulesFor(Step $step): ?array
    {
        $config = $step->input_config ?? [];
        $submissionType = $config['submission_type'] ?? null;

        if (! $submissionType) {
            return null;
        }

        $typeRule = ['required', Rule::in([$submissionType])];

        switch ($step->input_type) {

            case 'photo':
                return [
                    'submission_data.type' => $typeRule,
                    'submission_data.filename' => 'required|string|max:255',
                    'submission_data.mime_type' => 'required|in:image/jpeg,image/png,image/webp',
                    'submission_data.response' => [
                        'required',
                        'string',
                        'max:11000000',
                        'regex:/^data:image\/(jpeg|png|webp);base64,/',
                    ],
                ];

            case 'confirm':
                return [
                    'submission_data.type' => $typeRule,
                    'submission_data.response' => 'required|string',
                ];

            case 'text':
                return [
                    'submission_data.type' => $typeRule,
                    'submission_data.response' => 'required|string|max:' . ($config['max_length'] ?? 255),
                ];

            case 'select':
                $field = $config['field'] ?? 'response';
                $allowed = collect($config['options'] ?? [])->pluck('value')->all();

                if (! $allowed) {
                    return null;
                }

                $rules = [
                    'submission_data.type' => $typeRule,
                    "submission_data.$field" => ['required', Rule::in($allowed)],
                ];

                if ($field !== 'response') {
                    $rules['submission_data.response'] = 'required|string';
                }

                return $rules;
        }

        return null;
    }

    /**
     * Validate and submit an onboarding step.
     */
    public function submit(Request $request, $sessionId, $stepId)
    {
        $session = OnboardingSession::ownedBy($request->user())->findOrFail($sessionId);
        $step = Step::findOrFail($stepId);

        // The step must belong to the gig this session is running.
        if ((int) $step->gig_id !== (int) $session->gig_id) {
            return response()->json([
                'message' => 'This step does not belong to this onboarding session.',
            ], 422);
        }

        // Do not accept submissions for inactive sessions.
        if (strtolower($session->status) !== 'in_progress') {
            return response()->json([
                'message' => 'This onboarding session is not active.',
            ], 422);
        }

        // A step cannot be submitted before earlier steps of the same gig are completed.
        $completedStepIds = StepProgress::where('session_id', $sessionId)
            ->whereNotNull('completed_at')
            ->pluck('step_id');

        $hasIncompletePreviousStep = Step::where('gig_id', $step->gig_id)
            ->where('order_number', '<', $step->order_number)
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

        $rules = $this->rulesFor($step);

        if ($rules === null) {
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

        if ($step->input_type === 'photo') {
            $dataUrl = $submissionData['response'];

            if (! preg_match(
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

        $progress->submission_data = $submissionData;
        $progress->attempts = ($progress->attempts ?? 0) + 1;
        $progress->completed_at = now();
        $progress->save();

        // Find the next step within the same gig.
        $nextStep = Step::where('gig_id', $step->gig_id)
            ->where('order_number', '>', $step->order_number)
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
                'input_type' => $nextStep->input_type,
                'input_config' => $nextStep->input_config,
            ] : null,
            'onboarding_complete' => $nextStep === null,
        ]);
    }


    // Short AI tips for one step. Generated once, then cached for everyone.

    public function guidance(Request $request, $stepId)
    {
        set_time_limit(180);
        $step = Step::findOrFail($stepId);

        $tip = Cache::get('step_tip_' . $step->id);

        if (! $tip) {

            $question = "You are helping a sales agent complete one onboarding step. "
                . "Step: {$step->title}. What this step requires: {$step->instructions} "
                . "Give 3 short, practical tips for doing this step well, one per line, no numbering. "
                . "Only talk about this step. Do not mention other steps.";

            try {
                $response = Http::timeout(120)->post(
                    rtrim(env('FLASK_API_URL', 'http://localhost:5000'), '/') . '/ask',
                    ['question' => $question, 'max_tokens' => 130, 'top_k' => 2, 'gig' => $step->gig?->slug,]
                );
            } catch (\Exception $e) {
                return response()->json(['tip' => null]);
            }

            if (! $response->successful()) {
                return response()->json(['tip' => null]);
            }

            $tip = trim((string) $response->json('answer'));

            if ($tip !== '') {
                Cache::forever('step_tip_' . $step->id, $tip);
            }
        }

        return response()->json(['tip' => $tip ?: null]);
    }
}