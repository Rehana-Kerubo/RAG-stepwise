<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\CreatesStepWiseData;
use Tests\TestCase;

/**
 * Saving steps: in order, inside the session's own gig, never dependent on the AI.
 * The offline queue replays exactly these requests when the phone reconnects.
 */
class OnboardingFlowTest extends TestCase
{
    use RefreshDatabase, CreatesStepWiseData;

    public function test_steps_only_return_the_sessions_own_gig(): void
    {
        $pos = $this->makeGig('pos', 3);
        $this->makeGig('airtel', 2);
        $agent = $this->makeAgentUser('Amina', [$pos]);
        $session = $this->makeSession($agent, $pos);

        $this->actingAs($agent)->getJson("/wizard/sessions/{$session->id}/steps")
            ->assertOk()
            ->assertJsonCount(3);
    }

    public function test_a_step_cannot_be_saved_before_the_earlier_ones(): void
    {
        $pos = $this->makeGig('pos', 3);
        $agent = $this->makeAgentUser('Amina', [$pos]);
        $session = $this->makeSession($agent, $pos);

        $this->actingAs($agent);
        $this->submitStep($session, $pos->steps[1])->assertStatus(422);
    }

    public function test_saving_every_step_in_order_completes_the_onboarding(): void
    {
        $pos = $this->makeGig('pos', 3);
        $agent = $this->makeAgentUser('Amina', [$pos]);
        $session = $this->makeSession($agent, $pos);

        $this->actingAs($agent);
        $this->submitStep($session, $pos->steps[0])->assertOk()->assertJson(['onboarding_complete' => false]);
        $this->submitStep($session, $pos->steps[1])->assertOk();
        $this->submitStep($session, $pos->steps[2])->assertOk()->assertJson(['onboarding_complete' => true]);

        $this->assertSame('completed', $session->fresh()->status);

        // A finished onboarding accepts nothing more
        $this->submitStep($session, $pos->steps[0])->assertStatus(422);
    }

    public function test_a_step_from_another_gig_is_refused(): void
    {
        $pos = $this->makeGig('pos');
        $airtel = $this->makeGig('airtel');
        $agent = $this->makeAgentUser('Amina', [$pos]);
        $session = $this->makeSession($agent, $pos);

        $this->actingAs($agent);
        $this->submitStep($session, $airtel->steps()->first())->assertStatus(422);
    }

    public function test_saving_a_step_works_when_the_ai_is_down(): void
    {
        Http::fake(['*' => Http::response('down', 500)]);

        $pos = $this->makeGig('pos');
        $agent = $this->makeAgentUser('Amina', [$pos]);
        $session = $this->makeSession($agent, $pos);

        $this->actingAs($agent);
        $this->submitStep($session, $pos->steps()->first())->assertOk();
    }
}
