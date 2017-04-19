<?php
namespace Api\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TicketSla extends Model {

    protected $table 		= 	"tblTicketSla";
    protected $primaryKey 	= 	"TicketSlaID";
	protected $guarded 		=	 array("TicketSlaID");	
	
	
	static $TargetDefault	=   'Minute';
	static $SlaTargetTime	=	array(
		"Minute"=>"Mins",
		"Hour"=>"Hrs",
		"Day"=>"Days",
		"Month"=>"Mos",
	);
	
	static $SlaTargetTimeValue    =   "15";
	const  BusinessHours		  =		1;
	const  CalendarHours		  =		0;
	
	
	static $SlaOperationalHours	=	array(
		self::BusinessHours => "Business Hours",
		self::CalendarHours => "Calendar Hours",	
	);
	
	static $EscalateTime = array(
		"immediately"=>"Immediately",
		'30 Minute'=>"After 30 Minutes",
		'1 Hour'=>"After 1 Hour",
		'2 Hour'=>"After 2 Hours",
		'4 Hour'=>"After 4 Hours",		
		'8 Hour'=>"After 8 Hours",
		'12 Hour'=>"After 12 Hours",
		'1 Day'=>"After 1 Day",
		'2 Day'=>"After 2 Days",
		'3 Day'=>"After 3 Days",
		'1 Week'=>"After 1 Week",
		'2 Week'=>"After 2 Weeks",
		'1 Month'=>"After 1 Month",		
	);
	
	  /**
     * Assign SLA policy to ticket
     */
    public static function assignSlaToTicket($CompanyID,$TicketID){

        $query 				=      	"call prc_AssignSlaToTicket (".$CompanyID.",".$TicketID.")";
        DB::select($query);

    }
}
