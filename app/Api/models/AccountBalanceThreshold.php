<?php

namespace Api\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AccountBalanceThreshold extends Model
{
    //
    protected $guarded = array("AccountBalanceThresholdID");

    protected $table = 'tblAccountBalanceThreshold';

    protected $primaryKey = "AccountBalanceThresholdID";

    public $timestamps = false; // no created_at and updated_at

    public static function saveAccountBalanceThreshold($accountid, $post)
    {
        
        foreach ($post['BalanceThreshold'] as $key => $value) {
            $data = [
            'AccountID' => $accountid,
            'BalanceThreshold' => $value,
            "BalanceThresholdEmail" =>$post['email'][$key]
        ];
            AccountBalanceThreshold::insert($data);
        }

    }

}
