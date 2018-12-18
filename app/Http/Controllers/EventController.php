<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Event;
use App\UserEvent;
use App\User;
use Validator;
use App\EventFile;

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
    		'msg'		=> "Success create new event. Let's start to save your file or note here",
            'event_id'  => $event->event_id,
            'event_name' => $event->event_name,
            'created_at' => date('Y-m-d H:i:s'),
            'file_count' => 0
    	]);

    }

    function showEvent(Request $request){
        $user = User::where('user_access_token', $request->access_token)->first();

        $events = Event::whereHas('userEvent', function($q) use ($user){
            $q->where('user_id', $user->user_id);
        })->withCount('eventFile')
        ->withCount('userEvent')
        ->orderBy('event_id', 'desc')
        ->get();

        return response()->json([
            'status'    => true,
            'events'      => $events
        ]);
    }

    public function updateEvent(Request $request){
        $validator = Validator::make($request->all(),[
            'id' => 'required',
            'name'  => 'required',
            'access_token' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'    => false,
                'msg'       => 'Bad Request'
            ]);
        }

        $user = User::where('user_access_token', $request->access_token)->first();
        
        if (empty($user)) {
            return response()->json([
                'status'    => false,
                'msg'       => 'User not found.'
            ]);
        }

        $event = Event::whereHas('userEvent', function($q) use ($user){
            $q->where('user_id', $user->user_id);
        })->find($request->id);

        if (empty($event)) {
            return response()->json([
                'status'    => false,
                'msg'       => 'Event not found'
            ]);   
        }

        $event->event_name = $request->name;
        if ($event->save()) {
            return response()->json([
                'status'    => true,
                'msg'       => 'Event name changed.'
            ]);
        }
    }

    public function showFile($id, Request $request){
        $user = User::where('user_access_token', $request->access_token)->first();

        if(empty($user)){
            return response()->json([
                'status'    => false,
                'msg'       => "User not found."
            ]);
        }


        $eventFile = EventFile::
            join('tb_event', 'tb_event.event_id', '=', 'tb_eventfile.event_id')
            ->join('tb_userevent', 'tb_userevent.event_id', '=', 'tb_event.event_id')
            ->where([
                'tb_eventfile.event_id' => $id,
                'user_id'   => $user->user_id
            ])
            ->select('eventfile_id', 'tb_event.event_id', 'eventfile_title', 'eventfile_content', 'eventfile_format', 'upload_by', 'tb_eventfile.created_at')
            ->get();
        
        if(empty($eventFile[0])){
            return response()->json([
                'status'    => false,
                'msg'       => "There is no file or note uploaded yet!"
            ]);
        }

        return response()->json([
            'status'    => true,
            'msg'       => 'Request success!',
            'file'      => $eventFile
        ]);
    }

    public function delete(Request $request){
        $user = User::where('user_access_token', $request->access_token)->first();
        $event = Event::
            join('tb_userevent', 'tb_userevent.event_id', '=', 'tb_event.event_id')
            ->where([
                'tb_event.event_id' => $request->event_id,
                'user_id'   => $user->user_id
            ])->first();

        if(empty($event)){
            return response()->json([
                'status'    => false,
                'msg'       => 'You cant delete this event.'
            ]);
        }

        //deleting all note
        EventFile::where([
            'event_id' => $event->event_id,
            'eventfile_format'  => 'note'
        ])->delete();

        //get all file to delete
        $file = EventFile::where('event_id', $event->event_id)
            ->get();
        foreach($file as $f){
            File::delete(public_path().'/'.EventFile::$dir.'/'.$f->onserver_filename);
            $f->delete();    
        }

        $event->delete();
        return response()->json([
            'status'    => true,
            'msg'       => 'Event is deleted.'
        ]);
        
    }

    public function user($id){
        //user in this event
        $users = UserEvent::where("event_id", $id)
            ->join('tb_user', 'tb_user.user_id', '=', 'tb_userevent.user_id')
            ->select('tb_user.user_id', 'user_name', 'user_username')
            ->get();
        
        return response()->json([
            'status'    => true,
            'msg'       => 'Request success!',
            'users'     => $users
        ]);
    }

    public function invite(Request $request, $eventId){
        $user = User::where('user_access_token', $request->access_token)->first();

        foreach($request->users as $usr){
            $check = UserEvent::where([
                'user_id'   => $usr,
                'event_id'  => $eventId
            ])->first();

            if(empty($check)){
                $userEvent = new UserEvent;
                $userEvent->user_id = $usr;
                $userEvent->event_id = $eventId;
                $userEvent->save();
            }
        }

        $invitedFriend = User::whereIn('user_id', $request->users)->get();
        $registeredTo = $invitedFriend->pluck('user_fcm');

        $headers = array(
            'Authorization: key='.config('app.fcm_api'),
            'Content-Type: application/json'
        );

        $fields = array(
            'registration_ids'=>$registeredTo,
            'notification' => array(
                'title' => "Keep",
                'body' => "You are invited to " . Event::find($eventId)->event_name,
                'sound'=>'default'
            )
        );

        $curl_session = curl_init();
        curl_setopt($curl_session, CURLOPT_URL,config('app.fcm_url'));
        curl_setopt($curl_session, CURLOPT_POST, true);
        curl_setopt($curl_session, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl_session, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl_session, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl_session, CURLOPT_POSTFIELDS, json_encode($fields));
        $result = curl_exec($curl_session);
        curl_close($curl_session);

        return response()->json([
            'status'    => true,
            'msg'       => 'Success invite friend!'
        ]);
    }
}
