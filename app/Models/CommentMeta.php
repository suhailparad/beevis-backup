<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommentMeta extends Model
{
    use HasFactory;

    protected $table = "wp_commentmeta";
}
