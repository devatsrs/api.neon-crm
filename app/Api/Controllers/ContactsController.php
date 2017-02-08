<?php

namespace Api\Controllers;

use Dingo\Api\Http\Request;
use Api\Model\AccountBalance;
use Api\Model\AccountBalanceHistory;
use Api\Model\DataTableSql;
use Api\Model\User;
use Api\Model\Account;
use Api\Model\ContactNote;
use Api\Model\Invoice;
use Api\Model\Ticket;
use Api\Model\Company;
use Api\Model\CompanySetting;
use Api\Model\CompanyConfiguration;
use Api\Model\AccountEmailLog;
use Api\Model\TicketsTable;
use App\Http\Requests;
use Dingo\Api\Facade\API;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Api\Model\Tags;
use Api\Model\PaymentGateway;
use Api\Model\AccountPaymentProfile;
use App\Freshdesk;
use App\Imap;

class ContactsController extends BaseController
{
	protected $tokenClass;
	
    public function __construct(Request $request){ 
        $this->middleware('jwt.auth');
        Parent::__Construct($request);
    }

    public function add_note(){

        $data 	= 	Input::all();

	   $rules = array(
            'CompanyID' => 'required',
            'ContactID' => 'required',
            'Note' => 'required',
        );

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            return generateResponse($validator->errors(),true);
        }

