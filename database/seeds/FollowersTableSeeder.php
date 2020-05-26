<?php

use Illuminate\Database\Seeder;
use App\Models\User;

class FollowersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 让「第1个用户」和「其他用户」互相关注
        $users = User::all();

        $user = $users->first();
        $followers = $users->slice(1);

        $user_id = $user->id;
        $follower_ids = $followers->pluck('id')->toArray();

        // 1号 关注 其余人
        $user->follow($follower_ids);

        // 其余人 关注 1号
        foreach($followers as $follower) {
            $follower->follow($user_id);
        }
    }
}
