<?php

namespace Api\Model;

use Illuminate\Database\Eloquent\Model;

class AccountBalance extends Model
{
    //
    protected $guarded = array("AccountBalanceID");

    protected $table = 'tblAccountBalance';

    protected $primaryKey = "AccountBalanceID";

    public $timestamps = false; // no created_at and updated_at

    public static function addCredit($accountid, $credit_amount)
    {
        $AccountBalance = AccountBalance::where('AccountID', $accountid)->first();
        if (empty($AccountBalance)) {
            $AccountBalance['AccountID'] = $accountid;
            $AccountBalance['PermanentCredit'] = $credit_amount;
            AccountBalance::create($AccountBalance);
        } else {
            $credit_data['PermanentCredit'] = $AccountBalance->PermanentCredit + $credit_amount;
            AccountBalance::where('AccountID', $accountid)->update($credit_data);
        }
        $historydata['AccountID'] = $accountid;
        $historydata['PermanentCredit'] = $credit_amount;
        AccountBalanceHistory::addHistory($historydata);

    }

    public static function subCredit($accountid, $credit_amount)
    {
        $AccountBalance = AccountBalance::where('AccountID', $accountid)->first();
        if (empty($AccountBalance)) {
            $AccountBalance['AccountID'] = $accountid;
            $AccountBalance['PermanentCredit'] = -$credit_amount;
            AccountBalance::create($AccountBalance);
        } else {
            $credit_data['PermanentCredit'] = $AccountBalance->PermanentCredit - $credit_amount;
            AccountBalance::where('AccountID', $accountid)->update($credit_data);
        }
        $historydata['AccountID'] = $accountid;
        $historydata['PermanentCredit'] = $credit_amount;
        AccountBalanceHistory::addHistory($historydata);

    }

    public static function addTempCredit($accountid, $credit_amount, $date)
    {
        $AccountBalance = AccountBalance::where('AccountID', $accountid)->first();
        if (empty($AccountBalance)) {
            $AccountBalance['AccountID'] = $accountid;
            $AccountBalance['PermanentCredit'] = $credit_amount;
            AccountBalance::create($AccountBalance);
        } else {
            $credit_data['TemporaryCredit'] = $credit_amount;
            $credit_data['TemporaryCreditDateTime'] = check_date_format_db($date);
            AccountBalance::where('AccountID', $accountid)->update($credit_data);
        }
        $historydata['AccountID'] = $accountid;
        $historydata['TemporaryCreditDateTime'] = $credit_amount;
        AccountBalanceHistory::addHistory($historydata);
    }

    public static function subTempCredit($accountid, $credit_amount, $date)
    {
        $AccountBalance = AccountBalance::where('AccountID', $accountid)->first();
        if (empty($AccountBalance)) {
            $AccountBalance['AccountID'] = $accountid;
            $AccountBalance['PermanentCredit'] = -$credit_amount;
            AccountBalance::create($AccountBalance);
        } else {
            $credit_data['PermanentCredit'] = $AccountBalance->PermanentCredit - $credit_amount;
            AccountBalance::where('AccountID', $accountid)->update($credit_data);
        }
        $historydata['AccountID'] = $accountid;
        $historydata['TemporaryCreditDateTime'] = $credit_amount;
        AccountBalanceHistory::addHistory($historydata);

    }

    public static function getThreshold($accountid)
    {
        $AccountBalance = AccountBalance::where('AccountID', $accountid)->first();
        $BalanceThreshold = 0;
        if (!empty($AccountBalance)) {
            $BalanceThreshold = $AccountBalance->BalanceThreshold;
        }
        return $BalanceThreshold;

    }

    public static function setThreshold($accountid, $BalanceThreshold)
    {
        $AccountBalance = AccountBalance::where('AccountID', $accountid)->first();
        if (empty($AccountBalance)) {
            $AccountBalance['AccountID'] = $accountid;
            $AccountBalance['BalanceThreshold'] = $BalanceThreshold;
            AccountBalance::create($AccountBalance);
        } else {
            $credit_data['BalanceThreshold'] = $BalanceThreshold;
            AccountBalance::where('AccountID', $accountid)->update($credit_data);
        }
        $historydata['AccountID'] = $accountid;
        $historydata['BalanceThreshold'] = $BalanceThreshold;
        AccountBalanceHistory::addHistory($historydata);

    }
    public static function getBalanceAmount($AccountID){
        return AccountBalance::where(['AccountID'=>$AccountID])->pluck('BalanceAmount');
    }
    public static function getBalanceThreshold($AccountID){
        return str_replace('p', '%',AccountBalance::where(['AccountID'=>$AccountID])->pluck('BalanceThreshold'));
    }
}
