<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $guarded = [];

    public function packages()
    {
        return $this->hasMany(ServicePackage::class);
    }

    public function extras()
    {
        return $this->hasMany(ServiceExtra::class);
    }
}
