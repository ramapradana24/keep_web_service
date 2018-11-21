<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use App\EventFile;
use App\User;
use App\Event;

class EventFileController extends Controller
{
    public function store(Request $request){
        if(!$request->hasFile("file")){
            $validator = Validator::make($request->all(),[
                'title' => 'required',
                'event_id'  => 'required',
                'content'   => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'    => false,
                    'msg'       => 'Please complete the requirement.'
                ]);
            }

            $user = User::where('user_access_token', $request->access_token)->first();
            $isThisUserEvent = Event::join('tb_userevent', 'tb_userevent.event_id', '=', 'tb_event.event_id')
                ->where([
                    'user_id'  => $user->user_id,
                    'tb_event.event_id'  => $request->event_id
                ])->count();
            
            if($isThisUserEvent <= 0){
                return response()->json([
                    'status'    => false,
                    'msg'       => 'You cant add new note or file to this event.'
                ]);
            }

            $file = new EventFile;
            $file->event_id = $request->event_id;
            $file->eventfile_title = $request->title;
            $file->eventfile_content = $request->content;
            $file->eventfile_format = "note";
            $file->upload_by = $user->user_id;
            $file->save();

            return response()->json([
                'status'    => true,
                'msg'       => "Success creating note.",
                'format'    => "note",
                'uploadBy'  => $user->user_id,
                'id'        => $file->eventfile_id,
                'createdAt' => $file->created_at
            ]);
        }
    }
}
