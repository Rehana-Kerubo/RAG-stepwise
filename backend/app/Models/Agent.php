<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Agent extends Model
{
    protected $fillable = ['name', 'phone', 'region', 'status'];

    public function sessions()
    {
        return $this->hasMany(OnboardingSession::class);
    }

    /**
     * Gigs a manager has assigned to this agent (table agent_gig).
     */
    public function gigs()
    {
        return $this->belongsToMany(Gig::class, 'agent_gig')
            ->withPivot('assigned_by', 'assigned_at');
    }
}