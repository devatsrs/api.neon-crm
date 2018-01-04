<?php

namespace Api\Model;

use Illuminate\Database\Eloquent\Model;

class AccountEmailLog extends \Eloquent {
    protected $guarded = array("AccountEmailLogID");
    protected $fillable = [];
    protected $table = "AccountEmailLog";
    protected $primaryKey = "AccountEmailLogID";
	
    const InvoicePaymentReminder=1;
    const LowBalanceReminder=2;
    const QosACDAlert =3;
    const QosASRAlert =4;
    const CallDurationAlert = 5;
    const CallCostAlert = 6;
    const CallOfficeAlert = 7;
    const CallBlackListAlert = 8;
    const VendorBalanceReport = 9;
	const TicketEmail = 10;

    public static function getLastMessageIDByTicketID($TicketID){

        return AccountEmailLog::where(["TicketID"=>$TicketID])->orderby("AccountEmailLogID","desc")->pluck("MessageID");
    }

}