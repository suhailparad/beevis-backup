<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Channel\Entities\Channel;
use Modules\Customer\Entities\Customer;
use Modules\OrderRma\Entities\RmaRequest;
use Modules\ShippingMethod\Entities\ShippingMethod;
use Modules\Support\Money;
use Modules\User\Entities\User;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'id',
        'customer_id', 'is_guest',
        'status', 'date', 'items_count',
        'sub_total', 'discount_amount',
        'tax_total', 'grand_total',
        'shipping_method_id','priority',
        'platform',
        'email',
        'phone',
        'token',
        'channel_id'
    ];

    protected $casts=[
        'is_guest'=>'boolean'
    ];

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['_filter_data'] ?? null, function ($query, $filter) {
            $query->where(function ($query) use ($filter) {
                $filter_data = json_decode($filter);
                foreach ($filter_data as $field => $value) {
                    if ($value) {
                        if($field=="dates")
                            $query->whereBetween('date',$value);
                        else
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

    public function getCreatedAtAttribute($created_at)
    {
        if (!is_null($created_at)) {
            return Carbon::parse($created_at)->diffForHumans();
        }
    }

    public function getFormattedDateAttribute(){
        // return date('M d, H:i A',strtotime($this->attributes['created_at']));
        return date('M d, H:i A',strtotime($this->attributes['date']));
    }
    public function customer(){
        return $this->belongsTo(User::class,'customer_id','id');
    }

    public function billingAddress()
    {
        return $this->hasOne(OrderAddress::class)->where('address_type', 'Billing');
    }

    public function shippingAddress()
    {
        return $this->hasOne(OrderAddress::class)->where('address_type', 'Shipping');
    }

    public function orderNote()
    {
        return $this->hasMany(OrderHistory::class)->where('type', 'note')->with('user');
    }


    public function orderHistory()
    {
        return $this->hasMany(OrderHistory::class)->where('type', 'history')->with('user');
    }

    public function orderCommunication()
    {
        return $this->hasMany(OrderHistory::class)->where('type', 'communication')->with('user');
    }

    public function products()
    {
        return $this->hasMany(OrderItem::class)->where('type', 'product');
    }

    public function productchannel(){
        return $this->hasMany(OrderItem::class)->where('type', 'product')->with(['product'=>function($q){
            $q->with('channel','files');
        }]);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function addons()
    {
        return $this->hasMany(OrderItem::class)->where('type','!=','product')->with('addon_item');;
    }

    public function payment_addons(){
        return $this->hasMany(OrderItem::class)->where('type','payment_method_addon')->with('addon_item');
    }

    public function transactions()
    {
        return $this->hasMany(OrderTransaction::class,'parent_id','id')->where('parent_type','order');
    }

    public function setDateAttribute($date)
    {
        $this->attributes['date'] = date('Y-m-d H:i:s', strtotime($date));
    }

    public function taxes()
    {
        return $this->hasManyThrough(OrderTax::class, OrderItem::class);
    }

    public function tax_group()
    {
        return $this->taxes()->selectRaw('sum(order_taxes.tax_amount) as tax_amount,tax_rate_id')->groupBy('tax_rate_id')->with('tax_rate');
    }

    public function shipping_method()
    {
        return $this->belongsTo(ShippingMethod::class);
    }

    public function shipments(){
        return $this->hasMany(OrderShipment::class);
    }

    public function refunds(){
        return $this->hasMany(OrderRefund::class);
    }

    public function rma(){
        return $this->hasMany(RmaRequest::class);
    }

    public function coupon(){
        return $this->hasMany(OrderCoupon::class)->with('coupon');
    }
    public function discounts(){
        return $this->hasMany(OrderDiscount::class);
    }
    public function custom_note(){ //Custom note for Order Hold or Cancelled
        return $this->hasOne(StatusNote::class)->orderBy('id','desc')->with('user');
    }
}
