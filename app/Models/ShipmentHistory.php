<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShipmentHistory extends Model
{
    use HasFactory;
    protected $connection= 'platoshop_mysql';
    protected $guarded = [];
}
