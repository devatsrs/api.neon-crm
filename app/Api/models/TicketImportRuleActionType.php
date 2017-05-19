<?php
namespace Api\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Input;

class TicketImportRuleActionType extends \Eloquent {

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

	
}