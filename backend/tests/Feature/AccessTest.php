<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\CreatesStepWiseData;
use Tests\TestCase;

/**
 * Every page needs login and the right role, and agents only reach their own onboardings.
 */
class AccessTest extends TestCase
{
    use RefreshDatabase, CreatesStepWiseData;

    public function test_guests_are_sent_to_the_login_page(): void
    {
        foreach (['/agent', '/agent/onboardings', '/profile', '/manager', '/manager/agents', '/manager/insights'] as $url) {
            $this->get($url)->assertRedirect('/');
        }
    }

    public function test_each_role_is_kept_out_of_the_other_roles_pages(): void
    {
        $gig = $this->makeGig('pos');
        $agent = $this->makeAgentUser('Amina', [$gig]);
        $manager = $this->makeManager();

        $this->actingAs($agent)->get('/manager')->assertForbidden();
        $this->actingAs($agent)->get('/manager/agents')->assertForbidden();
        $this->actingAs($agent)->post("/manager/agents/{$agent->agent_id}/gigs/{$gig->id}", ['assign' => 1])->assertForbidden();

        $this->actingAs($manager)->get('/agent')->assertForbidden();
        $this->actingAs($manager)->post('/agent/sessions', ['gig_id' => $gig->id])->assertForbidden();
    }

    public function test_an_agent_cannot_reach_another_agents_onboarding(): void
    {
        $gig = $this->makeGig('pos');
        $amina = $this->makeAgentUser('Amina', [$gig]);
        $brian = $this->makeAgentUser('Brian', [$gig]);
        $aminasSession = $this->makeSession($amina, $gig);
        $step = $gig->steps()->first();

        $this->actingAs($brian);

        $this->get("/onboarding/{$aminasSession->id}")->assertNotFound();
        $this->getJson("/wizard/sessions/{$aminasSession->id}/steps")->assertNotFound();
        $this->submitStep($aminasSession, $step)->assertNotFound();
        $this->postJson('/wizard/ask', ['session_id' => $aminasSession->id, 'question' => 'Hi'])->assertNotFound();
    }

    public function test_the_old_chat_route_is_gone(): void
    {
        $agent = $this->makeAgentUser();

        $this->actingAs($agent)->get('/chat')->assertNotFound();
    }
}
