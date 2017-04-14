<?php
namespace Api\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TicketBusinessHours extends Model {

    protected $table 		= 	"TblTicketBusinessHours";
    protected $primaryKey 	= 	"ID";
	protected $guarded 		=	 array("ID");

	static $TicketHours	=	array(
	"1:00"=>"1:00",
	"1:30"=>"1:30",
	"2:00"=>"2:00",
	"2:30"=>"2:30",
	"3:00"=>"3:00",
	"3:30"=>"3:30",
	"4:00"=>"4:00",
	"4:30"=>"4:30",
	"5:00"=>"5:00",
	"5:30"=>"5:30",
	"6:00"=>"6:00",
	"6:30"=>"6:30",
	"7:00"=>"7:00",
	"7:30"=>"7:30",
	"8:00"=>"8:00",
	"8:30"=>"8:30",
	"09:00"=>"09:00",
	"09:30"=>"09:30",
	"10:00"=>"10:00",
	"11:00"=>"11:00",
	"11:30"=>"11:30",
	"12:00"=>"12:00",
	"12:30"=>"12:30");
	
	static $DefaultHourFrom      			=   "8:00";
	static $DefaultHourTo        			=   "5:00";	
	static $TicketHoursType		 			=	array("am"=>"am","pm"=>"pm");	
	static $DefaultTicketHoursTypeFrom      =   "am";
	static $DefaultTicketHoursTypeTo      	=   "pm";
	static $HelpdeskHours247				=	1;
	static $HelpdeskHoursCustom				=	2;
	static $CustomDays						=	array(1=>"Monday",2=>"Tuesday",3=>"Wednesday",4=>"Thursday",5=>"Friday",6=>"Saturday",7=>"Sunday");	
	static $HolidaysMonths  		    	=   array(1=>"Jan",2=>"Feb",3=>"Mar",4=>"Apr",5=>"May",6=>"Jun",7=>"Jul",8=>"Aug",9=>"Sep",10=>"Oct",11=>"Nov",12=>"Dec");
	static $HolidaysDays  		    		=   array(1=>1,2=>2,3=>3,4=>4,5=>5,6=>6,7=>7,8=>8,9=>9,10=>10,11=>11,12=>12,13=>13,14=>14,15=>15,16=>16,17=>17,18=>18,19=>19,20=>20,21=>21,22=>22,23=>23,24=>24,25=>25,26=>26,27=>27,28=>28,29=>29,30=>30,31=>31);
}

