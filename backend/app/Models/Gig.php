<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gig extends Model
{
    protected $fillable = ['slug', 'name', 'client', 'description'];

    public function steps()
    {
        return $this->hasMany(Step::class)->orderBy('order_number');
    }

    /**
     * Agents assigned to this gig (table agent_gig).
     */
    public function agents()
    {
        return $this->belongsToMany(Agent::class, 'agent_gig')
            ->withPivot('assigned_by', 'assigned_at');
    }
}
