<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OnboardingSession extends Model
{
    protected $fillable = ['agent_id', 'started_at', 'status', 'completed_at', 'gig_id'];

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }

    public function stepProgress()
    {
        return $this->hasMany(StepProgress::class, 'session_id');
    }

    public function alerts()
    {
        return $this->hasMany(Alert::class, 'session_id');
    }

    public function chatLogs()
    {
        return $this->hasMany(ChatLog::class, 'session_id');
    }

    public function gig()
    {
    return $this->belongsTo(Gig::class);
    }

    /**
     * Only the sessions that belong to this user's agent.
     * Use with findOrFail(): another agent's session gives a 404.
     */
    public function scopeOwnedBy($query, User $user)
    {
        return $query->where('agent_id', $user->agent_id);
    }
}