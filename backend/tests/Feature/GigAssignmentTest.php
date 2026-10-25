<?php

namespace Tests\Feature;

use App\Models\OnboardingSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\CreatesStepWiseData;
use Tests\TestCase;

/**
 * Agents can only start the gigs a manager assigned to them (table agent_gig).
 */
class GigAssignmentTest extends TestCase
{
    use RefreshDatabase, CreatesStepWiseData;

    public function test_home_shows_only_assigned_gigs(): void
    {
        $pos = $this->makeGig('pos');
        $airtel = $this->makeGig('airtel');
        $agent = $this->makeAgentUser('Amina', [$airtel]);

        $this->actingAs($agent)->get('/agent')
            ->assertSee('Airtel')
            ->assertDontSee('name="gig_id" value="' . $pos->id . '"', false);
    }

    public function test_an_agent_without_gigs_sees_the_empty_state(): void
    {
        $this->makeGig('pos');
        $agent = $this->makeAgentUser();

        $this->actingAs($agent)->get('/agent')
            ->assertSee("Your manager hasn't assigned you a gig yet", false);
    }

    public function test_the_server_refuses_a_gig_that_is_not_assigned(): void
    {
        $pos = $this->makeGig('pos');
        $airtel = $this->makeGig('airtel');
        $agent = $this->makeAgentUser('Amina', [$airtel]);

        $this->actingAs($agent)
            ->from('/agent')
            ->post('/agent/sessions', ['gig_id' => $pos->id])
            ->assertRedirect('/agent')
            ->assertSessionHasErrors('gig_id');

        $this->assertSame(0, OnboardingSession::count());
    }

    public function test_an_assigned_gig_starts_an_onboarding(): void
    {
        $pos = $this->makeGig('pos');
        $agent = $this->makeAgentUser('Amina', [$pos]);

        $response = $this->actingAs($agent)->post('/agent/sessions', ['gig_id' => $pos->id]);

        $session = OnboardingSession::first();
        $response->assertRedirect('/onboarding/' . $session->id);
        $this->assertSame($pos->id, (int) $session->gig_id);
    }

    public function test_a_manager_assigns_and_unassigns_a_gig(): void
    {
        $pos = $this->makeGig('pos');
        $agent = $this->makeAgentUser();
        $manager = $this->makeManager();
        $url = "/manager/agents/{$agent->agent_id}/gigs/{$pos->id}";

        // Assign twice (a double tap): still one row, with who assigned it
        $this->actingAs($manager)->post($url, ['assign' => 1])->assertRedirect();
        $this->actingAs($manager)->post($url, ['assign' => 1]);

        $this->assertDatabaseCount('agent_gig', 1);
        $this->assertDatabaseHas('agent_gig', [
            'agent_id' => $agent->agent_id, 'gig_id' => $pos->id, 'assigned_by' => $manager->id,
        ]);

        $this->actingAs($manager)->post($url, ['assign' => 0]);
        $this->assertDatabaseCount('agent_gig', 0);
    }

    public function test_unassigning_does_not_break_an_onboarding_already_started(): void
    {
        $pos = $this->makeGig('pos');
        $agent = $this->makeAgentUser('Amina', [$pos]);
        $session = $this->makeSession($agent, $pos);

        $agent->agent->gigs()->detach($pos->id);

        $this->actingAs($agent)->get("/onboarding/{$session->id}")->assertOk();
        $this->submitStep($session, $pos->steps()->first())->assertOk();
    }
}
