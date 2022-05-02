<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderDiscount extends Model
{
    // protected $connection= 'platoshop_mysql';

    protected $fillable = ['order_id','type','discount_value','taxable_discount_amount','amount','parent_id','parent_type','remarks','status'];

}
