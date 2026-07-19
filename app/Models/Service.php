<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Traits\LogsActivity;

class Service extends Model
{
    use LogsActivity;
    protected $guarded = [];

    public function packages()
    {
        return $this->hasMany(ServicePackage::class);
    }

    public function extras()
    {
        return $this->hasMany(ServiceExtra::class);
    }

    public function promotions()
    {
        return $this->hasMany(ServicePromotion::class);
    }

    public function activePromotion()
    {
        return $this->hasOne(ServicePromotion::class)
            ->where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where('expires_at', '>=', now());
    }
}
