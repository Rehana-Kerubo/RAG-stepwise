<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\ChatLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\CreatesStepWiseData;
use Tests\TestCase;

/**
 * Asking the AI. The Flask service is faked, so these tests never call a real model.
 */
class AskTest extends TestCase
{
    use RefreshDatabase, CreatesStepWiseData;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake(['*' => Http::response(['answer' => 'Use the code from your dashboard.', 'sources' => ['pos.md']])]);
    }

    public function test_a_question_is_answered_and_logged(): void
    {
        $pos = $this->makeGig('pos');
        $agent = $this->makeAgentUser('Amina', [$pos]);
        $session = $this->makeSession($agent, $pos);
        $step = $pos->steps()->first();

        $this->actingAs($agent)
            ->postJson('/wizard/ask', ['session_id' => $session->id, 'step_id' => $step->id, 'question' => 'Where is the code?'])
            ->assertOk()
            ->assertJson(['answer' => 'Use the code from your dashboard.']);

        $this->assertDatabaseHas('chat_logs', ['session_id' => $session->id, 'step_id' => $step->id]);

        // Only this gig's documents are searched, and the step's own text goes along
        Http::assertSent(fn ($request) => $request['gig'] === 'pos'
            && $request['step']['title'] === $step->title
            && $request['step']['instructions'] === $step->instructions);
    }

    public function test_what_the_agent_submitted_is_never_sent_to_the_ai(): void
    {
        $pos = $this->makeGig('pos');
        $agent = $this->makeAgentUser('Amina', [$pos]);
        $session = $this->makeSession($agent, $pos);
        $step = $pos->steps()->first();

        $this->actingAs($agent);
        $this->postJson("/wizard/sessions/{$session->id}/steps/{$step->id}/submit", [
            'submission_data' => ['type' => 'confirmation', 'response' => 'SECRET-CODE-123'],
        ])->assertOk();

        $this->postJson('/wizard/ask', ['session_id' => $session->id, 'step_id' => $step->id, 'question' => 'Is that right?'])
            ->assertOk();

        Http::assertSent(fn ($request) => ! str_contains($request->body(), 'SECRET-CODE-123'));
    }

    public function test_three_questions_on_one_step_raise_one_alert(): void
    {
        $pos = $this->makeGig('pos');
        $agent = $this->makeAgentUser('Amina', [$pos]);
        $session = $this->makeSession($agent, $pos);
        $step = $pos->steps()->first();

        $this->actingAs($agent);

        for ($i = 1; $i <= 4; $i++) {
            $this->postJson('/wizard/ask', ['session_id' => $session->id, 'step_id' => $step->id, 'question' => "Question $i"]);
        }

        $this->assertSame(1, Alert::where('session_id', $session->id)->count());
        $this->assertStringStartsWith('Asked the AI 3 times', Alert::first()->reason);
    }

    public function test_a_step_from_another_gig_is_refused(): void
    {
        $pos = $this->makeGig('pos');
        $airtel = $this->makeGig('airtel');
        $agent = $this->makeAgentUser('Amina', [$pos]);
        $session = $this->makeSession($agent, $pos);

        $this->actingAs($agent)
            ->postJson('/wizard/ask', ['session_id' => $session->id, 'step_id' => $airtel->steps()->first()->id, 'question' => 'Hi'])
            ->assertStatus(422);

        $this->assertSame(0, ChatLog::count());
    }

    public function test_long_questions_and_floods_are_refused(): void
    {
        $pos = $this->makeGig('pos');
        $agent = $this->makeAgentUser('Amina', [$pos]);
        $session = $this->makeSession($agent, $pos);

        $this->actingAs($agent)
            ->postJson('/wizard/ask', ['session_id' => $session->id, 'question' => str_repeat('x', 501)])
            ->assertStatus(422);

        // 10 a minute are allowed (the one above counts), the 11th is refused
        for ($i = 2; $i <= 10; $i++) {
            $this->postJson('/wizard/ask', ['session_id' => $session->id, 'question' => "Question $i"])->assertOk();
        }

        $this->postJson('/wizard/ask', ['session_id' => $session->id, 'question' => 'One too many'])->assertStatus(429);
    }
}
