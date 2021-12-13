<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WpOrderItem extends Model
{
    use HasFactory;
    protected $guarded=[];
    protected $table = "wp_woocommerce_order_items";


    public function meta(){
        return $this->hasMany(OrderItemMeta::class,'order_item_id','order_item_id');
    }

}
