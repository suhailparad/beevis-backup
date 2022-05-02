<?php

namespace App\Models;

use Modules\Courier\Entities\Courier;
use Modules\Setting\Entities\Warehouse;
use Illuminate\Database\Eloquent\Model;

class OrderShipment extends Model
{
    // protected $connection= 'platoshop_mysql';

    protected $guarded = ['items'];

    public function setDateTimeAttribute($value)
    {
        $this->attributes['date_time'] = date('Y-m-d H:i:s', strtotime($value));
    }

    public function save(array $options = array())
    {
        //$this->user_id = auth()->id();
        parent::save($options);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
    public function courier()
    {
        return $this->belongsTo(Courier::class);
    }
    public function items()
    {
        return $this->hasMany(ShipmentItem::class, 'shipment_id', 'id');
    }

    public function channel_items(){
        return $this->hasMany(ShipmentItem::class,'shipment_id','id')->with(['product'=>function($q){
            $q->with('channel','files');
        }]);
    }

    public function invoice()
    {
        return $this->hasOne(OrderInvoice::class, 'shipment_id', 'id');
    }
    public function warehouse(){
        return $this->belongsTo(Warehouse::class);
    }

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
                $query->where('order_id', 'like', '%' . $search . '%')
                    ->orWhere('status', 'like', '%' . $search . '%')
                    ->orWhere('courier', 'like', '%' . $search . '%')
                    ->orWhere('waybill_no', 'like', '%' . $search . '%');
            });
        });
    }

    public function histories(){
        return $this->hasMany(ShipmentHistory::class);
    }
}
