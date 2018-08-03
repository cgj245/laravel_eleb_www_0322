<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Addre extends Model
{
    protected $fillable=['user_id','province','city','county','tel','name','address','is_default'];
}
