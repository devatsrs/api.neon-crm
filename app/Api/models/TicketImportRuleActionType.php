<?php
namespace Api\Model;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TicketImportRuleActionType extends \Eloquent  {

    protected $table 		= 	"tblTicketImportRuleActionType";
    protected $primaryKey 	= 	"TicketImportRuleActionTypeID";
    protected $guarded 		=	 array("TicketImportRuleActionTypeID");


    const DELETE_TICKET = 'delete_ticket';
    const SKIP_NOTIFICATION = 'skip_notification';
    const SET_PRIORITY = 'set_priority';
    const SET_STATUS = 'set_status';
    const SET_AGENT = 'set_agent';
    const SET_GROUP = 'set_group';
	const SET_TYPE = 'set_type';
	
	static $ActionArrayValue = array(
		self::DELETE_TICKET=>"skip",
		self::SKIP_NOTIFICATION=>"skip",
		self::SET_PRIORITY=>"condition_match_priority",
		self::SET_STATUS=>"condition_match_status",	
		self::SET_AGENT=>"condition_match_agent",
		self::SET_GROUP=>"condition_match_group",
		self::SET_TYPE=>"condition_match_type",	
	);

    protected $enable_cache = true;
    protected $cache_name = "TicketImportRuleActionType";

    // load all types in cache
    function __construct(){

        if ($this->enable_cache && Cache::has($this->cache_name)) {

            $cache = Cache::get($this->cache_name);

        } else {
            $cache = array();
            $cache[$this->cache_name] = TicketImportRuleActionType::lists('Action','TicketImportRuleActionTypeID');
            Cache::forever($this->cache_name, $cache);

        }
        return $cache[$this->cache_name];
    }

    // get type value by id
    function get($key){

        $cache = Cache::get($this->cache_name);
        $cache = isset($cache[$this->cache_name])?$cache[$this->cache_name]:"";

        if(!empty($cache) && isset($cache[$key])){
            return $cache[$key];
        }
        return "";

    }

    function isDeleteTicket($TicketImportRuleActionTypeID){

        if($this->get($TicketImportRuleActionTypeID) == self::DELETE_TICKET){
            Log::info("DELETE_TICKET " );
            return true;
        }
        return false;
    }

    function isSkipNotification($TicketImportRuleActionTypeID){

        if($this->get($TicketImportRuleActionTypeID) == self::SKIP_NOTIFICATION){
            return true;
        }
        return false;
    }

    function isSetPriority($TicketImportRuleActionTypeID){

        if($this->get($TicketImportRuleActionTypeID) == self::SET_PRIORITY){
            return true;
        }
        return false;
    }

    function isSetStatus($TicketImportRuleActionTypeID){

        if($this->get($TicketImportRuleActionTypeID) == self::SET_STATUS){
            return true;
        }
        return false;
    }

    function isSetAgent($TicketImportRuleActionTypeID){

        if($this->get($TicketImportRuleActionTypeID) == self::SET_AGENT){
            return true;
        }
        return false;
    }

    function isSetGroup($TicketImportRuleActionTypeID){

        if($this->get($TicketImportRuleActionTypeID) == self::SET_GROUP){
            return true;
        }
        return false;
    }

    public function doActions($TicketImportRuleID,$TicketData) {

        $TicketImportRuleActions = TicketImportRuleAction::where(["TicketImportRuleID" => $TicketImportRuleID])->orderby("Order")->get();

        $TicketID = $TicketData["TicketID"];
        $log = array();

        Log::info("doActions - " . count($TicketImportRuleActions) );
        Log::info($TicketImportRuleActions);

        if(count($TicketImportRuleActions) > 0) {

            foreach ($TicketImportRuleActions as $TicketImportRuleAction) {

                $TicketImportRuleActionTypeID = $TicketImportRuleAction["TicketImportRuleActionTypeID"];
                $Value                        = $TicketImportRuleAction["Value"];

                Log::info("TicketImportRuleActionTypeID " . $TicketImportRuleActionTypeID);

                if ($this->isDeleteTicket($TicketImportRuleActionTypeID)) {
                    TicketsTable::deleteTicket($TicketID);
                    $log[] = TicketImportRuleActionType::DELETE_TICKET;
                    Log::info($log);
                    return $log;

                } else if ($this->isSkipNotification($TicketImportRuleActionTypeID)) {

                    $log[] = TicketImportRuleActionType::SKIP_NOTIFICATION;

                } else if ($this->isSetPriority($TicketImportRuleActionTypeID)) {

                    TicketsTable::setTicketFieldValue($TicketID,"Priority",$Value);
                    $log[] = TicketImportRuleActionType::SET_PRIORITY;

                } else if ($this->isSetStatus($TicketImportRuleActionTypeID)) {

                    TicketsTable::setTicketFieldValue($TicketID,"Status",$Value);
                    $log[] = TicketImportRuleActionType::SET_STATUS;

                } else if ($this->isSetAgent($TicketImportRuleActionTypeID)) {

                    TicketsTable::setTicketFieldValue($TicketID,"Agent",$Value);
                    $log[] = TicketImportRuleActionType::SET_AGENT;
                } else if ($this->isSetGroup($TicketImportRuleActionTypeID)) {

                    TicketsTable::setTicketFieldValue($TicketID,"Group",$Value);
                    $log[] = TicketImportRuleActionType::SET_GROUP;
                }
            }
        }

        return $log;
    }

}
