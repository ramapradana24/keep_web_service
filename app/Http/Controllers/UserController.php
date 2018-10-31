<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;

class UserController extends Controller
{
    public function updateProfile(Request $request){
    	$user = User::find($request->id);
    	$user->user_name = $request->user_name;
    	$user->user_username = $request->user_username;
    	$user->user_email = $request->user_email;
    	$user->save();

    	return response()->json([
    		'status'	=> true,
    		'msg'		=> 'Update Profile success.'
    	]);
    }
}
