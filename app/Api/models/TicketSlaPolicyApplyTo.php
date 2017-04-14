<?php
namespace Api\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TicketSlaPolicyApplyTo extends Model {

    protected $table 		= 	"tblTicketSlaPolicyApplyTo";
    protected $primaryKey 	= 	"ApplyToID";
	protected $guarded 		=	 array("ApplyToID");	
}