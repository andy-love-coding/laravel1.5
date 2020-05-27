<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name', 'email', 'password',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    // boot 方法会在用户模型类完成初始化之后进行加载，因此我们对事件的监听需要放在该方法中。
    public static function boot()
    {
        parent::boot();

        static::creating(function($user) {
            $user->activation_token = Str::random(10);
        });
    }

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function gravatar($size = '100')
    {
        $hash = md5(strtolower(trim($this->attributes['email'])));
        return "http://www.gravatar.com/avatar/$hash?s=$size";
    }

    // 一对多 一个用户拥有多条微博
    public function statuses()
    {
        return $this->hasMany(Status::class);
    }

    // 动态流
    public function feed()
    {
        // 自己的动态流
        // return $this->statuses()
        //                 ->orderBy('created_at', 'desc');

        // 自己和关注用户的动态流
        $user_ids = $this->followings->pluck('id')->toArray();
        array_push($user_ids, $this->id);
        return Status::whereIn('user_id', $user_ids)
                                ->with('user')
                                ->orderBy('created_at', 'desc');
    }

    // 多对多关联语法：$this-> belongsToMany(关联表model，中间表表名，中间表中本model的关联ID，中间表中关联model的关联ID);
    // 本 model 为博主 （博主的粉丝列表）
    public function followers()
    {
        return $this->belongsToMany(User::class, 'followers', 'user_id', 'follower_id');
    }

    // 本 model 为粉丝 （粉丝的博主列表）
    public function followings()
    {
        return $this->belongsToMany(User::class, 'followers', 'follower_id', 'user_id');
    }

    // 关注 (所谓关注，即把粉丝的ids 加到 博主列表 中去)
    public function follow($user_ids)
    {
        if ( !is_array($user_ids) ) {
            $user_ids = compact('user_ids');
        }
        $this->followings()->sync($user_ids, false); // false 参数表示添加关注人(博主)id数组时，不删除其他关注人id
    }

    // 取关 (所谓取关，即把粉丝的ids 从 博主列表 中减去)
    public function unfollow($user_ids)
    {
        if ( !is_array($user_ids) ) {
            $user_ids = compact('user_ids');
        }
        $this->followings()->detach($user_ids);
    }

    // 是否关注
    public function isFollowing($user_id)
    {
        // $this->followings 返回的是：粉丝关注的博主列表的集合
        // $this->followings() 返回的是：数据库请求构建器（也就是数据库查询语句）
        // $this->followings == $this->followings()->get() // 等于 true
        // contains 方法是 Collection集合 的一个方法
        return $this->followings->contains($user_id);
    }
}
