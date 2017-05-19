<?php
namespace Api\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Input;

class TicketImportRuleCondition extends \Eloquent {

    protected $table 		= 	"tblTicketImportRuleCondition";
    protected $primaryKey 	= 	"TicketImportRuleConditionID";
	protected $guarded 		=	 array("TicketImportRuleConditionID");	
}