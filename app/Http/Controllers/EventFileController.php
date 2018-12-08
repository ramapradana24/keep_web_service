<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use App\EventFile;
use App\User;
use App\Event;
use File;


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

        //upload file
        $validator = Validator::make($request->all(), [
            'access_token'  => 'required',
            'event_id'      => 'required',
            'file'          => 'required'
        ]);
        
        $user = User::where('user_access_token', $request->access_token)->first();
        if(empty($user)){
            return response()->json([
                'status'    => false,
                'msg'       => 'User unknown.'
            ]);
        }

        if($validator->fails()){
            return response()->json([
                'status'    => false,
                'msg'       => 'Bad Request!'
            ]);
        }

        $contentList = collect([
            ['format' => 'doc', 'content' => 'Word Document'],
            ['format' => 'docx', 'content' => 'Word Dosument' ],
            ['format' => 'txt', 'content' => 'Text File' ],
            ['format' => 'png', 'content' => 'Image File' ],
            ['format' => 'jpg', 'content' => 'Image File' ],
            ['format' => 'jpeg', 'content' => 'Image File' ],
            ['format' => 'bmp', 'content' => 'Image File' ],
            ['format' => 'ppt', 'content' => 'Power Point Document' ],
            ['format' => 'pptx', 'content' => 'Power Point Document' ],
            ['format' => 'pdf', 'content' => 'PDF File' ],
            ['format' => 'mp3', 'content' => 'Music' ],
            ['format' => 'mp4', 'content' => 'Video/Movie' ],
            ['format' => 'mkv', 'content' => 'Video/Movie' ],
            ['format' => 'html', 'content' => 'Hyper Text Markup Language' ],
            ['format' => 'rar', 'content' => 'Compressed File' ],
            ['format' => 'zip', 'content' => 'Compressed File' ],
        ]);

        if($request->hasFile("file")){
            $filename = $request->file("file")->getClientOriginalName();
            $format = $request->file('file')->getClientOriginalExtension();
            $content = $contentList->where('format', $format)->first()['content'];
            if(empty($content)){
                $content = 'Unknown File Format';
            }

            $onServerFileName = $user->user_id.'_'.uniqid().'.'.$format;

            $eventFile = new EventFile;
            $eventFile->event_id = $request->event_id;
            $eventFile->eventfile_title = $filename;
            $eventFile->eventfile_content = $content;
            $eventFile->eventfile_format = $format;
            $eventFile->onserver_filename = $onServerFileName;
            $eventFile->upload_by = $user->user_id;
            if($eventFile->save()){
                EventFile::uploadFile($request->file('file'), $onServerFileName);
            }
            
            return response()->json([
                'status'    => true,
                'msg'       => "file uploaded with server name " . $onServerFileName
            ]);
        }
    }

    public function updateNote($id, Request $request){
        $validator = Validator::make($request->all(), [
            'access_token'  => 'required',
            'title'         => 'required',
            'content'       => 'required'
        ]);

        if($validator->fails()){
            return response()->json([
                'status' => false,
                'mag'    => "Bad Request!"
            ]);
        }

        $user = User::where('user_access_token', $request->access_token)->first();
        if(empty($user)){
            return response()->json([
                'status'    => false,
                'msg'       => 'User not found.'
            ]);
        }

        $file = EventFile::
            join('tb_event', 'tb_event.event_id', '=', 'tb_eventfile.event_id')
            ->join('tb_userevent', 'tb_userevent.event_id', '=', 'tb_event.event_id')
            ->where([
                'tb_eventfile.eventfile_id' => $id,
                'user_id'   => $user->user_id
            ])
            ->first();
        
        if(empty($file)){
            return response()->json([
                'status'    => false,
                'msg'       => 'You cant update this note!'
            ]);
        }

        $eventfile = EventFile::find($id);
        $eventfile->eventfile_title = $request->title;
        $eventfile->eventfile_content = $request->content;
        if($eventfile->save()){
            return response()->json([
                'status'    => true,
                'msg'       => 'Success updating note!'
            ]);
        }

        return response()->json([
            'status'    => false,
            'msg'       => 'Failed to update this note!'
        ]);

        
    }

    public function delete($id, Request $request){
        $user = User::where('user_access_token', $request->access_token)->first();
        $file = EventFile::
            join('tb_event', 'tb_event.event_id', '=', 'tb_eventfile.event_id')
            ->join('tb_userevent', 'tb_userevent.event_id', '=', 'tb_event.event_id')
            ->where([
                'eventfile_id' => $id,
                'tb_userevent.user_id' => $user->user_id
            ])->first();
        
        if(empty($file)){
            return response()->json([
                'status'    => false,
                'msg'       => 'You cant delete this file or note.'   
            ]);
        }

        if($file->onserver_filename != null){
            File::delete(EventFile::$dir.'/'.$file->onserver_filename);
        }
        
        $file->delete();
        return response()->json([
            'status'    => true,
            'msg'       => 'File or note has been deleted'
        ]);

    }

    public function download($id){
        $file = EventFile::find($id);

        $filePath = public_path() . "/file/" . $file->onserver_filename;
        $header = array('Content-Type: application/*');
        return response()->download($filePath, $file->eventfile_title, $header);
    }
}
