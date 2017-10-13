<?php

namespace Api\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AccountAuthenticate extends Model
{

    protected $fillable = [];
    protected $guarded = array('AccountAuthenticateID');
    protected $table = 'tblAccountAuthenticate';
    protected  $primaryKey = "AccountAuthenticateID";
    public $timestamps = false;
}
