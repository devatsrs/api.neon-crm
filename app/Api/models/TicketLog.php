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
    const TICKET_REPLIED = 'ticket_replied';

    const TICKET_ACTION_CREATED = 1;
    const TICKET_ACTION_ASSIGNED_TO = 2;
    const TICKET_ACTION_AGENT_REPLIED = 3;
    const TICKET_ACTION_CUSTOMER_REPLIED = 4;
    const TICKET_ACTION_STATUS_CHANGED = 5;
    const TICKET_ACTION_NOTE_ADDED = 6;
    const TICKET_ACTION_FIELD_CHANGED = 7;

    const TICKET_USER_TYPE_ACCOUNT = 1;
    const TICKET_USER_TYPE_CONTACT = 2;
    const TICKET_USER_TYPE_USER = 3;
    const TICKET_USER_TYPE_SYSTEM = 4;

    static  $TicketUserTypes = [ self::TICKET_USER_TYPE_ACCOUNT  => "Account",
                                self::TICKET_USER_TYPE_CONTACT  => "Contact",
                                self::TICKET_USER_TYPE_USER     => "User",
                                self::TICKET_USER_TYPE_SYSTEM   => "System"
    ];


    public static function insertTicketLog($TicketID,$Action,$isCustomer = 0 , $status  = 0 ) {

        if($isCustomer == 1){
            $AccountID = User::get_userID();
            $ParentID = $AccountID;
            $ParentType = TicketLog::TICKET_USER_TYPE_ACCOUNT;

        }else {
            $UserID = User::get_userID();
            $ParentID = $UserID;
            $ParentType = TicketLog::TICKET_USER_TYPE_USER;
        }

        $CompanyID = User::get_companyID();
        $data = [
            'CompanyID' => $CompanyID,
            'TicketID' => $TicketID,
            "ParentID" =>$ParentID,
            "ParentType" =>$ParentType,
            "Action" =>$Action,
            'created_at' => date("Y-m-d H:i:s")
        ];

        $TicketUserName = User::get_user_full_name();

        if($Action == self::TICKET_ACTION_CREATED){

            $data["ActionText"]  = "Ticket Created by " . $TicketUserName;

            //self::NewTicketTicketLog($TicketID,$isCustomer);

        } else if($Action == self::TICKET_ACTION_STATUS_CHANGED ) {

            $FieldName = TicketfieldsValues::where(['ValuesID'=>$status])->pluck('FieldValueAgent');
            $ActionText = "Status Changed to " . $FieldName  . " by " . $TicketUserName ;
            $data["ActionText"]  = $ActionText;

        } else if($Action == self::TICKET_ACTION_AGENT_REPLIED ) {

            $data["ActionText"]  = "Ticket Replied by " . TicketLog::$TicketUserTypes[$data["ParentType"]]  . " " . $TicketUserName;

         }
        TicketLog::insert($data);


    }

    //-- not in use
    public static function NewTicketTicketLog($TicketID,$isCustomer = 0) {

        if($isCustomer == 1){
            $AccountID = User::get_userID();
            $ParentID = $AccountID;
            $ParentType = TicketLog::TICKET_USER_TYPE_ACCOUNT;

        }else {
            $UserID = User::get_userID();
            $ParentID = $UserID;
            $ParentType = TicketLog::TICKET_USER_TYPE_USER;

        }
        $CompanyID = User::get_companyID();
        $data = [
            'CompanyID' => $CompanyID,
            'TicketID' => $TicketID,
			"ParentID" =>$ParentID,
			"ParentType" =>$ParentType,
			"Action" =>self::TICKET_ACTION_CREATED,
            'created_at' => date("Y-m-d H:i:s")
        ];

        TicketLog::insert($data);
    }

    // not in use
    public static function StatusChangedTicketLog($TicketID,$isCustomer = 0,$status) { //updateEmailLog


        if($isCustomer == 1){
            $AccountID = User::get_userID();
            $ParentID = $AccountID;
            $ParentType = TicketLog::TICKET_USER_TYPE_ACCOUNT;

        }else {
            $UserID = User::get_userID();
            $ParentID = $UserID;
            $ParentType = TicketLog::TICKET_USER_TYPE_USER;

        }

        $CompanyID = User::get_companyID();
        $FieldName = TicketfieldsValues::where(['ValuesID'=>$status])->pluck('FieldValueAgent');
        $ActionText = "Status Changed to " . $FieldName;

        $data = [
            'CompanyID' => $CompanyID,
            'TicketID' => $TicketID,
            "ParentID" => $ParentID,
            "ParentType" => $ParentType,
            "Action" => self::TICKET_ACTION_STATUS_CHANGED,
            "ActionText" => $ActionText,
            'created_at' => date("Y-m-d H:i:s")
        ];

        TicketLog::insert($data);
    }

}