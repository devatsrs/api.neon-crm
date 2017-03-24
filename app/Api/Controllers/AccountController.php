<?php

namespace Api\Controllers;

use Dingo\Api\Http\Request;
use Api\Model\AccountBalance;
use Api\Model\AccountBalanceHistory;
use Api\Model\DataTableSql;
use Api\Model\User;
use Api\Model\Account;
use Api\Model\Note;
use Api\Model\Invoice;
use Api\Model\ContactNote;
use Api\Model\Ticket;
use Api\Model\Company;
use Api\Model\CompanySetting;
use Api\Model\CompanyConfiguration;
use Api\Model\AccountEmailLog;
use Api\Model\TicketsTable;
use Api\Model\Contact;
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

class AccountController extends BaseController
{
	protected $tokenClass;
	
    public function __construct(Request $request){ 
        $this->middleware('jwt.auth');
        Parent::__Construct($request);
    }

    /**
     * Show account balance
     *
     * Get a JSON representation of all the dogs
     *  get/post variables
     * @Get('/')
     */
    public function GetCredit()
    {
        $post_data = Input::all();
        $rules['account_id'] = 'required';
        $validator = Validator::make($post_data, $rules);
        if ($validator->fails()) {
            return generateResponse($validator->errors(),true);
        }
        $AccountBalance = AccountBalance::where('AccountID', $post_data['account_id'])->first();
        return generateResponse('',false,false,array('UnbilledAmount' =>$AccountBalance->UnbilledAmount));
    }

    public function UpdateCredit()
    {
        $post_data = Input::all();

        $rules['account_id'] = 'required';
        $rules['credit'] = 'required';
        $rules['action'] = 'required';
        $validator = Validator::make($post_data, $rules);
        if ($validator->fails()) {
            return generateResponse($validator->errors(),true);
        }
        if (!in_array($post_data['action'], array('add', 'sub'))) {
            return $this->response->errorBadRequest('action is not valid');
        }
        try {
            if ($post_data['action'] == 'add') {
                AccountBalance::addCredit($post_data['account_id'], $post_data['credit']);
            } elseif ($post_data['action'] == 'sub') {
                AccountBalance::subCredit($post_data['account_id'], $post_data['credit']);
            }

        } catch (\Exception $e) {
            Log::info($e);
            return $this->response->errorInternal($e->getMessage());
        }
        return generateResponse('credit added successfully');
    }

    public function DeleteCredit()
    {

        return generateResponse('success');
    }

    public function GetTempCredit()
    {
        return generateResponse('success');
    }

    public function UpdateTempCredit()
    {
        $post_data = Input::all();

        $rules['account_id'] = 'required';
        $rules['credit'] = 'required';
        $rules['action'] = 'required';
        $rules['date'] = 'required';

        $validator = Validator::make($post_data, $rules);
        if ($validator->fails()) {
            return generateResponse($validator->errors(),true);
        }
        if (!in_array($post_data['action'], array('add', 'sub'))) {
            return $this->response->errorBadRequest('provide valid action');
        }
        try {
            if ($post_data['action'] == 'add') {
                AccountBalance::addTempCredit($post_data['account_id'], $post_data['credit'], $post_data['date']);
            } elseif ($post_data['action'] == 'sub') {
                AccountBalance::subTempCredit($post_data['account_id'], $post_data['credit'], $post_data['date']);
            }
        } catch (\Exception $e) {
            Log::info($e);
            return $this->response->errorInternal($e->getMessage());
        }
        return generateResponse('Temporary credit added successfully');

    }
    public function DeleteTempCredit()
    {
        return generateResponse('success');
    }

    public function GetAccountThreshold()
    {
        $post_data = Input::all();

        $rules['account_id'] = 'required';
        $validator = Validator::make($post_data, $rules);
        if ($validator->fails()) {
            return generateResponse($validator->errors(),true);
        }
        $BalanceThreshold = 0;
        try {
            $BalanceThreshold = AccountBalance::getThreshold($post_data['account_id']);
        } catch (\Exception $e) {
            Log::info($e);
            return $this->response->errorInternal($e->getMessage());
        }
        return generateResponse('success',false,false,array('BalanceThreshold' =>$BalanceThreshold));

    }

