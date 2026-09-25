<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    // Table only has created_at, not updated_at (DB default handles the timestamp)
    public $timestamps = false;

    protected $fillable = ['session_id', 'step_id', 'reason', 'is_resolved'];

    protected $casts = ['is_resolved' => 'boolean'];

    public function session()
    {
        return $this->belongsTo(OnboardingSession::class, 'session_id');
    }

    public function step()
    {
        return $this->belongsTo(Step::class);
    }
}