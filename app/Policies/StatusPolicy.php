<?php

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use App\Models\User;
use App\Models\Status;

class StatusPolicy
{
    use HandlesAuthorization;
    
    // 只有自己才能删除自己的微博
    public function destroy(User $currentUser, Status $status)
    {
        return $currentUser->id === $status->user_id;
    }
}
