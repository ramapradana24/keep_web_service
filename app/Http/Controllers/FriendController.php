<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\Friendship;

class FriendController extends Controller
{
    public function search($username, Request $request){
        $user = User::where("user_access_token", $request->access_token)->first();
        $userSearch = User::where('user_username', $username)
            ->where('user_id', '<>', $user->user_id)
            ->select('user_id', 'user_username', 'user_name')
            ->first();
        if(empty($userSearch)){
            return response()->json([
                'status'    => false,
                'msg'       => 'User not found!'
            ]);
        }

        $friend = Friendship::where([
            'user_id'   => $user->user_id,
            'user_friend_id'    => $userSearch->user_id
        ])
        ->orWhere([
            'user_id'   => $userSearch->user_id,
            'user_friend_id'    => $user->user_id
        ])->first();

        $isFriend = false;
        if(!empty($friend)){
            $isFriend = true;
        }

        return response()->json([
            'status'    => true,
            'msg'       => 'User found!',
            'user'      => $userSearch
        ]);
    }

    public function add($userid, Request $request){
        $user = User::where("user_access_token", $request->access_token)->first();
        $friend = Friendship::where([
            'user_id'   => $user->user_id,
            'user_friend_id'    => $userid
        ])
        ->orWhere([
            'user_id'   => $userid,
            'user_friend_id'    => $user->user_id
        ])->first();

        if(!empty($friend)){
            return response()->json([
                'status'    => false,
                'msg'       => "You are friend!"
            ]);
        }

        $friendship = new Friendship;
        $friendship->user_id = $user->user_id;
        $friendship->user_friend_id = $userid;
        if($friendship->save()){
            return response()->json([
                'status'    => true,
                'msg'       => 'You are now friend!'
            ]);
        }
    }
}
