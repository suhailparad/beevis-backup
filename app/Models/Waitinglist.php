<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Waitinglist extends Model
{
    use HasFactory;

    // protected $connection= 'platoshop_mysql';

    protected $guarded=[];
    
    protected $table="waiting_lists";
}
