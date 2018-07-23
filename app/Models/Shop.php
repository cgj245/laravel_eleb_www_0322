<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    protected $fillable=[
        'shop_category_id',
        'shop_name',
        'shop_img',
        'shop_rating',
        'fengniao',
        'bao',
        'piao',
        'zhun',
        'notice',
        'discount',
        'brand',
        'on_time',
        'start_send',
        'send_cost',
        'status'
    ];
}
