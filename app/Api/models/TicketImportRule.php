<?php
namespace Api\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Input;

class TicketImportRule extends \Eloquent {
	
    protected $table 		= 	"tblTicketImportRule";
    protected $primaryKey 	= 	"TicketImportRuleID";
	protected $guarded 		=	 array("TicketImportRuleID");	

}