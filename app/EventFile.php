<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EventFile extends Model
{
    protected $table = 'tb_eventfile';
    protected $primaryKey = 'eventfile_id';
    public static $dir = 'file';

    public static function uploadFile($file, $fileName){
        $destinationPath = public_path(self::$dir);
        $file->move($destinationPath, $fileName);
    }
}
