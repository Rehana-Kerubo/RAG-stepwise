<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StepProgress extends Model
{
    protected $table = 'step_progress'; // Laravel would default to "step_progresses" otherwise

    protected $fillable = ['session_id', 'step_id', 'started_at', 'completed_at', 'submission_data', 'attempts'];

    public function session()
    {
        return $this->belongsTo(OnboardingSession::class, 'session_id');
    }

    public function step()
    {
        return $this->belongsTo(Step::class);
    }
}