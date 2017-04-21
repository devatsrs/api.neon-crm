<?php
namespace Api\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TicketsWorkingDays extends Model {
	
    protected $table 		= 	"tblTicketsWorkingDays";
    protected $primaryKey 	= 	"ID";
	protected $guarded 		=	 array("ID");
	
	
	static function ProcessWorkingDays($BusinessHoursID){
		$final		  =	 array();
		$WorkingDays  =  TicketsWorkingDays::where(["BusinessHoursID"=>$BusinessHoursID])->get();
		
		foreach($WorkingDays as $WorkingDaysData){ 
		
			$startTime 		=  date("g:i", strtotime($WorkingDaysData->StartTime));
			$startTimeType  =  date("a", strtotime($WorkingDaysData->StartTime));			
			$EndTime 		=  date("g:i", strtotime($WorkingDaysData->EndTime));
			$EndTimeType  	=  date("a", strtotime($WorkingDaysData->EndTime));
			
			$StartTimeStr	=	"FromHour";
			$StartTimeStr2	=	"FromType";
			$EndTimeStr		=	"ToHour";
			$EndTimeStr2	=	"ToType";
			
			$final[strtolower(TicketBusinessHours::$CustomDays[$WorkingDaysData->Day])] = array(
				$StartTimeStr=>$startTime,
				$StartTimeStr2=>$startTimeType,
				$EndTimeStr=>$EndTime,
				$EndTimeStr2=>$EndTimeType
			);
		}		
		return $final;
	}	
}

