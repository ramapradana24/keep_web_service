<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserEvent extends Model
{
    protected $table = 'tb_userevent';
    protected $primaryKey = 'userevent_id';
}
