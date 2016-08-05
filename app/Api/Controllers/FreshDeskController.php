<?php
namespace Api\Controllers;

use Dingo\Api\Http\Request;
use Api\Model\DataTableSql;
use Api\Model\User;
use Api\Model\Account;
use App\AmazonS3;
use App\Http\Requests;
use Dingo\Api\Facade\API;
use Faker\Provider\Uuid;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Freshdesk;
/*use Freshdesk\Config\Connection;
use Freshdesk\Rest;
use Freshdesk\Ticket;
use Freshdesk\Model\Contact;
use Freshdesk\Model\Ticket as TicketM;
use Freshdesk\Tool\ModelGenerator;
*/

class FreshDeskController extends BaseController {
    
    public function __construct(Request $request)
    {
        $this->middleware('jwt.auth');
        Parent::__Construct($request);
    }
	
	function GetContacts(){
		$data 		= 	array("domain"=>"cdpk","email"=>"umer.ahmed@code-desk.com","password"=>"computer123","key"=>"se0nymUkCgk9eVlOOJN");
	  try {
			$obj 			= 	new FreshDesk($data);
			$GetContacts 	= 	$obj->GetContacts(); Log::info($GetContacts);
			return generateResponse('',false,false,$GetContacts);
		}catch (\Exception $ex){
			Log::info($ex);
			return $this->response->errorInternal($ex->getMessage());
		}       
    }
	function GetTickets(){
		$data 		= 	array("domain"=>"cdpk","email"=>"umer.ahmed@code-desk.com","password"=>"computer123","key"=>"se0nymUkCgk9eVlOOJN","per_page"=>5);
		try {
			$obj 			= 	new FreshDesk($data);
			$GetTickets 	= 	$obj->GetTickets(); 		
			return generateResponse('',false,false,$GetTickets);
		}catch (\Exception $ex){
			Log::info($ex);
			return $this->response->errorInternal($ex->getMessage());
		}
    }
}