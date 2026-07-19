<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Traits\LogsActivity;

class Testimonial extends Model
{
    use LogsActivity;

    protected $guarded = [];
}
