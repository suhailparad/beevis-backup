<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    // protected $connection= 'platoshop_mysql';

    public function channel()
    {
        return $this->hasOne(ProductChannel::class);
    }

}