		try{
			$data = cleanarray($data,[]);
            $result = ContactNote::create($data);
            return generateResponse('',false,false,$result);
        }catch (\Exception $ex){
            Log::info($ex);
            return $this->response->errorInternal($ex->getMessage());
        }
    }

    public function GetNote()
    {
        $data = Input::all();

        $rules['NoteID'] = 'required';
        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            return generateResponse($validator->errors(),true);
        }
        try {
            $Note = ContactNote::find($data['NoteID']);
        } catch (\Exception $e) {
            Log::info($e);
            return $this->response->errorInternal($e->getMessage());
        }
        return generateResponse('',false,false,$Note);
    }

	 public function UpdateNote(){

       $data 	= 	Input::all();

	   $rules = array(
            'NoteID' => 'required',
            'Note' => 'required',
        );

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            return generateResponse($validator->errors(),true);
        }

		try{
			$data = cleanarray($data,[]);
			$result = ContactNote::find($data['NoteID'])->update($data);
			$result = ContactNote::find($data['NoteID']);

            return generateResponse('',false,false,$result);
        }catch (\Exception $ex){
            Log::info($ex);
            return $this->response->errorInternal($ex->getMessage());
        }
    }
	
	 public function DeleteNote(){
        $data = Input::all();

        $rules['NoteID'] = 'required';
        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            return generateResponse($validator->errors(),true);
        }

        try{
            ContactNote::where(['NoteID'=>$data['NoteID']])->delete();
        }catch (\Exception $ex){
            Log::info($ex);
            return $this->response->errorInternal($ex->getMessage());
        }
        return generateResponse('successfull');

    }


    public function GetTimeLine()
    {
        $data                       =   Input::all();  
        $companyID                  =   User::get_companyID();
        $rules['iDisplayStart']     =   'required|numeric|Min:0';
        $rules['iDisplayLength']    =   'required|numeric';
        $rules['ContactID']         =   'required|numeric';
		
        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            return generateResponse($validator->errors(),true);
        }			
		
		$queryTicketType	= 0;
		$SystemTicket  = TicketsTable::CheckTicketLicense();
		if($SystemTicket){
			$queryTicketType	= TicketsTable::$SystemTicket;
		}
			
        try { 
			if(!$queryTicketType){ //check system ticket enable . if not then check freshdesk tickets
				if($data['iDisplayStart']==0) {
					if(\App\SiteIntegration::CheckIntegrationConfiguration(false,\App\SiteIntegration::$freshdeskSlug)){
						$queryTicketType	= TicketsTable::$FreshdeskTicket;
					 $freshsdesk = 	$this->FreshSDeskGetTickets($data['AccountID'],$data['GUID']); 
						if($freshsdesk){
							//return generateResponse(array("freshsdesk"=>array(0=>$freshsdesk['errors'][0]->message)),true);
						}
					}
				}
			}
			
			
            $columns =  ['Timeline_type','ActivityTitle','ActivityDescription','ActivityDate','ActivityType','ActivityID','Emailfrom','EmailTo','EmailSubject','EmailMessage','AccountEmailLogID','NoteID','Note','CreatedBy','created_at','updated_at'];
            $query = "call prc_getContactTimeLine(" . $data['ContactID'] . "," . $companyID . ",".$queryTicketType.",'".$data['GUID']."'," . $data['iDisplayStart'] . "," . $data['iDisplayLength'] . ")";  
            $result_array = DB::select($query); Log::info($query);
            return generateResponse('',false,false,$result_array);
       }
        catch (\Exception $ex){
            Log::info($ex);
            return $this->response->errorInternal($ex->getMessage());
        }
    }
	
	function FreshSDeskGetTickets($AccountID,$GUID){ 
		//date_default_timezone_set("Europe/London");
		Ticket::where(['AccountID'=>$AccountID,"GUID"=>$GUID])->delete(); //delete old tickets
	    $companyID 		=	User::get_companyID();
        /*$AccountEmails  =	Account::where("AccountID",$AccountID)->select(['Email','BillingEmail'])->first();
        $AccountEmails  = 	json_decode(json_encode($AccountEmails),true);
        $emails			=	array_unique($AccountEmails);*/
 
		
        $email_array			 = 	array();
        $billingemail_array 	 = 	array();
        $allemail 				 =  array();
        $AccountEmails  		 =	Account::where("AccountID",$AccountID)->select(['Email'])->first();
		
        if(count($AccountEmails)>0)
		{
            $email_array = explode(',',$AccountEmails['Email']);
        }
		
        $AccountEmails1  		=	Account::where("AccountID",$AccountID)->select(['BillingEmail'])->first();
		
        if(count($AccountEmails1)>0)
		{
            $billingemail_array = explode(',', $AccountEmails1['BillingEmail']);
        }
		
        $allemail 				= 	array_merge($email_array,$billingemail_array);
        $emails					=	array_filter(array_unique($allemail));
		$TicketsIDs				=	array();  		
		$FreshDeskObj 			=  	new \App\SiteIntegration();
		$FreshDeskObj->SetSupportSettings();	
			
		if(count($emails)>0 && $FreshDeskObj->CheckSupportSettings())
		{ 
			foreach($emails as $UsersEmails)
			{				
				$GetTickets 	= 		$FreshDeskObj->GetSupportTickets(array("email"=>trim($UsersEmails),"include"=>"requester"));				
				
				if(isset($GetTickets['StatusCode']) && $GetTickets['StatusCode'] == 200 && count($GetTickets['data'])>0)
				{   
					foreach($GetTickets['data'] as $GetTickets_data)
					{   
						if(in_array($GetTickets_data->id,$TicketsIDs)){continue;}else{$TicketsIDs[] = $GetTickets_data->id;} //ticket duplication						
						$TicketData['CompanyID']		=	$companyID;
						$TicketData['AccountID'] 		=   $AccountID;
						$TicketData['TicketID']			=   $GetTickets_data->id;	
						$TicketData['Subject']			=	$GetTickets_data->subject;
						$TicketData['Description']		=	$GetTickets_data->description;
						//$TicketData['Description']		=	$GetTickets_data->description_text;
						$TicketData['Priority']			=	$FreshDeskObj->SupportSetPriority($GetTickets_data->priority);
						$TicketData['Status']			=	$FreshDeskObj->SupportSetStatus($GetTickets_data->status);
						$TicketData['Type']				=	$GetTickets_data->type;				
						$TicketData['Group']			=	$FreshDeskObj->SupportSetGroup($GetTickets_data->group_id);
						$TicketData['RequestEmail']		=	$GetTickets_data->requester->email;				
						$TicketData['ApiCreatedDate']	=   date("Y-m-d H:i:s",strtotime($GetTickets_data->created_at));
						$TicketData['ApiUpdateDate']	=   date("Y-m-d H:i:s",strtotime($GetTickets_data->updated_at));	
						$TicketData['created_by']  		= 	User::get_user_full_name();
						$TicketData['GUID']  			= 	$GUID; 
						if(!empty($GetTickets_data->to_emails) && $GetTickets_data->to_emails!='null'){
							if(is_array($GetTickets_data->to_emails)){
								$TicketData['to_emails']		=	implode(",",$GetTickets_data->to_emails);	
							}
							else{
								$TicketData['to_emails']		=	$GetTickets_data->to_emails;	
							}
						}
						$result 						= 	Ticket::create($TicketData);		
						unset($TicketData);
					}	
				}
				else
				{
					//return $GetTickets;	
					if(isset($GetTickets['StatusCode']) && $GetTickets['StatusCode']!='200' && $GetTickets['StatusCode']!='400'){
						Log::info("freshdesk StatusCode ".print_r($GetTickets,true));
						return $GetTickets;							
					}
				} 	    
			}		
		}
	}
	
	function GetConversations(){
		$data           	=   	Input::all();  
	
		if(isset($data['conversations_type'])){
			if($data['conversations_type']=='mail')
			{
				return $this->GetMailConversations();	
			}
			else if($data['conversations_type']=='ticket')
			{
			    return 	$this->GetTicketConversations();
			}
		}
	}
	
	function GetMailConversations(){
		$companyID 			=	 	User::get_companyID();
		$data           	=   	Input::all();  		
		$Emails				= 		AccountEmailLog::where(['EmailParent'=>$data['id'],'CompanyID'=>$companyID])->get();
		if($Emails)
		{
			return generateResponse('',false,false,$Emails);
		}else
		{
			return generateResponse('No Record Found.',false,false);
		}		
	}
	
	
	function GetTicketConversations(){
		$companyID 			=	 	User::get_companyID();
		$data           	=   	Input::all();  		
		$FreshDeskObj 		= 		new \App\SiteIntegration();
		$FreshDeskObj->SetSupportSettings();		
		
		$GetTicketsCon 		= 		$FreshDeskObj->GetSupportTicketConversations($data['id']);  
		if($GetTicketsCon['StatusCode'] == 200 && count($GetTicketsCon['data'])>0){ 
			return generateResponse('',false,false,$GetTicketsCon['data']);
		}
		else{
			return generateResponse('No Record Found.',false,false);
		} 	
	}

	
	public function GetAccountLeadByContactNumber()
	{
		$data 				= 		Input::all();
		
		$rules = array(
            'contactNo' =>      'required|numeric',
        );

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            return generateResponse($validator->errors(),true);
        }		
		
	    try{
		    $account = Account::where(['phone'=>$data['contactNo']])->get();
        }catch (\Exception $ex){
            Log::info($ex);
            return $this->response->errorInternal($ex->getMessage());
        }
        return generateResponse('success',false,false,$account);
	}
}