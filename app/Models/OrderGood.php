<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderGood extends Model
{
    protected $fillable=[
        'order_id',
        'goods_id',
        'goods_name',
        'goods_price',
        'goods_img',
        'amount',
    ];
}
