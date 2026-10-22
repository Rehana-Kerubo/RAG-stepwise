<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Login accounts. Run: php artisan db:seed --class=DemoUserSeeder
 * Safe to re-run: users are matched on email.
 */
class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        // Real people who sign in with Google. They never use a password,
        // so they get a random one nobody knows.
        $this->account('deborah.kerubo@strathmore.edu', 'Deborah Kerubo', 'manager', null, Str::random(40));
        $this->account('debbie.mu04@gmail.com', 'Debbie (agent)', 'agent', Agent::orderBy('id')->value('id'), Str::random(40));

        // Demo accounts for the password login, which only works on local.
        if (! app()->isLocal()) {
            return;
        }

        $this->account('manager@stepwise.test', 'Demo Manager', 'manager', null, 'password');

        foreach (Agent::orderBy('id')->get() as $agent) {
            $this->account("agent{$agent->id}@stepwise.test", $agent->name, 'agent', $agent->id, 'password');
        }
    }

    private function account(string $email, string $name, string $role, ?int $agentId, string $password): void
    {
        User::updateOrCreate(
            ['email' => $email],
            ['name' => $name, 'role' => $role, 'agent_id' => $agentId, 'password' => $password]
        );
    }
}
