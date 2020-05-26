<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    // 多对一 多条微博属于一个用户
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
