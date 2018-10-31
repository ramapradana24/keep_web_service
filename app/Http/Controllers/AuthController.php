<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use Hash;

class AuthController extends Controller
{
    public function register(Request $request){
    	try{

    		$checkingUname = User::where('user_username', $request->username)->count();
    		if ($checkingUname > 0) {
    			return response()->json([
    				'status'	=> false,
    				'msg'		=> 'Username has been used by other person.'
    			], 200);
    		}

    		$user = new User;
	    	$user->user_name = $request->name;
	    	$user->user_username = $request->username;
	    	$user->user_email = $request->email;
	    	$user->user_password = bcrypt($request->password);
	    	$user->save();	

	    	return response()->json([
    			'status'	=> true,
    			'msg'		=> 'Success registering mew user.'
    		], 200);
    	} catch(Illuminate\Database\QueryException $e){
    		return response()->json([
    			'status'	=> false,
    			'msg'		=> 'Error inserting data.'
    		], 200);
    	}
    }


    public function login(Request $request){
    	try{
    		$user = User::where('user_username', '=', $request->username)
    			->whereNull('user_access_token')
    			->first();

    		if (empty($user)) {
    			return response()->json([
    				'status'	=> false,
    				'msg'		=> 'user not found.'
    			], 200);
    		}
            

            if (Hash::check($request->password, $user->user_password) == false) {
                return response()->json([
                    'status'    => false,
                    'msg'       => 'Wrong password.'
                ], 200);   
            }

    		$user->user_access_token = bcrypt(uniqid());
    		$user->save();

    		return response()->json([
    			'status'	=> true,
    			'msg'		=> 'User found. Login success.',
    			'user_name'	=> $user->user_name,
    			'user_username'	=> $user->user_username,
    			'user_email'	=> $user->user_email,
    			'user_access_token'	=> $user->user_access_token
    		], 200);

    	}catch(Illuminate\Database\QueryException $e){
    		return response()->json([
    			'status'	=> false,
    			'msg'		=> 'Server error. Please try again leter.'
    		], 200);
    	}
    
    }

    public function logout(Request $request){
    	$user = User::where('user_access_token', $request->access_token)->first();

    	if (empty($user)) {
    		return response()->json([
    			'status'	=> false,
    			'msg'		=> "User not found or isn't login, cant logout right now."
    		], 200);
    	}

    	$user->user_access_token = '';
    	$user->save();

    	return response()->json([
    		'status'	=> true,
    		'msg'		=> 'Logout success.'
    	], 200);
    }
}
