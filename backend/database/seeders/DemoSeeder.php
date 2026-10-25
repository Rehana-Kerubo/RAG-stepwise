<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\Gig;
use App\Models\Step;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Demo data so the manager dashboard looks alive for the panel.
 * Run: php artisan db:seed --class=DemoSeeder   (after StepSeeder)
 *
 * Safe to re-run: it only touches its own three demo agents. Their old
 * onboardings are deleted first (progress, alerts and chat logs go with them
 * through the foreign keys) and everything is rebuilt relative to "now".
 * No photos are created: photo steps only record that a photo was taken.
 */
class DemoSeeder extends Seeder
{
    // Minutes each step usually takes, so the hard steps stand out in the
    // step difficulty view (POS step 7 and 9, Airtel step 6).
    private const POS_MINUTES = [4, 3, 3, 5, 2, 4, 14, 4, 12, 6, 5, 3, 4];
    private const AIRTEL_MINUTES = [3, 4, 3, 4, 5, 9, 4, 3];

    public function run(): void
    {
        $pos = Gig::where('slug', 'pos-merchant-onboarding')->firstOrFail();
        $airtel = Gig::where('slug', 'airtel-line-registration')->firstOrFail();

        $grace = $this->agent('Grace Achieng', '0711 000 101', 'Nairobi CBD', 'grace@stepwise.test');
        $kevin = $this->agent('Kevin Mwangi', '0711 000 102', 'Mombasa', 'kevin@stepwise.test');
        $faith = $this->agent('Faith Njeri', '0711 000 103', 'Nakuru', 'faith@stepwise.test');

        // Start clean for these three agents only
        DB::table('onboarding_sessions')->whereIn('agent_id', [$grace->id, $kevin->id, $faith->id])->delete();

        // Gig assignments (agent_gig)
        $now = now();
        $assigned = ['assigned_at' => $now->copy()->subWeek()];

        $grace->gigs()->syncWithoutDetaching([$pos->id => $assigned, $airtel->id => $assigned]);
        $kevin->gigs()->syncWithoutDetaching([$pos->id => $assigned]);
        $faith->gigs()->syncWithoutDetaching([$pos->id => $assigned, $airtel->id => $assigned]);

        // Grace: finished a POS onboarding 3 days ago, asked about the activation code
        $s = $this->onboarding($grace, $pos, $now->copy()->subDays(3)->setTime(9, 15), 13);
        $this->questions($s, $pos, 7, ['Where do I find the POS activation code?', 'The activation code is not showing on my dashboard, what now?', 'How many times can I try the activation code?']);
        $this->alert($s, $pos, 7, 'Asked the AI 3 times about this step', resolved: true);

        // Grace: Airtel registration in progress right now (3 of 8 done)
        $s = $this->onboarding($grace, $airtel, $now->copy()->subMinutes(14), 3);
        $this->questions($s, $airtel, 4, ['Should I type the ID number with spaces?']);

        // Kevin: finished a POS onboarding yesterday, Till vs Paybill confused him
        $s = $this->onboarding($kevin, $pos, $now->copy()->subDay()->setTime(14, 0), 13);
        $this->questions($s, $pos, 9, ['What is the difference between a Till and a Paybill?', 'Which documents do I need for a Paybill?']);

        // Kevin: STALLED on the POS activation code (6 of 13 done, quiet for hours)
        $s = $this->onboarding($kevin, $pos, $now->copy()->subHours(3), 6);
        $this->questions($s, $pos, 7, ['I entered the activation code and it says invalid', 'Does the activation code expire?', 'Who do I call if the activation code keeps failing?']);
        $this->alert($s, $pos, 7, 'Asked the AI 3 times about this step');
        $this->alert($s, $pos, 7, 'No activity for ' . (int) $this->lastActivity($s)->diffInMinutes($now, true) . ' minutes');

        // Faith: finished an Airtel registration 2 days ago
        $s = $this->onboarding($faith, $airtel, $now->copy()->subDays(2)->setTime(11, 30), 8);
        $this->questions($s, $airtel, 6, ['What does the customer dial to check their registration?', 'The number does not appear under *106#, what should I do?']);

        // Faith: STALLED on the *106# check (5 of 8 done)
        $s = $this->onboarding($faith, $airtel, $now->copy()->subHours(2), 5);
        $this->questions($s, $airtel, 6, ['Customer says *106# shows a different ID type', 'How long does *106# take to update?', 'Can I finish without the *106# check?']);
        $this->alert($s, $airtel, 6, 'Asked the AI 3 times about this step');
        $this->alert($s, $airtel, 6, 'No activity for ' . (int) $this->lastActivity($s)->diffInMinutes($now, true) . ' minutes');

        // Faith: just started a POS onboarding (2 of 13 done)
        $this->onboarding($faith, $pos, $now->copy()->subMinutes(8), 2);
    }

