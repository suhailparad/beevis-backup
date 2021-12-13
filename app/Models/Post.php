<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $guarded=[];
    protected $table = "wp_posts";
    use HasFactory;

    public function meta(){
        return $this->hasMany(PostMeta::class,'post_id','ID');
    }

    public function items(){
        return $this->hasMany(WpOrderItem::class,'order_id','ID');
    }
}
