<?php
namespace Api\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Api\Model\CompanySetting;

class IntegrationConfiguration extends Model
{
    protected $guarded 		= 	array("IntegrationConfigurationID");
    protected $table 		= 	'tblIntegrationConfiguration';
    protected $primaryKey 	= 	"IntegrationConfigurationID";
	
    public static $rules = array(
    );
	
	
   
}
