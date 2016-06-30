<?php

namespace Api\Model;

use Illuminate\Database\Eloquent\Model;

class AccountEmailLog extends \Eloquent {
    protected $guarded = array("AccountEmailLogID");
    protected $fillable = [];
    protected $table = "AccountEmailLog";
    protected $primaryKey = "AccountEmailLogID";

}