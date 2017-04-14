<?php
namespace Api\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TicketBusinessHolidays extends Model {
	
    protected $table 		= 	"TblTicketBusinessHolidays";
    protected $primaryKey 	= 	"HolidayID";
	protected $guarded 		=	 array("HolidayID");	
}