    public function UpdateAccountThreshold()
    {
        $post_data = Input::all();

        $rules['account_id'] = 'required';
        $rules['balance_threshold'] = 'required';
        $validator = Validator::make($post_data, $rules);
        if ($validator->fails()) {
            return generateResponse($validator->errors(),true);
        }
        try {
            AccountBalance::setThreshold($post_data['account_id'], $post_data['balance_threshold']);
        } catch (\Exception $e) {
            Log::info($e);
            return $this->response->errorInternal($e->getMessage());
        }
        return generateResponse('Balance Warning Threshold updated successfully');

    }
    public function DeleteAccountThreshold()
    {
        return generateResponse('success');
    }
    public function GetAccount($id){
        try{
            $account = Account::find($id);
        }catch (\Exception $ex){
            Log::info($ex);
            return $this->response->errorInternal($ex->getMessage());
        }
        return generateResponse('success',false,false,$account);
    }

	 public function add_note(){

        $data 	= 	Input::all();

	   $rules = array(
            'CompanyID' => 'required',
            'AccountID' => 'required',
            'Note' => 'required',
        );

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            return generateResponse($validator->errors(),true);
        }

		try{
			$data = cleanarray($data,[]);
            $result = Note::create($data);
            return generateResponse('',false,false,$result);
        }catch (\Exception $ex){
            Log::info($ex);
            return $this->response->errorInternal($ex->getMessage());
        }
    }

    public function GetNote()
    {
        $data = Input::all();
		Log::info(print_r($data,true));
        $rules['NoteID'] = 'required';
        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            return generateResponse($validator->errors(),true);
        }
        try {
			if(isset($data['note_type']) && $data['note_type'] == 'ContactNote'){
				$Note = ContactNote::find($data['NoteID']); Log::info("ContactNote");
			}else{
            	$Note = Note::find($data['NoteID']); Log::info("AccountNote");
			} Log::info(print_r($Note,true));
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
			 $NoteType = $data['NoteType'];
			 $data = cleanarray($data,['NoteType']);
			 if(isset($NoteType) && $NoteType == 'ContactNote'){
				ContactNote::find($data['NoteID'])->update($data);
				$result = ContactNote::find($data['NoteID']);
			}else{
				Note::find($data['NoteID'])->update($data);
				$result = Note::find($data['NoteID']);
			} 
			
			//$result = Note::find($data['NoteID'])->update($data);
			//$result = Note::find($data['NoteID']);

            return generateResponse('',false,false,$result);
        }catch (\Exception $ex){
            Log::info($ex);
            return $this->response->errorInternal($ex->getMessage());
        }
    }

    public function GetTimeLine()
    {
        $data                       =   Input::all();  
        $companyID                  =   User::get_companyID();
        $rules['iDisplayStart']     =   'required|numeric|Min:0';
        $rules['iDisplayLength']    =   'required|numeric';
        $rules['AccountID']         =   'required|numeric';
		
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
            $query = "call prc_getAccountTimeLine(" . $data['AccountID'] . "," . $companyID . ",".$queryTicketType.",'".$data['GUID']."','".date('Y-m-d H:i:00')."'," . $data['iDisplayStart'] . "," . $data['iDisplayLength'] . ")";   Log::info($query);
            $result_array = DB::select($query); 
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
		$Contacts_Email_array	 =	array();
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
		
		$AccountsContacts  	=DB::table('tblContact')->select(DB::raw("group_concat(DISTINCT Email separator ',') as ContactsEmails"))->where(array("AccountID"=>$AccountID))->pluck('ContactsEmails');
	
		if(strlen($AccountsContacts)>0)
		{
            $Contacts_Email_array = explode(',', $AccountsContacts);
        }
		
        $allemail 				= 	array_merge($email_array,$billingemail_array,$Contacts_Email_array);
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
		
		$queryTicketType	= 0;
		$SystemTicket  = TicketsTable::CheckTicketLicense();
		if($SystemTicket){
			$queryTicketType	= TicketsTable::$SystemTicket;
		}
			
		if(!$queryTicketType){ //fresh desk ticket
			$FreshDeskObj 		= 		new \App\SiteIntegration();
			$FreshDeskObj->SetSupportSettings();		
			
			$GetTicketsCon 		= 		$FreshDeskObj->GetSupportTicketConversations($data['id']);  
			if($GetTicketsCon['StatusCode'] == 200 && count($GetTicketsCon['data'])>0){ 
				return generateResponse('',false,false,$GetTicketsCon['data']);
			}
			else
			{
				return generateResponse('No Record Found.',false,false);
			} 	
		}else{ //system ticket
			$ticket = TicketsTable::find($data['id']);
			Log::info("Ticketid:".$data['id']);
			Log::info("AccountEmailLogID:".$ticket->AccountEmailLogID);
			
			
			$GetTicketsCon = AccountEmailLog::where(['EmailParent'=>$ticket->AccountEmailLogID,'CompanyID'=>$companyID])->select([DB::raw("Message AS body_text"), "created_at"])->orderBy('created_at', 'asc')->get();
			Log::info(print_r($GetTicketsCon,true));	
			if(count($ticket)>0 && $ticket->AccountEmailLogID>0 && count($GetTicketsCon)>0){
				return generateResponse('',false,false,$GetTicketsCon);
			}
			else
			{
				return generateResponse('No Record Found.',false,false);
			}
		}
		
	}

    public function DeleteNote(){
        $data = Input::all();
 		Log::info(print_r($data,true));
        $rules['NoteID'] = 'required';
        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            return generateResponse($validator->errors(),true);
        }

        try{
			 if(isset($data['NoteType']) && $data['NoteType'] == 'ContactNote'){
				 ContactNote::where(['NoteID'=>$data['NoteID']])->delete();
			}else{
				 Note::where(['NoteID'=>$data['NoteID']])->delete();
			}
        }catch (\Exception $ex){
            Log::info($ex);
            return $this->response->errorInternal($ex->getMessage());
        }
        return generateResponse('successfull');

    }
	
	public function add_account()
	{
            $data 				 	=	 Input::all();
            $companyID 			 	=	 User::get_companyID();
            $data['CompanyID'] 	 	= 	 $companyID;
            $data['AccountType'] 	= 	1;
            $data['IsVendor'] 	 	= 	isset($data['IsVendor']) ? 1 : 0;
            $data['IsCustomer']  	= 	isset($data['IsCustomer']) ? 1 : 0;
            $data['created_by']  	= 	User::get_user_full_name();
            $data['AccountType'] 	= 	1;
            if(isset($data['AccountName'])){
				$data['AccountName'] 	= 	trim($data['AccountName']);
			}
			
            if (isset($data['TaxRateId'])) {
                $data['TaxRateId'] = implode(',', array_unique($data['TaxRateId']));
            }
			
             $data['Status'] = isset($data['Status']) ? 1 : 0;

            if (empty($data['Number'])) {
                $data['Number'] = Account::getLastAccountNo();
            }
            $data['Number'] = trim($data['Number']);

        if(Company::isBillingLicence($this->request)) {
            Account::$rules['BillingType'] = 'required';
            Account::$rules['BillingTimezone'] = 'required';
        }

            Account::$rules['AccountName'] = 'required|unique:tblAccount,AccountName,NULL,CompanyID,CompanyID,' . $data['CompanyID'].',AccountType,1';
            Account::$rules['Number'] = 'required|unique:tblAccount,Number,NULL,CompanyID,CompanyID,' . $data['CompanyID'];

            $validator = Validator::make($data, Account::$rules, Account::$messages);

            if ($validator->fails()) {
                return generateResponse($validator->errors(),true);
            }
			
			 if (strpbrk($data['AccountName'], '\/?*:|"<>')) {
                 return generateResponse("Account Name contains illegal character",true);
            }
			
			 $data = cleanarray($data,['token']);

			
			try{
 	         	$account = Account::create($data);
                if (trim(Input::get('Number')) == ''){
                    CompanySetting::setKeyVal('LastAccountNo', $account->Number);
                }
                $data['NextInvoiceDate'] = Invoice::getNextInvoiceDate($account->AccountID);
                $account->update($data);
                return generateResponse('Account Successfully Created',false,false,$account);
            }catch (\Exception $ex){
                 Log::info($ex);
           		 return $this->response->errorInternal($ex->getMessage());
            }           
	}
	
	public function update_account($id) {
        $data 		= 	Input::all();
        $account 	= 	Account::find($id);
        $newTags 	= 	array_diff(explode(',',isset($data['tags'])?$data['tags']:''),Tags::getTagsArray());
        if(count($newTags)>0){
            foreach($newTags as $tag){
                Tags::create(array('TagName'=>$tag,'CompanyID'=>User::get_companyID(),'TagType'=>Tags::Account_tag));
            }
        }
        $message 				= 	$password = "";
        $companyID 				= 	User::get_companyID();
        $data['CompanyID'] 		= 	$companyID;
        $data['IsVendor'] 		= 	isset($data['IsVendor']) ? 1 : 0;
        $data['IsCustomer'] 	= 	isset($data['IsCustomer']) ? 1 : 0;
        $data['updated_by'] 	= 	User::get_user_full_name();
		$data['AccountName'] 	= 	trim(isset($data['AccountName'])?$data['AccountName']:'');

        $shipping = array('firstName'=>$account['FirstName'],
            'lastName'=>$account['LastName'],
            'address'=> isset($data['Address1'])?$data['Address1']:"",
            'city'=> isset($data['City'])?$data['City']:"",
            'state'=>isset($data['state'])?$account['state']:"",
            'zip'=>isset($data['PostCode'])?$data['PostCode']:"",
            'country'=>isset($data['Country'])?$data['Country']:"",
            'phoneNumber'=>$account['Mobile']);
        
		$data = cleanarray($data,['table-4_length','cardID']);
	
        if(isset($data['TaxRateId'])) {
            $data['TaxRateId'] = implode(',', array_unique($data['TaxRateId']));
        }
        if (strpbrk($data['AccountName'],'\/?*:|"<>')) {
				return API::response()->array(['status' => 'failed', 'message' => 'Account Name contains illegal character', 'status_code' => 432])->statusCode(432);
        }
        $data['Status'] = isset($data['Status']) ? 1 : 0;
        if(!isset($data['Number']) || trim($data['Number']) == ''){
            $data['Number'] = Account::getLastAccountNo();
        }

        if(empty($data['password'])){ /* if empty, dont update password */
            unset($data['password']);
			$data = cleanarray($data,['password']);
        }else{
            if($account->VerificationStatus == Account::VERIFIED && $account->Status == 1 ) {
                /* Send mail to Customer */
                $password       = $data['password'];
                $data['password']       = Hash::make($password);
            }
        }
        $data['Number'] = trim($data['Number']);

       if(Company::isBillingLicence($this->request)) {
            Account::$rules['BillingType'] = 'required';
            Account::$rules['BillingTimezone'] = 'required';
            $icount = Invoice::where(["AccountID" => $id])->count();
            if($icount>0){
                Account::$rules['InvoiceTemplateID'] = 'required';
            }
        }

        Account::$rules['AccountName'] = 'required|unique:tblAccount,AccountName,' . $account->AccountID . ',AccountID,CompanyID,'.$data['CompanyID'].',AccountType,1';
        Account::$rules['Number'] = 'required|unique:tblAccount,Number,' . $account->AccountID . ',AccountID,CompanyID,'.$data['CompanyID'];

        $validator = Validator::make($data, Account::$rules,Account::$messages);

        if ($validator->fails()) {
            return generateResponse($validator->errors(),true);
            exit;
        }
		
        $data['CustomerCLI'] = implode(',',array_unique(explode(',',isset($data['CustomerCLI'])?$data['CustomerCLI']:"")));
		$data = cleanarray($data,['token']);
       try{ 
	   		$account->update($data); 
            $data['NextInvoiceDate'] = Invoice::getNextInvoiceDate($id);
            $invoice_count = Account::getInvoiceCount($id);
            if($invoice_count == 0){
                $data['LastInvoiceDate'] = isset($data['BillingStartDate'])?$data['BillingStartDate']:"";
            }
            $account->update($data);
            if(trim(Input::get('Number')) == ''){
                CompanySetting::setKeyVal('LastAccountNo',$account->Number);
            }
            if(isset($data['password'])) {
                $this->sendPasswordEmail($account, $password, $data);
            }
			$isAuthorizedNet = 	\App\SiteIntegration::CheckIntegrationConfiguration(false,\App\SiteIntegration::$AuthorizeSlug);
			if($isAuthorizedNet){
				 $this->updateAuthorizeProfileShippingAddress($id,$companyID,$shipping);
			}
           return generateResponse('Account Successfully Updated ');
        }catch (\Exception $ex){
                 Log::info($ex);
           		 return $this->response->errorInternal($ex->getMessage());
        } 
        //return Redirect::route('accounts.index')->with('success_message', 'Accounts Successfully Updated');;
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

    public function GetCreditInfo()
    {
        $post_data = Input::all();
        $rules['AccountID'] = 'required';
        $validator = Validator::make($post_data, $rules);
        if ($validator->fails()) {
            return generateResponse($validator->errors(),true);
        }
        try {
            $AccountBalance = AccountBalance::where('AccountID', $post_data['AccountID'])->first(['AccountID', 'PermanentCredit', 'UnbilledAmount','EmailToCustomer', 'TemporaryCredit', 'TemporaryCreditDateTime', 'BalanceThreshold','BalanceAmount','VendorUnbilledAmount']);
        }catch (\Exception $ex){
            Log::info($ex);
            return $this->response->errorInternal($ex->getMessage());
        }
        return generateResponse('success',false,false,$AccountBalance);
    }

    public function UpdateCreditInfo()
    {
        $post_data = Input::all();
        $rules['AccountID'] = 'required';
        $rules['BalanceThreshold'] = 'required';
        $rules['PermanentCredit'] = 'required';
        $validator = Validator::make($post_data, $rules);
        if ($validator->fails()) {
            return generateResponse($validator->errors(),true);
        }
        $AccountBalancedata = $AccountBalance = array();
        if (isset($post_data['PermanentCredit'])) {
            $AccountBalancedata['PermanentCredit'] = $post_data['PermanentCredit'];
        }
        if (isset($post_data['TemporaryCredit'])) {
            $AccountBalancedata['TemporaryCredit'] = $post_data['TemporaryCredit'];
        }
        if (isset($post_data['TemporaryCreditDateTime'])) {
            $AccountBalancedata['TemporaryCreditDateTime'] = $post_data['TemporaryCreditDateTime'];
        }
        if (isset($post_data['BalanceThreshold'])) {
            $AccountBalancedata['BalanceThreshold'] = $post_data['BalanceThreshold'];
        }
        
        $AccountBalancedata['EmailToCustomer'] = isset($post_data['EmailToCustomer'])?1:0;

        try {
            if (!empty($AccountBalancedata) && AccountBalance::where('AccountID', $post_data['AccountID'])->count()) {
                $AccountBalance = AccountBalance::where('AccountID', $post_data['AccountID'])->update($AccountBalancedata);
                $AccountBalancedata['AccountID'] = $post_data['AccountID'];
            } elseif (AccountBalance::where('AccountID', $post_data['AccountID'])->count() == 0) {
                $AccountBalancedata['AccountID'] = $post_data['AccountID'];
                AccountBalance::create($AccountBalancedata);
            }
            unset($AccountBalancedata['EmailToCustomer']);
            AccountBalanceHistory::addHistory($AccountBalancedata);
            return generateResponse('Account Successfully Updated');
        } catch (\Exception $e) {
            Log::info($e);
            return $this->response->errorInternal();
        }
    }
    public function GetCreditHistoryGrid(){
        $post_data = Input::all();
        try {
            $companyID = User::get_companyID();
            $rules['iDisplayStart'] = 'required|Min:1';
            $rules['iDisplayLength'] = 'required';
            $rules['iDisplayLength'] = 'required';
            $rules['sSortDir_0'] = 'required';
            $rules['AccountID'] = 'required';
            $validator = Validator::make($post_data, $rules);
            if ($validator->fails()) {
                return generateResponse($validator->errors(),true);
            }
            $post_data['iDisplayStart'] += 1;
            $columns = ['PermanentCredit', 'TemporaryCredit', 'Threshold', 'CreatedBy','created_at'];
            $sort_column = $columns[$post_data['iSortCol_0']];
            $query = "call prc_GetAccountBalanceHistory (" . $companyID . "," . $post_data['AccountID'] . "," . (ceil($post_data['iDisplayStart'] / $post_data['iDisplayLength'])) . " ," . $post_data['iDisplayLength'] . ",'" . $sort_column . "','" . $post_data['sSortDir_0'] . "'";
            if (isset($post_data['Export']) && $post_data['Export'] == 1) {
                $result = DB::select($query . ',1)');
            } else {
                $query .= ',0)';
                $result = DataTableSql::of($query)->make();
            }
            Log::info($query);
            return generateResponse('',false,false,$result);
        } catch (\Exception $e) {
            Log::info($e);
            return $this->response->errorInternal($e->getMessage());
        }
    }
	
	//This function is only for authorize.net	
	public function updateAuthorizeProfileShippingAddress($AccountID,$companyID,$shipping){
		$PaymentGatewayID = PaymentGateway::where(['Title'=>PaymentGateway::$gateways['Authorize']])
		->where(['CompanyID'=>$companyID])
		->pluck('PaymentGatewayID');
		$PaymentProfile = AccountPaymentProfile::where(['AccountID'=>$AccountID])
		->where(['CompanyID'=>$companyID])
		->where(['PaymentGatewayID'=>$PaymentGatewayID])
		->first();
		
		if(!empty($PaymentProfile)){
			$options = json_decode($PaymentProfile->Options);
			$ProfileID = $options->ProfileID;
			$ShippingProfileID = $options->ShippingProfileID;
	
			//If using Authorize.net
			//$isAuthorizedNet = getenv('AMAZONS3_KEY');
			$AuthorizeNet = new AuthorizeNet();
			 $result = $AuthorizeNet->UpdateShippingAddress($ProfileID, $ShippingProfileID, $shipping);
		}
	}	
}