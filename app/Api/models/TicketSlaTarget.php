<?php
namespace Api\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TicketSlaTarget extends Model {

    protected $table 		= 	"tblTicketSlaTarget";
    protected $primaryKey 	= 	"SlaTargetID";
	protected $guarded 		=	 array("SlaTargetID");	
	
	
	static function ProcessTargets($id){
		
			$targets 		= 	TicketSlaTarget::where(['TicketSlaID'=>$id])->get();
			$targets_array	= 	array();
			
			foreach($targets as $targetsData)	
			{
				$targets_array[TicketPriority::getPriorityStatusByID($targetsData['PriorityID'])]	 = 
				array(
					"RespondTime"=>$targetsData['RespondValue'],
					"RespondType"=>$targetsData['RespondType'],
					"ResolveTime"=>$targetsData['ResolveValue'],
					"ResolveType"=>$targetsData['ResolveType'],
					"SlaOperationalHours"=>$targetsData['OperationalHrs'],
					"Escalationemail"=>$targetsData['EscalationEmail'],
				);
			}
			
			return $targets_array;
	}
}


