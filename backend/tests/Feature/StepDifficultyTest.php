<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\ChatLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\CreatesStepWiseData;
use Tests\TestCase;

/**
 * The manager's step difficulty view picks the step with the most questions + alerts.
 */
class StepDifficultyTest extends TestCase
{
    use RefreshDatabase, CreatesStepWiseData;

    public function test_the_step_with_most_questions_and_alerts_is_the_hardest(): void
    {
        $pos = $this->makeGig('pos', 3);
        $agent = $this->makeAgentUser('Amina', [$pos]);
        $session = $this->makeSession($agent, $pos);
        [$one, $two] = [$pos->steps[0], $pos->steps[1]];

        ChatLog::create(['session_id' => $session->id, 'step_id' => $one->id, 'question' => 'q', 'answer' => 'a']);

        foreach (range(1, 3) as $i) {
            ChatLog::create(['session_id' => $session->id, 'step_id' => $two->id, 'question' => "q$i", 'answer' => 'a']);
        }
        Alert::create(['session_id' => $session->id, 'step_id' => $two->id, 'reason' => 'Asked the AI 3 times about this step']);

        $this->actingAs($this->makeManager())
            ->get('/manager/insights?gig=pos')
            ->assertOk()
            ->assertSeeInOrder(['Hardest step', $two->title, '3 AI questions', '1 alert']);
    }

    public function test_a_gig_with_no_activity_has_no_hardest_step(): void
    {
        $this->makeGig('pos');

        $this->actingAs($this->makeManager())
            ->get('/manager/insights?gig=pos')
            ->assertOk()
            ->assertSee('No questions, alerts or saved steps for this gig yet.');
    }
}
