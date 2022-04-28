<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderInvoiceItem extends Model
{
    protected $connection= 'platoshop_mysql';

    protected $fillable = ['order_invoice_id','product_id','quantity','rate','total_amount','currency_id','currency_rate','type'];

    public function setTotal_amountAttribute($amount){
        $this->attributes['total_amount']=$this->attributes['rate']*$this->attributes['quantity'];
    }
}
