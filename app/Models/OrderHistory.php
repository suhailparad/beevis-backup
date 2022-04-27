<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\User\Entities\User;


class OrderHistory extends Model
{

    protected $connection= 'platoshop_mysql';

    protected $guarded = [];

    protected $appends = ['date'];

    public function getDateAttribute()
    {
        return $this->attributes['date'] = date('M d, H:i A', strtotime($this->attributes['created_at']));
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
