<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Support\Money;

class OrderRefund extends Model
{
    protected $fillable = ['order_id','user_id','comment','sub_total','grand_total','status','rma_request_id'];

    protected $appends=[
        'formatted_sub_total',
        'formatted_grand_total'
    ];

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
                $query->where('id', 'like', '%' . $search . '%');
            });
        });
    }

    public function save(array $options = array())
    {
        $this->user_id = auth()->id();
        parent::save($options);
    }

    public function items(){
        return $this->hasMany(RefundItem::class);
    }

    public function transactions(){
        return $this->hasMany(OrderTransaction::class,'parent_id','id')->whereIn('parent_type',['refund','rma']);
    }
    public function order(){
        return $this->belongsTo(Order::class);
    }
    public function histories(){
        return $this->hasMany(RefundHistory::class);
    }
}
