<?php
namespace Api\Model;

class TicketLog extends \Eloquent
{
    protected $guarded = array("TicketLogID");

    protected $table = 'tblTicketLog';

    protected $primaryKey = "TicketLogID";

    public static function AddLog($TicketID,$isCustomer = 0){
        if($isCustomer == 1){
            $UserID = 0;
            $AccountID = User::get_userID();
        }else {
            $UserID = User::get_userID();
            $AccountID = 0;
        }
        $CompanyID = User::get_companyID();
        $data = ['UserID' => $UserID,
            'AccountID' => $AccountID,
            'CompanyID' => $CompanyID,
            'TicketID' => $TicketID,
            'created_at' => date("Y-m-d H:i:s")];
        TicketLog::insert($data);
    }

}