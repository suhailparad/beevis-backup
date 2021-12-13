<?php

namespace App\Models;

use Carbon\Carbon;
use Modules\AddOnItem\Entities\AddOnItem;
use Modules\PaymentMethod\Entities\PaymentMethod;
use Modules\Setting\Entities\Currency;
use Illuminate\Database\Eloquent\Model;
use Modules\Support\Money;

class OrderTransaction extends Model
{

    protected $fillable = ['parent_id','parent_type','transaction_date',
        'transaction_no','payment_method_id','amount','mode','isPrimary'];

    protected $guarded=['formatted_amount'];

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['_filter_data'] ?? null, function ($query, $filter) {
            $query->where(function ($query) use ($filter) {
                $filter_data = json_decode($filter);
                foreach ($filter_data as $field => $value) {
                    if ($value) {
                        $query->where($field, '=', $value);
                    }
                }
            });
        })->when($filters['_searchvalue'] ?? null, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query->where('transaction_no', 'like', '%' . $search . '%');
            });
        });
    }

    public function setTransactionDateAttribute($transaction_date){
        $this->attributes['transaction_date'] =date('Y-m-d H:i:s',strtotime($transaction_date));
    }

    public function payment_method(){
        return $this->belongsTo(PaymentMethod::class);
    }


    public function payment_method_product(){
        return $this->hasOne(OrderItem::class,'type_id','id')->where('type','payment_method_addon');
    }

    public function order(){
        return $this->belongsTo(Order::class,'parent_id','id');
    }

}
