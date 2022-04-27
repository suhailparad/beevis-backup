<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RmaRequest extends Model
{
    use HasFactory;

    protected $guarded=[];

    public function rma_items(){
        return $this->hasMany(RmaRequestItem::class);
    }

    public function return_items(){
        return $this->hasMany(RmaRequestItem::class)->where('movement_type','in');
    }

    public function exchange_items(){
        return $this->hasMany(RmaRequestItem::class)->where('movement_type','out');
    }

    public function histories(){
        return $this->hasMany(RmaHistory::class);
    }

    public function transactions(){
        return $this->hasMany(OrderTransaction::class,'parent_id','id')->where('parent_type','rma');
    }

    public function shipments(){
        return $this->hasMany(OrderShipment::class,'rma_request_id','id');
    }

    public function refund(){
        return $this->hasOne(OrderRefund::class,'rma_request_id','id');
    }

    public function exchange_order(){
        return $this->hasOne(Order::class,'child_order_id','id');
    }
}
