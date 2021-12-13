<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostMeta extends Model
{

    use HasFactory;
    protected $guarded=[];
    protected $table = "wp_postmeta";
}
