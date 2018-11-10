<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $table = 'tb_event';
    protected $primaryKey = 'event_id';
    

    public function userEvent(){
    	return $this->hasMany('App\UserEvent', 'event_id', 'event_id');
    }

    public function eventFile(){
    	return $this->hasMany('App\EventFile', 'event_id', 'event_id');
    }
}
