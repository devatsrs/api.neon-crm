<?php
/**
 * Created by PhpStorm.
 * User: deven
 * Date: 27/07/2016
 * Time: 5:41 PM
 */

namespace App;
use Api\Model\CompanyConfiguration;
use Illuminate\Support\Facades\Log;
use \App\SiteIntegration;
/** Base Calendar API class
 * Class CalendarAPI
 * @package App
 */
class CalendarAPI
{

    protected $request ;

    protected $has_outlook = false;

    //for outlook api
    protected $server ;
    protected $email ;
    protected $password ;

    public function  __construct(){

        if($this->is_outlook()){

            $this->request  = new OutlookCalendarAPI($this->server,$this->email,$this->password);
        }
    }

    public function create_event($options = array())
    {

        Log::info("create_event . " . $this->has_outlook);

        if($this->has_outlook){

            Log::info("create_event again . " . $this->has_outlook);

            return $this->request->create_event($options);
        }

        return false;

    }

    public function update_event($options = array())
    {

        if($this->has_outlook) {
            return $this->request->update_event($options);
        }

        return false;

    }

    public function delete_event($options = array())
    {

        if($this->has_outlook) {
            return $this->request->delete_event($options);
        }

        return false;

    }

    public function is_outlook() {

       /* $outlook_key = "OUTLOOKCALENDAR_API";
        $is_outlook = CompanyConfiguration::get($outlook_key);*/
		
		$OutlookData		=	SiteIntegration::CheckIntegrationConfiguration(true,SiteIntegration::$outlookcalenarSlug);
	
        Log::info("OUTLOOKCALENDAR_API " . print_r($OutlookData,true));

        if( !empty($OutlookData) ) {

            $this->server 		= 	$OutlookData->OutlookCalendarServer;
            $this->email  		= 	$OutlookData->OutlookCalendarEmail;
            $this->password 	= 	$OutlookData->OutlookCalendarPassword;

            if(!empty($this->server) && !empty($this->email) && !empty($this->password) ) {

                $this->has_outlook = true;
                return true;
            }
        }

    }

}