<?php

namespace App\Models;

use Modules\Setting\Entities\Country;
use Modules\Setting\Entities\State;
use Illuminate\Database\Eloquent\Model;

class OrderAddress extends Model
{

    protected $guarded = ['id','full_name','cart_id'];

    protected $connection= 'platoshop_mysql';

    public function state(){
        return $this->belongsTo(State::class);
    }

    public function country(){
        return $this->belongsTo(Country::class);
    }

}
