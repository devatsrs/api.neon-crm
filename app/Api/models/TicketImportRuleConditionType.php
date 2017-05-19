<?php
namespace Api\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Input;

class TicketImportRuleConditionType extends \Eloquent {

    protected $table 		= 	"tblTicketImportRuleConditionType";
    protected $primaryKey 	= 	"TicketImportRuleConditionTypeID";
	protected $guarded 		=	 array("TicketImportRuleConditionTypeID");	
	
	const EMAIL_FROM = 'from_email';
    const EMAIL_TO = 'to_email';
    const SUBJECT = 'subject';
    const DESCRIPTION = 'description';
    const DESC_OR_SUB = 'subject_or_description';
    const PRIORITY = 'priority';
    const STATUS = 'status';
    const AGENT = 'agent';
    const GROUP = 'group';
	
	
	static $DifferentCondtionsArray = array(self::PRIORITY ,self::STATUS,self::AGENT,self::GROUP);
	
	static $DifferentCondtionsArrayValue = array(
		self::PRIORITY=>"condition_value_priority",
		self::STATUS=>"condition_value_status",
		self::GROUP=>"condition_value_group",
		self::AGENT=>"condition_value_agent"
	);
	
}