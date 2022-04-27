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

    public function comments(){
        return $this->hasMany(Comment::class,'comment_post_ID','ID');
    }

    public function child(){
        return $this->hasMany(Post::class,'post_parent','ID')->where('post_type','shop_order_refund');
    }

}
