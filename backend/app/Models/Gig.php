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
}
