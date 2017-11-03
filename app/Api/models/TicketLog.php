<?php
namespace Api\Model;

class TicketLog extends \Eloquent
{
    protected $guarded = array("TicketLogID");

    protected $table = 'tblTicketLog';

    protected $primaryKey = "TicketLogID";


    static  $defaultTicketLogFields = [
        'Type'=>Ticketfields::default_ticket_type,
        'Status'=>Ticketfields::default_status,
        'Priority'=>Ticketfields::default_priority,
        'Group'=>Ticketfields::default_group,
        'Agent'=>Ticketfields::default_agent
    ];


    const NEW_TICKET = 'new_ticket';
    const STATUS_CHANGED = 'status_changed';

    public static function insertTicketLog($TicketID,$Action,$isCustomer = 0 , $status  = 0 ) {


        if($Action == self::NEW_TICKET){

            self::NewTicketTicketLog($TicketID,$isCustomer);

        } else if($Action == self::STATUS_CHANGED ) {

            self::StatusChangedTicketLog($TicketID,$isCustomer ,$status );
        }


    }
    public static function NewTicketTicketLog($TicketID,$isCustomer = 0) {

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
			"NewTicket" =>1,
            'created_at' => date("Y-m-d H:i:s")];
        TicketLog::insert($data);
    }

    public static function StatusChangedTicketLog($TicketID,$isCustomer = 0,$status) { //updateEmailLog
        if($isCustomer == 1){
            $UserID = 0;
            $AccountID = User::get_userID();
        } else {
            $UserID = User::get_userID();
            $AccountID = 0;
        }
        $CompanyID = User::get_companyID();
        $FieldsID = TicketfieldsValues::where(['ValuesID'=>$status])->pluck('FieldsID');
        $TicketFieldValueFromID = 0;
        $data = ['UserID' => $UserID,
            'AccountID' => $AccountID,
            'CompanyID' => $CompanyID,
            'TicketID' => $TicketID,
            'TicketFieldID' => $FieldsID,
            'TicketFieldValueFromID' => $TicketFieldValueFromID,
			'TicketFieldValueToID' => $status,
            'created_at' => date("Y-m-d H:i:s")];
        TicketLog::insert($data);
    }

}