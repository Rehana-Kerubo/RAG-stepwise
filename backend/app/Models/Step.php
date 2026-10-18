<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Step extends Model
{
    protected $fillable = ['title', 'instructions', 'order_number', 'validation_rules', 'guidance_label', 'gig_id', 'input_type', 'input_config'];
    protected $casts = ['input_config' => 'array'];

    public function stepProgress()
    {
        return $this->hasMany(StepProgress::class);
    }

    public function alerts()
    {
        return $this->hasMany(Alert::class);
    }

    public function chatLogs()
    {
        return $this->hasMany(ChatLog::class);
    }

    public function gig()
    {
    return $this->belongsTo(Gig::class);
    }
}