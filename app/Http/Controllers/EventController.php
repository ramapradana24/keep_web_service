<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Event;
use App\UserEvent;
use App\User;

class EventController extends Controller
{
    public function createEvent(Request $request){
    	$event = new Event;
    	$event->event_name = $request->name;
    	if (!$event->save()) {
    		return response()->json([
    			'status'	=> false,
    			'msg'		=> 'cannot create new event.'
    		]);
    	}

    	$user = User::where('user_access_token', $request->access_token)->first();


    	$userEvent = new UserEvent;
    	$userEvent->event_id = $event->event_id;
    	$userEvent->user_id = $user->user_id;
    	$userEvent->is_admin = 1;
    	if (!$userEvent->save()) {
    		return response()->json([
    			'status'	=> false,
    			'msg'		=> 'cannot create new event.'
    		]);	
    	}

    	return response()->json([
    		'status'	=> true,
    		'msg'		=> "Success create new event. Let's start to save your file or note here"
    	]);

    }

    function showEvent(Request $request){
        $user = User::where('user_access_token', $request->access_token)->first();

        $events = Event::whereHas('userEvent', function($q) use ($user){
            $q->where('user_id', $user->user_id);
        })->withCount('eventFile')->get();

        return response()->json([
            'status'    => true,
            'events'      => $events
        ]);
    }
}
