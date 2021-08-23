<?php

namespace App\Http\Controllers;

use App\Http\Requests\FollowRequest;
use App\Http\Resources\FollowResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FollowController extends Controller
{
    public function getFollowsInfo(FollowRequest $request, $userId)
    {
        $relation = $request->relation;
        //SELECT * FROM users WHERE id = $userId AND id IS NOT NULL;
        $user = User::findOrFail($userId);
        //SELECT users.*, followers.follower_id AS pivot_follower_id, followers.user_id AS pivot_user_id, followers.is_approve AS pivot_is_approve
        //->FROM users INNER JOIN followers ON users.id = followers.user_id where followers.follower_id = $user->id;
        return FollowResource::collection($user->{$relation}()->get());
    }

    public function follow(Request $request, $userId)
    {
        //SELECT * FROM users WHERE users.id = 'request.user.id';
        $user = $request->user();
        //SELECT * FROM users WHERE EXISTS (SELECT * FROM profiles WHERE users.id = profiles.user_id) AND id <> 'user.id' AND users.id = 'follow' LIMIT 1;
        $current_user = User::where('id','<>',$user->id)->has('profile')->findOrFail($userId);
        //SELECT users.*, followers.follower_id AS pivot_follower_id, followers.user_id as pivot_user_id, followers.is_approve AS pivot_is_approve FROM users
        //->INNER JOIN followers ON users.id = followers.user_id WHERE followers.follower_id = $request->user->id AND users.id = $userId limit 1
        $isFollowingNow = $user->followings()->find($current_user->id);
        if (!$isFollowingNow){
            //SELECT * FROM profiles WHERE profiles.user_id = $userId AND profiles.user_id IS NOT NULL LIMIT 1;
            //INSERT INTO followers (follower_id, is_approve, user_id) VALUES ('$request->user->id', '!$current_user->profile->is_private', 'user_id');
            $user->followings()->attach($current_user->id,['is_approve' => !$current_user->profile->is_private]);
            Log::channel('follow')->info('User have new follow',[
                'follower_id' => $current_user->id,
                'user_id' => $user->id,
            ]);
            return FollowResource::make($isFollowingNow->refresh());
        }else{
            return response(null,404);
        }

    }

    public function approveFollow(Request $request, $userId)
    {
        //SELECT * FROM users WHERE users.id = 'request.user.id';
        $current_user = $request->user();
        //SELECT * FROM users WHERE id <> '$current_user->id' AND users.id = '$userId' LIMIT 1;
        $user = User::where('id','<>',$current_user->id)->findOrFail($userId);
        //SELECT users.*, followers.user_id AS pivot_user_id, followers.follower_id as pivot_follower_id, followers.is_approve AS pivot_is_approve FROM users
        //->INNER JOIN followers ON users.id = followers.follower_id WHERE followers.user_id = $request->user->id AND users.id = $userId limit 1
        $isFollowingNow = $current_user->followers()->find($user->id);
        if($isFollowingNow){
            //UPDATE `followers` SET `is_approve` = ? WHERE `followers`.`user_id` = $current_user->id AND `follower_id` IN ('$userId');
            $current_user->followers()->updateExistingPivot($userId, ['is_approve' => true]);
            //SELECT * FROM followers WHERE followers.user_id = 'current_user.id' AND followers.follower_id = user.id;
            //SELECT users.*, followers.user_id AS pivot_user_id, followers.follower_id as pivot_follower_id, followers.is_approve AS pivot_is_approve FROM users
            //->INNER JOIN followers ON users.id = followers.follower_id WHERE followers.user_id = $request->user->id AND users.id = $userId limit 1
            $isFollowingNow = $current_user->followers()->find($user->id);
            Log::channel('follow')->info('User approve follow',[
                'follower_id' => $user->id,
                'user_id' => $current_user->id,
            ]);
            return FollowResource::make($isFollowingNow);
        }
        else{
            return response(null,404);
        }
    }

    public function denyFollow(Request $request, $userId)
    {
        //SELECT * FROM users WHERE users.id = 'request.user.id';
        $current_user = $request->user();
        //SELECT users.*, followers.follower_id AS pivot_follower_id, followers.user_id as pivot_user_id, followers.is_approve AS pivot_is_approve FROM users
        //->INNER JOIN followers ON users.id = followers.user_id WHERE followers.follower_id = $request->user->id AND users.id = $userId limit 1
        //SELECT * FROM profiles WHERE profiles.user_id IN ('$current_user->id')
        $is_following_now = $current_user->followings()->with('profile')->findOrFail($userId);
        Log::channel('follow')->info('User was denied his follow',[
            'follower_id' => $current_user->id,
            'user_id' => $is_following_now->id,
        ]);
        //DELETE FROM followers WHERE follower_id = 'current_user.id' AND user_id = '$its_following_now';
        return $current_user->followings()->detach($is_following_now);
    }
}
