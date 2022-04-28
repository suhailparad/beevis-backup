<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Support\Money;
use Modules\Tax\Entities\TaxRate;

class OrderTax extends Model
{

    protected $connection= 'platoshop_mysql';

    protected $fillable = ['order_item_id','tax_rate_id','rate','tax_amount'];

    public function tax_rate(){
        return $this->belongsTo(TaxRate::class);
    }

}
