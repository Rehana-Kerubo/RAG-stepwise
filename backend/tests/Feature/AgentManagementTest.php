<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GoogleUser;
use Tests\CreatesStepWiseData;
use Tests\TestCase;

/**
 * Managers add agents and switch them inactive; Google login respects both.
 */
class AgentManagementTest extends TestCase
{
    use RefreshDatabase, CreatesStepWiseData;

    /**
     * Pretend Google answered the sign-in with this (verified) email.
     */
    private function fakeGoogle(string $email): void
    {
        $googleUser = (new GoogleUser)
            ->setRaw(['email_verified' => true])
            ->map(['email' => $email, 'avatar' => 'https://example.com/photo.jpg']);

        Socialite::shouldReceive('driver->user')->andReturn($googleUser);
    }

    public function test_a_manager_adds_an_agent_with_a_google_login(): void
    {
        $this->actingAs($this->makeManager())->get('/manager/agents/create')->assertOk();

        $response = $this->post('/manager/agents', [
            'name' => 'Grace Achieng',
            'phone' => '0711000101',
            'region' => 'Nairobi CBD',
            'email' => 'Grace@Example.com',
        ]);

        $agent = Agent::where('phone', '0711000101')->first();
        $response->assertRedirect('/manager/agents/' . $agent->id);

        $this->assertDatabaseHas('users', [
            'email' => 'grace@example.com', 'role' => 'agent', 'agent_id' => $agent->id,
        ]);

        // The agent's screen shows the active switch, and the list shows the new agent
        $this->get('/manager/agents/' . $agent->id)->assertOk()->assertSee('Mark inactive');
        $this->get('/manager/agents')->assertOk()->assertSee('Grace Achieng');
    }

    public function test_duplicate_phone_or_email_is_refused(): void
    {
        $existing = $this->makeAgentUser('Amina');

        $this->actingAs($this->makeManager())
            ->post('/manager/agents', [
                'name' => 'Someone',
                'phone' => $existing->agent->phone,
                'email' => $existing->email,
            ])
            ->assertSessionHasErrors(['phone', 'email']);

        $this->assertSame(1, Agent::count());
    }

    public function test_agents_cannot_add_agents(): void
    {
        $this->actingAs($this->makeAgentUser())
            ->post('/manager/agents', ['name' => 'X', 'phone' => '1', 'email' => 'x@example.com'])
            ->assertForbidden();
    }

    public function test_an_inactive_agent_is_stopped_even_when_logged_in(): void
    {
        $agent = $this->makeAgentUser('Amina');

        $this->actingAs($this->makeManager())
            ->post("/manager/agents/{$agent->agent_id}/status", ['status' => 'inactive'])
            ->assertRedirect();

        $this->actingAs($agent->fresh())->get('/agent')->assertForbidden();
    }

    public function test_google_login_works_for_a_known_agent_and_saves_the_photo(): void
    {
        $agent = $this->makeAgentUser('Amina');
        $this->fakeGoogle($agent->email);

        $this->get('/auth/google/callback')->assertRedirect('/agent');

        $this->assertAuthenticatedAs($agent);
        $this->assertSame('https://example.com/photo.jpg', $agent->fresh()->avatar);
    }

    public function test_google_login_refuses_unknown_emails_and_inactive_agents(): void
    {
        $this->fakeGoogle('stranger@example.com');
        $this->get('/auth/google/callback')->assertRedirect('/')->assertSessionHasErrors('email');
        $this->assertGuest();

        $agent = $this->makeAgentUser('Amina');
        $agent->agent->update(['status' => 'inactive']);
        $this->fakeGoogle($agent->email);

        $this->get('/auth/google/callback')->assertRedirect('/')->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
