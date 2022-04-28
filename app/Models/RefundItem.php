<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RefundItem extends Model
{
    protected $connection= 'platoshop_mysql';

    protected $fillable = ['shipment_id','product_id','quantity','unit_id','rate','total_amount','order_item_id','return_to_stock'];
    protected $casts=[
        'return_to_stock'=>'boolean'
    ];

    public function items(){
        return $this->hasMany(RefundItem::class);
    }

}
