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
            $CustomerID = User::get_userID();
        }else {
            $UserID = User::get_userID();
            $CustomerID = 0;
        }
        $CompanyID = User::get_companyID();
        $data = ['UserID' => $UserID,
            'CustomerID' => $CustomerID,
            'CompanyID' => $CompanyID,
            'TicketID' => $TicketID,
            'created_at' => date("Y-m-d H:i:s")];
        TicketLog::insert($data);
    }

}