<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    // update 方法接收两个参数，第一个参数默认为当前登录用户实例，第二个参数则为要进行授权的用户实例
    // 使用授权策略时，我们 不需要 传递当前登录用户至该方法内，因为框架会自动加载当前登录用户，即不用传递 $currentUser
    public function update(User $currentUser, User $user)
    {
        // 只有自己才能更新自己
        return $currentUser->id === $user->id;
    }

    public function destroy(User $currentUser, User $user)
    {
        // 管理员才能删除 且 自己不能删除自己
        return $currentUser->is_admin && $currentUser->id !== $user->id;
    }
}