    /**
     * A demo agent, plus a password login on local (like DemoUserSeeder).
     */
    private function agent(string $name, string $phone, string $region, string $email): Agent
    {
        $agent = Agent::updateOrCreate(
            ['phone' => $phone],
            ['name' => $name, 'region' => $region, 'status' => 'active']
        );

        if (app()->isLocal()) {
            User::updateOrCreate(
                ['email' => $email],
                ['name' => $name, 'role' => 'agent', 'agent_id' => $agent->id, 'password' => 'password']
            );
        }

        return $agent;
    }

    /**
     * One onboarding with its first $done steps saved, one after another.
     * Completed when every step of the gig is done. Returns the session id.
     */
    private function onboarding(Agent $agent, Gig $gig, Carbon $startedAt, int $done): int
    {
        $steps = Step::where('gig_id', $gig->id)->orderBy('order_number')->get();
        $minutes = $gig->slug === 'airtel-line-registration' ? self::AIRTEL_MINUTES : self::POS_MINUTES;

        $sessionId = DB::table('onboarding_sessions')->insertGetId([
            'agent_id' => $agent->id,
            'gig_id' => $gig->id,
            'status' => 'in_progress',
            'started_at' => $startedAt,
            'created_at' => $startedAt,
            'updated_at' => $startedAt,
        ]);

        $time = $startedAt->copy();

        foreach ($steps->take($done) as $i => $step) {
            $previous = $time->copy();
            $time->addMinutes($minutes[$i] ?? 4);

            DB::table('step_progress')->insert([
                'session_id' => $sessionId,
                'step_id' => $step->id,
                'started_at' => $previous,
                'completed_at' => $time,
                'submission_data' => json_encode($this->submission($step)),
                'attempts' => 1,
                'created_at' => $time,
                'updated_at' => $time,
            ]);
        }

        if ($done >= $steps->count()) {
            DB::table('onboarding_sessions')->where('id', $sessionId)
                ->update(['status' => 'completed', 'completed_at' => $time, 'updated_at' => $time]);
        }

        return $sessionId;
    }

    /**
     * Submission data shaped like the real wizard sends it, from the step's input_config.
     */
    private function submission(Step $step): array
    {
        $config = $step->input_config ?? [];
        $data = ['type' => $config['submission_type'] ?? 'confirmation'];

        switch ($step->input_type) {
            case 'photo':
                // No file is stored for demo data
                $data += ['filename' => 'demo.jpg', 'mime_type' => 'image/jpeg', 'demo' => true];
                break;

            case 'text':
                $data['response'] = $data['type'] === 'sim_number' ? '0733 000 000' : 'DEMO-0000';
                break;

            case 'select':
                $field = $config['field'] ?? 'response';
                $value = $config['options'][0]['value'] ?? 'demo';
                $data[$field] = $value;

                if ($field !== 'response') {
                    $data['response'] = $config['response_text'] ?? $value;
                }
                break;

            default:
                $data['response'] = 'Confirmed by agent';
        }

        return $data;
    }

    /**
     * AI questions on one step, 2 minutes apart: while the agent was on that step
     * (before it was saved), or after the last activity if they are still stuck on it.
     */
    private function questions(int $sessionId, Gig $gig, int $order, array $questions): void
    {
        $stepId = $this->stepId($gig, $order);

        $savedAt = DB::table('step_progress')
            ->where(['session_id' => $sessionId, 'step_id' => $stepId])
            ->value('completed_at');

        $time = $savedAt
            ? Carbon::parse($savedAt)->subMinutes(2 * count($questions) + 1)
            : $this->lastActivity($sessionId);

        foreach ($questions as $question) {
            $time = $time->copy()->addMinutes(2);

            DB::table('chat_logs')->insert([
                'session_id' => $sessionId,
                'step_id' => $stepId,
                'question' => $question,
                'answer' => '(Demo answer) Follow the instructions for this step and check the requirements before saving. If it still does not work, ask your manager.',
                'sources' => null,
                'created_at' => $time,
            ]);
        }
    }

    /**
     * An alert raised just after the latest question on that step.
     */
    private function alert(int $sessionId, Gig $gig, int $order, string $reason, bool $resolved = false): void
    {
        $stepId = $this->stepId($gig, $order);

        $lastQuestion = DB::table('chat_logs')
            ->where(['session_id' => $sessionId, 'step_id' => $stepId])
            ->max('created_at');

        DB::table('alerts')->insert([
            'session_id' => $sessionId,
            'step_id' => $stepId,
            'reason' => $reason,
            'is_resolved' => $resolved,
            'created_at' => Carbon::parse($lastQuestion ?? $this->lastActivity($sessionId))->addMinute(),
        ]);
    }

    /**
     * Time of the last saved step or question in a session (or its start).
     */
    private function lastActivity(int $sessionId): Carbon
    {
        $times = [
            DB::table('onboarding_sessions')->where('id', $sessionId)->value('started_at'),
            DB::table('step_progress')->where('session_id', $sessionId)->max('completed_at'),
            DB::table('chat_logs')->where('session_id', $sessionId)->max('created_at'),
        ];

        return collect($times)->filter()->map(fn ($t) => Carbon::parse($t))->max();
    }

    private function stepId(Gig $gig, int $order): int
    {
        return Step::where('gig_id', $gig->id)->where('order_number', $order)->value('id');
    }
}
