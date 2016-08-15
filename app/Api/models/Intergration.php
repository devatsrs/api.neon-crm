<?php

namespace Api\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Api\Model\CompanySetting;

class Integration extends Model
{
    protected $guarded 		= 	array("IntegrationID");
    protected $table 		= 	'tblIntegration';
    protected $primaryKey 	= 	"IntegrationID";
	
    public static $rules = array(
    );
	
	
   
}
