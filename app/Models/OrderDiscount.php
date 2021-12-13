<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderDiscount extends Model
{
    protected $fillable = ['order_id','type','discount_value','amount','parent_id','parent_type','remarks','status'];

}
