<?php

namespace Api\Model;

use Illuminate\Database\Eloquent\Model;

class AccountBalanceHistory extends Model
{
    //
    protected $guarded = array("AccountBalanceHistoryID");

    protected $table = 'tblAccountBalanceHistory';

    protected $primaryKey = "AccountBalanceHistoryID";

    public $timestamps = false; // no created_at and updated_at


    public static function addHistory($historydata){
        $historydata['created_at'] = date('Y-m-d H:i:s');
        $historydata['CreatedBy'] = User::get_user_full_name();
        AccountBalanceHistory::create($historydata);
    }
}
