<?php

namespace App\Models;

use Modules\Coupon\Entities\Coupon;
use Illuminate\Database\Eloquent\Model;

class OrderCoupon extends Model
{
    protected $fillable = ['order_id','coupon_id'];

    public function order(){
        return $this->belongsTo(Order::class);
    }
    public function coupon(){
        return $this->belongsTo(Coupon::class);
    }
}
