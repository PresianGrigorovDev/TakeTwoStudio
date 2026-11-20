<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServicePackage extends Model
{
    protected $guarded = [];

    protected $casts = ['shoot_plan' => 'array'];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
