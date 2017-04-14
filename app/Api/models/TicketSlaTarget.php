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
		
			$targets 		= 	TicketSlaTarget::where(['SlaPolicyID'=>$id])->get();
			$targets_array	= 	array();
			
			foreach($targets as $targetsData)	
			{
				$targets_array[TicketPriority::getPriorityStatusByID($targetsData['PritiryID'])]	 = 
				array(
					"RespondTime"=>$targetsData['RespondWithinTimeValue'],
					"RespondType"=>$targetsData['RespondWithinTimeType'],
					"ResolveTime"=>$targetsData['ResolveWithinTimeValue'],
					"ResolveType"=>$targetsData['ResolveWithinTimeType'],
					"SlaOperationalHours"=>$targetsData['OperationalHrs'],
					"Escalationemail"=>$targetsData['EscalationEmail'],
				);
			}
			
			return $targets_array;
	}
}


