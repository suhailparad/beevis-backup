<?php

namespace App\Models;

use Modules\Product\Entities\Product;
use Illuminate\Database\Eloquent\Model;

class ShipmentItem extends Model
{

    protected $fillable = ['shipment_id','product_id','quantity',
        'unit_id','price','total','order_item_id'];

    public function setTotal_amountAttribute($amount){
        $this->attributes['total_amount']=$this->attributes['rate']*$this->attributes['quantity'];
    }

    public function product(){
        return $this->belongsTo(Product::class);
    }

}
