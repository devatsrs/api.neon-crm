<?php

namespace Api\Controllers;

use Api\Model\TicketDashboardTimeline;
use Api\Model\Ticketfields;
use App\EmailClient;
use Dingo\Api\Http\Request;
use Api\Model\AccountBalance;
use Api\Model\AccountBalanceHistory;
use Api\Model\DataTableSql;
use Api\Model\User;
use Api\Model\Ticket;
use Api\Model\Company;
use Api\Model\TicketsTable;
use Api\Model\TicketGroups;
use Api\Model\TicketGroupAgents;
use Api\Model\TicketLog;
use App\Http\Requests;
use App\Imap;
use Dingo\Api\Facade\API;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Routing\UrlGenerator;

class TicketsGroupController extends BaseController
{

private $validlicense;	

	public function __construct(Request $request){ 
        $this->middleware('jwt.auth');
        Parent::__Construct($request);
    }
	 


    public function index() {          

		$data 			 		= 	array();
		$EscalationTimes_json 	= 	json_encode(TicketGroups::$EscalationTimes);
        return View::make('ticketgroups.groups', compact('data','EscalationTimes_json'));   
	  }		
	  
	  
	  
	   public function get($id){
        $post_data = Input::all();
        if ($id > 0) {           
            try {
                $GroupData = TicketGroups::findOrFail($id);
            } catch (\Exception $e) {
                Log::info($e);
                return generateResponse('Ticket Group not found.',true,true);
            }
            return generateResponse('success', false, false, $GroupData);
        } else {
            return generateResponse('Provide Valid Integer Value.', true, true);
        }
    }
	  
	  
	  public function getGroups(){ 
		
		$data 			= 	Input::all();  
		
		$rules['iDisplayStart'] ='required|Min:1';
        $rules['iDisplayLength']='required';
        $rules['iSortCol_0']='required';

        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            return generateResponse($validator->errors(),true);
        }
		try
		{
		   $CompanyID 				= 	User::get_companyID();       
		   $data 					= 	Input::all();
		   $data['iDisplayStart'] 	+=	1;
		   $userID					=	(isset($data['UsersID']) && !empty($data['UsersID']))?$data['UsersID']:0;
		   $search		 			=	$data['Search'];	   
		   $columns 	 			= 	array('GroupID','GroupName','GroupEmailAddress','TotalAgents','GroupAssignTime','AssignUser');
		   $sort_column 			= 	$columns[$data['iSortCol_0']];
			
			$query 	= 	"call prc_GetTicketGroups (".$CompanyID.",'".$search."',".( ceil($data['iDisplayStart']/$data['iDisplayLength']) )." ,".$data['iDisplayLength'].",'".$sort_column."','".$data['sSortDir_0']."'";  
	
			if(isset($data['Export']) && $data['Export'] == 1) {
				$result = DB::select($query . ',1)');
			}else{
				$query .=',0)';  
				$result =  DataTableSql::of($query)->make(); 
			} 
			return generateResponse('',false,false,$result);
		} catch (\Exception $e) {
            Log::info($e);
            return $this->response->errorInternal('Internal Server');
        }
	 }
	  
	  function Store(){
		  

		$data 			= 	Input::all();
        
        $rules = array(
            'GroupName' => 'required|min:2',
            'GroupAgent' => 'required',
            'GroupAssignEmail' => 'required',
			'GroupEmailServer' => 'required',
			'GroupEmailPassword' => 'required',
			'GroupReplyAddress' => 'email|required',		
			'GroupEmailAddress'	=> 'email|required|unique:tblTicketGroups,GroupEmailAddress',
        );

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
			 return generateResponse($validator->errors(),true);
        }
		
			$GroupData = array(
				"CompanyID"=>User::get_companyID(),
				"LanguageID"=>$data['groupLanguage'],
				"GroupName"=>$data['GroupName'],
				"GroupDescription"=>$data['GroupDescription'],
				"GroupBusinessHours"=>isset($data["GroupBusinessHours"])?$data["GroupBusinessHours"]:0,
				"GroupAssignTime"=>$data['GroupAssignTime'],
				"GroupAssignEmail"=>$data['GroupAssignEmail'],
				"GroupReplyAddress"=>$data['GroupReplyAddress'],				
				"GroupEmailServer"=>$data['GroupEmailServer'],
				"GroupEmailPassword"=>$data['GroupEmailPassword'],	
				"GroupEmailStatus" => 0,
				"created_at"=>date("Y-m-d H:i:s"),
				"created_by"=>User::get_user_full_name()
			);
			
			try{
 			    DB::beginTransaction();
				$GroupID = TicketGroups::insertGetId($GroupData);		
				if(is_array($data['GroupAgent'])){
					foreach($data['GroupAgent'] as $GroupAgents){
						$TicketGroupAgents =	array("GroupID"=>$GroupID,'UserID'=>$GroupAgents,"created_at"=>date("Y-m-d H:i:s"),"created_by"=>User::get_user_full_name());   
						TicketGroupAgents::Insert($TicketGroupAgents);						
					}
				}	
					
				$this->SendEmailActivationEmail($data['GroupEmailAddress'],$GroupID,$data['activate']);
				 DB::commit();	
				return generateResponse('Group Successfully Created');
      		 }catch (Exception $ex){ 	
			      DB::rollback();
				 return generateResponse($ex->getMessage(),true,true);
       		 }    
	  }
	  
	  function Update($id){
		  

		$data 			= 	Input::all();
		$TicketGroup	= 	TicketGroups::find($id);
		$TicketGroupold	= 	TicketGroups::find($id);
		$data['GroupEmailIsSSL'] = 	isset($data['GroupEmailIsSSL']) ? 1 : 0;
	    $rules = array(
            'GroupName' => 'required|min:2',
            'GroupAgent' => 'required',
            'GroupEmailAddress'	=> 'email|required|unique:tblTicketGroups,GroupEmailAddress,'.$id.',GroupID,CompanyID,'.User::get_companyID(),
            'GroupAssignEmail' => 'required',
			'GroupEmailServer' => 'required',
			'GroupEmailPort' => 'required',
			'GroupEmailPassword' => 'required',
			'GroupReplyAddress' => 'email|required',	
        );

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
			return generateResponse($validator->errors(),true);
        }
			try{
				 DB::beginTransaction();
				if(isset($TicketGroup->GroupID)){
					
					$grpagents 					= 	$data['GroupAgent'];
					$GroupEmailAddress  		= 	$data['GroupEmailAddress'];		
					$activate					=	$data['activate'];								
					$data["GroupBusinessHours"] =	isset($data["GroupBusinessHours"])?$data["GroupBusinessHours"]:0;
					$data["LanguageID"]			=	$data['groupLanguage'];
					//$data 					= 	cleanarray($data,['GroupAgent','_wysihtml5_mode','GroupEmailAddress','activate']);	
					$data 						= 	cleanarray($data,['GroupAgent','_wysihtml5_mode','activate', 'groupLanguage']);
							 			
					$TicketGroup->update($data);  	 //update groups
					TicketGroupAgents::where(["GroupID" => $TicketGroup->GroupID])->delete(); //delete old group agents
					
					if(is_array($grpagents)){
						foreach($grpagents as $GroupAgents){	 //insert new agents						  
							$TicketGroupAgents =	array("GroupID"=>$TicketGroup->GroupID,'UserID'=>$GroupAgents,"updated_at"=>date("Y-m-d H:i:s"),"updated_by"=>User::get_user_full_name());   
							TicketGroupAgents::Insert($TicketGroupAgents);
						}
					}
					
					if($TicketGroupold->GroupEmailAddress!=$GroupEmailAddress){						 		 		
						$this->SendEmailActivationEmailUpdate($GroupEmailAddress,$id,$activate);
					}
					DB::commit();	
					return generateResponse('Group Successfully Updated');
				}
      		 }catch (Exception $ex){ 	
				 DB::rollback();
				 return generateResponse($ex->getMessage(),true,true);
       		 } 
	  }
	  
	  function SendEmailActivationEmail($email,$groupID,$url){
		  
		  if(!empty($email))
		  {     
				$remember_token				 = 		str_random(32);
				$user_reset_link 			 = 		$url."?remember_token=".$remember_token;
				$data 						 = 		array();
				$data['companyID'] 			 = 		User::get_companyID();
				$CompanyName 				 =  	Company::getName($data['companyID']);
				$data['EmailTo'] 			 = 		trim($email);
				$data['CompanyName'] 		 = 		$CompanyName;
				$data['Subject'] 			 = 		'Activate support email address';
				$data['user_reset_link'] 	 = 		$user_reset_link;
				$result 					 = 		sendMail('auth.email_verify',$data);
				
				if ($result['status'] == 1) {
					$GroupEmaildata = array(
						"GroupEmailAddress"=>$email,
						"remember_token"=>$remember_token
						);
						
					 TicketGroups::where(['GroupID'=>$groupID])->update($GroupEmaildata);								 					
				}				
		  }
	  	
	  }
	  
	  function SendEmailActivationEmailUpdate($email,$groupID,$url){			
		  if(!empty($email))
	 	  {   
			$remember_token				 = 		str_random(32); //add new
			$user_reset_link 			 = 		$url."?remember_token=".$remember_token;
			$data 						 = 		array();
			$data['companyID'] 			 = 		User::get_companyID();
			$CompanyName 				 =  	Company::getName($data['companyID']);
			$data['EmailTo'] 			 = 		trim($email);
			$data['CompanyName'] 		 = 		$CompanyName;
			$data['Subject'] 			 = 		'Activate support email address';
			$data['user_reset_link'] 	 = 		$user_reset_link;
			$result 					 = 		sendMail('auth.email_verify',$data);
				
			if ($result['status'] == 1)
			{
				$GroupEmaildata = array(
					"GroupEmailAddress"=>$email,
					"GroupEmailStatus"=>0,
					"remember_token"=>$remember_token,
					"updated_at"=>date("Y-m-d H:i:s"),
					"updated_by"=>User::get_user_full_name()
					);
					
				 TicketGroups::where(['GroupID'=>$groupID])->update($GroupEmaildata);	
			 }				
	  	  }
	  }
	  
	  function Activate_support_email(){
	 	 $data = Input::all();
        //if any open reset password page direct he will redirect login page
			if(isset($data['remember_token']) && $data['remember_token'] != '')
			{
				$remember_token  = 	$data['remember_token'];
				$user 			 = 	TicketGroups::get_support_email_by_remember_token($remember_token);
				
				if (empty($user)) {
					$data['message']  = "Invalid Token";
					$data['status']  =  "failed";
				} else {
					TicketGroups::where(["GroupID"=>$user->GroupID])->update(array("remember_token"=>'',"GroupEmailStatus"=>1));				
					$data['message']  		=  "Email successfully activated";
					$data['status'] 		=  "success";				
				}  
				return View::make('ticketgroups.activate_status',compact('data'));     					
			}else{
				return Redirect::to('/');
			}
	  }
	  
	 public function Delete($id)
     {
		$data = Input::all();
        if( intval($id) > 0)
		{
               try{

				   $CompanyID 				= 	User::get_companyID();

				   TicketsTable::GroupDelete($CompanyID,$id);

				   return generateResponse('Ticket Group Successfully Deleted');
                }catch (Exception $ex){
					return generateResponse('Problem Deleting. Exception:'.$ex->getMessage(), true, true);
                }
            
        }
		else {
            return generateResponse('Provide Valid Integer Value.', true, true);
        }
    }
	
	
	function send_activation_single($id)
	{
		$data = Input::all(); 
	    try
		{
			if($id)
			{
			   $email_data = 	TicketGroups::find($id);
			  
			  if(count($email_data)>0 && $email_data->GroupEmailStatus==0)
			  {
					$remember_token				 = 		str_random(32); //add new
				    $site_url 					 = 		\Api\Model\CompanyConfiguration::get("WEB_URL").'/activate_support_email';
					$user_reset_link 			 = 		$site_url."?remember_token=".$remember_token;
					$data 						 = 		array();
					$data['companyID'] 			 = 		User::get_companyID();
					$CompanyName 				 =  	Company::getName($data['companyID']);
					$data['EmailTo'] 			 = 		trim($email_data->GroupEmailAddress);
					$data['CompanyName'] 		 = 		$CompanyName;
					$data['Subject'] 			 = 		'Activate support email address';
					$data['user_reset_link'] 	 = 		$user_reset_link;
					$result 					 = 		sendMail('auth.email_verify',$data);
					
					if ($result['status'] == 1)
					{
							$GroupEmaildata = array(
								"remember_token"=>$remember_token,
								"updated_at"=>date("Y-m-d H:i:s"),
								"updated_by"=>User::get_user_full_name()
							);

						 $email_data->update($GroupEmaildata);
						 return generateResponse('Activation email successfully sent');
					}
			  }else{
				return generateResponse('No email found or already activated', true, true);
			  }			  
			}
		 }catch (Exception $ex){
			  return generateResponse('Problem occurred. Exception:'.$ex->getMessage(), true, true);
         }
	}
	
	function get_group_agents($id){
		try
		{
			$Groupagents = TicketGroupAgents::get_group_agents($id);
			//echo "<pre>"; print_r($Groupagents);	echo "</pre>";
			return generateResponse('',false,false,$Groupagents);
		
		  }catch (Exception $ex){
				 return generateResponse('Problem occurred. Exception:'.$ex->getMessage(), true, true);
         }
	}
	
	function get_group_agents_ids($id){
		try
		{
			$Groupagents    =   array();
			if($id)
			{
				$Groupagentsdb	=	TicketGroupAgents::where(["GroupID"=>$id])->get(); 
			}
			else
			{
				$Groupagentsdb	=	TicketGroupAgents::get(); 
			}
			
			foreach($Groupagentsdb as $Groupagentsdata){
				$Groupagents[] = $Groupagentsdata->UserID;			
			}
			//echo "<pre>"; print_r($Groupagents);	echo "</pre>";
			return generateResponse('',false,false,$Groupagents);
		  }catch (Exception $ex){
			 return generateResponse($ex->getMessage(),true,true);
         }
	}
	
	function validatesmtp(){
		$data = Input::all();
		$data['GroupEmailIsSSL'] = 	isset($data['GroupEmailIsSSL']) ? 1 : 0;
		  $rules = array(
            'GroupEmailServer' => 'required',
            'GroupEmailPort' => 'required',
			'GroupEmailPassword' => 'required',
			'GroupEmailAddress' => 'required',
        );

        $validator = Validator::make($data, $rules);		
		
		if ($validator->fails()) {
			 return generateResponse($validator->errors(),true);
        }
		try
		{
			$result =  new EmailClient(["host"=>$data['GroupEmailServer'], "port"=>$data['GroupEmailPort'], "IsSSL"=>$data['GroupEmailIsSSL'], "username"=>$data['GroupEmailAddress'], "password"=>$data['GroupEmailPassword'] ]);
			if($result->isConnected()){
			 return generateResponse('Validated.');
			}else{
			 return generateResponse("could not connect",true,true);
			}
		}catch (Exception $ex){
			 return generateResponse($ex->getMessage(),true,true);
         }	
	}	
	
}