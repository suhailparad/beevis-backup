<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory;

    protected $table = "wp_comments";

    public function meta(){
        return $this->hasMany(CommentMeta::class,'comment_id','comment_ID');
    }
}
