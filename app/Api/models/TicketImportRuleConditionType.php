<?php
namespace Api\Model;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TicketImportRuleConditionType extends \Eloquent  {

    protected $table 		= 	"tblTicketImportRuleConditionType";
    protected $primaryKey 	= 	"TicketImportRuleConditionTypeID";
    protected $guarded 		=	 array("TicketImportRuleConditionTypeID");

    const EMAIL_FROM = 'from_email';
    const EMAIL_TO = 'to_email';
    const SUBJECT = 'subject';
    const DESCRIPTION = 'description';
    const SUB_OR_DESC = 'subject_or_description';
    const PRIORITY = 'priority';
    const STATUS = 'status';
    const AGENT = 'agent';
    const GROUP = 'group';

    protected $enable_cache = true;
    protected $cache_name = "TicketImportRuleConditionType";

    // load all types in cache
    function __construct(){

        if ($this->enable_cache && Cache::has($this->cache_name)) {

            $cache = Cache::get($this->cache_name);

        } else {
            $cache = array();
            $cache[$this->cache_name] = TicketImportRuleConditionType::lists('Condition','TicketImportRuleConditionTypeID');
            Cache::forever($this->cache_name, $cache);

        }
        Log::info("TicketImportRuleConditionType");
        Log::info($cache);

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

    function isEmailFrom($TicketImportRuleConditionTypeID) {

        if($this->get($TicketImportRuleConditionTypeID) == self::EMAIL_FROM){
            return true;
        }
        return false;
    }

    function isEmailTo($TicketImportRuleConditionTypeID) {

        if($this->get($TicketImportRuleConditionTypeID) == self::EMAIL_TO){
            return true;
        }
        return false;
    }

    function isSubject($TicketImportRuleConditionTypeID) {

        if($this->get($TicketImportRuleConditionTypeID) == self::SUBJECT){
            return true;
        }
        return false;
    }

    function isDescription($TicketImportRuleConditionTypeID) {

        if($this->get($TicketImportRuleConditionTypeID) == self::DESCRIPTION){
            return true;
        }
        return false;
    }

    function isSubOrDesc($TicketImportRuleConditionTypeID) {

        if($this->get($TicketImportRuleConditionTypeID) == self::SUB_OR_DESC){
            return true;
        }
        return false;
    }
    function isPriority($TicketImportRuleConditionTypeID) {

        if($this->get($TicketImportRuleConditionTypeID) == self::PRIORITY){
            return true;
        }
        return false;
    }

    function isStatus($TicketImportRuleConditionTypeID) {

        if($this->get($TicketImportRuleConditionTypeID) == self::STATUS){
            return true;
        }
        return false;
    }

    function isAgent($TicketImportRuleConditionTypeID) {

        if($this->get($TicketImportRuleConditionTypeID) == self::AGENT){
            return true;
        }
        return false;
    }

    function isGroup($TicketImportRuleConditionTypeID) {

        if($this->get($TicketImportRuleConditionTypeID) == self::GROUP){
            return true;
        }
        return false;
    }

    function validate($TicketData,$TicketImportRuleConditionTypeID,$Operand,$Value) {

        $field = "";
        //@TODO: this is for requester email match only.
        if ($TicketImportRuleConditionTypeID > 0 ) {

            if ($this->isEmailFrom($TicketImportRuleConditionTypeID)) {
                $field = $TicketData["Requester"];
                Log::info("fromEmail " . $field);
            } else if ($this->isEmailTo($TicketImportRuleConditionTypeID)) {
                $field = $TicketData["EmailTo"];
                Log::info("EmailTo " . $field);
            } else if ($this->isSubject($TicketImportRuleConditionTypeID)) {
                $field = $TicketData["Subject"];
                Log::info("Subject " . $field);
            } else if ($this->isDescription($TicketImportRuleConditionTypeID)) {
                $field = $TicketData["Description"];
                Log::info("Description " . $field);
            } else if ($this->isSubOrDesc($TicketImportRuleConditionTypeID)) {
                $field = $TicketData["Description"];
                Log::info("Description " . $field);

                if (TicketImportRuleCondition::field($field)->operand($Operand)->value($Value)->check()) {
                    return true;
                }

                $field = $TicketData["Subject"];
                Log::info("Subject " . $field);

            } else if ($this->isPriority($TicketImportRuleConditionTypeID)) {
                $field = $TicketData["Priority"];
                Log::info("Priority " . $field);
            } else if ($this->isStatus($TicketImportRuleConditionTypeID)) {
                $field = $TicketData["Status"];
                Log::info("Status " . $field);
            } else if ($this->isAgent($TicketImportRuleConditionTypeID)) {
                $Agent = TicketsTable::where(["TicketID",$TicketData])->pluck("Agent");
                $field = $Agent;
                Log::info("Agent " . $Agent);
            } else if ($this->isGroup($TicketImportRuleConditionTypeID)) {
                $field = $TicketData["Group"];
                Log::info("Group " . $field);
            }

            if (TicketImportRuleCondition::field($field)->operand($Operand)->value($Value)->check()) {

                return true;
            }
        }

        return false;


    }
 }
