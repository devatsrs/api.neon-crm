<?php
namespace Api\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Input;

class TicketImportRuleAction extends \Eloquent {

    protected $table 		= 	"tblTicketImportRuleAction";
    protected $primaryKey 	= 	"TicketImportRuleActionID";
	protected $guarded 		=	 array("TicketImportRuleActionID");		
	
}