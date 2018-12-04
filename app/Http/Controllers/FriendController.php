<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\Friendship;
use DB;

class FriendController extends Controller
{
    public function search($username, Request $request){
        $user = User::where("user_access_token", $request->access_token)->first();
        $userSearch = User::where('user_username', $username)
            ->where('user_id', '<>', $user->user_id)
            ->select('user_id', 'user_username', 'user_name', 'user_email')
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

        $friend = DB::table('tb_friendship')
            ->whereRaw(
                "(user_id = ". $user->user_id ." and user_friend_id = ". $userid .") or 
                (user_id = ". $userid ." and user_friend_id = ". $user->user_id .")")
            ->get();

        if(!empty($friend[0])){
            return response()->json([
                'status'    => false,
                'msg'       => "You are already friend!"
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

    public function friends(Request $request){
        $user = User::where('user_access_token', $request->access_token)->first();
        if(empty($user)){
            return response()->json([
                'status'    => false,
                'msg'       => 'User not found or not authenticated.'
            ]);
        }

        // $friends = Friendship::
        //     join('tb_user', 'tb_user.user_id', '=', 'tb_friendship.user_friend_id')
        //     ->where('tb_friendship.user_id', $user->user_id)
        //     ->selectRaw('user_friend_id as user_id, user_username, user_name, user_email')
        //     ->get();
        
        $friends = DB::table('tb_friendship')
            ->join('tb_user', 'tb_user.user_id', '=', 'tb_friendship.user_friend_id')
            ->where('tb_friendship.user_id', $user->user_id)
            ->selectRaw('user_friend_id as user_id, user_username, user_name, user_email');
            
        
        // $friendMore = Friendship::
        //     join('tb_user', 'tb_user.user_id', '=', 'tb_friendship.user_id')
        //     ->where('tb_friendship.user_friend_id', $user->user_id)
        //     ->selectRaw('user_friend_id as user_id, user_username, user_name, user_email')
        //     ->get();
        
        $friendMore = DB::table('tb_friendship')
            ->join('tb_user', 'tb_user.user_id', '=', 'tb_friendship.user_id')
            ->where('tb_friendship.user_friend_id', $user->user_id)
            ->selectRaw('user_friend_id as user_id, user_username, user_name, user_email')
            ->union($friends)
            ->orderBy('user_id', 'desc')
            ->get();
        
        return response()->json([
            'status' => true,
            'msg'    => "Request success!",
            'user'   => $friendMore
        ]);
    }
}
