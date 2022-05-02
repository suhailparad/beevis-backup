<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderInvoice extends Model
{
    // protected $connection= 'platoshop_mysql';

    protected $fillable = ['order_id','invoice_no','invoice_date','shipment_id','user_id'];

    public function items(){
        return $this->hasMany(OrderInvoiceItem::class);
    }

}
