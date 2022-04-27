<?php

namespace App\Models;

use Modules\AddOnItem\Entities\AddOnItem;
use Modules\Product\Entities\Product;
use Illuminate\Database\Eloquent\Model;
use Modules\Support\Money;

class OrderItem extends Model
{

    protected $connection= 'platoshop_mysql';

    protected $fillable = ['additional',
        'order_id','product_id','type','type_id',
        'price','total','quantity','currency_id',
        'rate','tax_amount','taxable_amount','discount','tax_percentage'];


    public function order(){
        return $this->belongsTo(Order::class);
    }

    public function taxes(){
        return $this->hasMany(OrderTax::class);
    }

    public function product(){
        return $this->belongsTo(Product::class);
    }

    public function addon_item(){
        return $this->belongsTo(AddOnItem::class,'product_id','id');
    }

}
