<?php

namespace Tests;

use App\Models\Agent;
use App\Models\Gig;
use App\Models\OnboardingSession;
use App\Models\Step;
use App\Models\User;

/**
 * Small helpers that build StepWise data for tests.
 * Tests run on an in-memory SQLite database (phpunit.xml), never on MariaDB.
 */
trait CreatesStepWiseData
{
    /**
     * A gig with $count confirmation steps, numbered 1..$count.
     */
    protected function makeGig(string $slug, int $count = 2): Gig
    {
        $gig = Gig::create(['slug' => $slug, 'name' => ucfirst(str_replace('-', ' ', $slug))]);

        for ($i = 1; $i <= $count; $i++) {
            Step::create([
                'gig_id' => $gig->id,
                'title' => "$slug step $i",
                'instructions' => "Do part $i.",
                'guidance_label' => "Finish part $i of $slug",
                'order_number' => $i,
                'input_type' => 'confirm',
                'input_config' => ['submission_type' => 'confirmation', 'statement' => 'Done.'],
            ]);
        }

        return $gig;
    }

    /**
     * An agent login, optionally assigned to some gigs.
     */
    protected function makeAgentUser(string $name = 'Test Agent', array $gigs = []): User
    {
        // phone is required and unique
        $agent = Agent::create([
            'name' => $name,
            'phone' => '07' . substr(md5($name), 0, 8),
            'region' => 'Nairobi',
            'status' => 'active',
        ]);

        foreach ($gigs as $gig) {
            $agent->gigs()->attach($gig->id, ['assigned_at' => now()]);
        }

        return User::create([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)) . '@test.local',
            'password' => 'password',
            'role' => 'agent',
            'agent_id' => $agent->id,
        ]);
    }

    protected function makeManager(): User
    {
        return User::create([
            'name' => 'Test Manager',
            'email' => 'manager@test.local',
            'password' => 'password',
            'role' => 'manager',
        ]);
    }

    protected function makeSession(User $agentUser, Gig $gig, string $status = 'in_progress'): OnboardingSession
    {
        return OnboardingSession::create([
            'agent_id' => $agentUser->agent_id,
            'gig_id' => $gig->id,
            'status' => $status,
            'started_at' => now()->subMinutes(20),
        ]);
    }

    /**
     * Submit a confirmation step the way the wizard (and the offline sync) does.
     */
    protected function submitStep(OnboardingSession $session, Step $step)
    {
        return $this->postJson("/wizard/sessions/{$session->id}/steps/{$step->id}/submit", [
            'submission_data' => ['type' => 'confirmation', 'response' => 'Confirmed by agent'],
        ]);
    }
}
