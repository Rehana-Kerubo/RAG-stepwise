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
}