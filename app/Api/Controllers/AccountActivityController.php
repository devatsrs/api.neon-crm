<?php
namespace Api\Controllers;

use Api\Model\Company;
use Dingo\Api\Http\Request;
use Api\Model\AccountBalance;
use Api\Model\Account;
use Api\Model\Note;
use Api\Model\User;
use Api\Model\DataTableSql;
use Api\Model\AccountEmailLog;
use Api\Model\TicketGroups;
use Api\Model\TicketsTable;
use Api\Model\TicketPriority;
use Api\Model\Messages;
use Api\Model\Contact;
use App\Http\Requests;
use Dingo\Api\Facade\API;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Faker\Provider\Uuid;
use App\AmazonS3;
use App\TicketEmails;

class AccountActivityController extends BaseController {

    public function __construct(Request $request)
    {
        $this->middleware('jwt.auth');
        Parent::__Construct($request);
    }
	/**
	 * Store a newly created resource in storage.
	 * POST /accountsubscription
	 *
	 * @return Response
	 */

    public function sendMail(){ 
		$data = Input::all();  
		$usertype = 0;	 //acount by default	
        $rules = array(
			"email-to" =>'required',
            'Subject'=>'required',
            'Message'=>'required'			
        );

		if(isset($data['usertype'])  && $data['usertype']==Messages::UserTypeContact){
			$Contact	 	=	Contact::find($data['ContactID']);	
			$usertype 		  		 =    1;
		}else{
			$account	 	=    Account::find($data['AccountID']);	
		}
		
 	    $data['EmailTo']	= 	$data['email-to'];

        $validator = Validator::make($data,$rules);
        if ($validator->fails()) {
            return generateResponse($validator->errors(),true);
        }
		$files = '';
        if (isset($data['file']) && !empty($data['file'])) {
            $data['AttachmentPaths'] = json_decode($data['file'],true);
			$files = serialize(json_decode($data['file'],true));			
        }
		
		if(isset($data['EmailParent'])){
			$ParentEmail 		   =  AccountEmailLog::find($data['EmailParent']);
			$data['In-Reply-To']   =  $ParentEmail->MessageID;
		}

        $JobLoggedUser = User::find(User::get_userID());
		if($usertype){
			$replace_array  = Contact::create_replace_array_contact($Contact,array(),$JobLoggedUser);
		}else{
       		 $replace_array = Account::create_replace_array($account,array(),$JobLoggedUser);
		}
        $data['Message'] = template_var_replace($data['Message'],$replace_array);
		// image upload end
		
			
		
        $data['mandrill'] = 0;
		$data = cleanarray($data,[]);	
		//$data['CompanyName'] = $account->AccountName;
		$data['CompanyName'] = User::get_user_full_name(); //logined user's name as from name
		
        try{
			 DB::beginTransaction();
        	 Contact::CheckEmailContact($data['EmailTo'],isset($data['AccountID'])?$data['AccountID']:0);
			 Contact::CheckEmailContact($data['cc'],isset($data['AccountID'])?$data['AccountID']:0);
			 Contact::CheckEmailContact($data['bcc'],isset($data['AccountID'])?$data['AccountID']:0);		
			 
			 if(isset($data['createticket']) && TicketsTable::CheckTicketLicense()){ //check and create ticket
			 	$email_from_data   	= 	TicketGroups::where(["GroupEmailAddress"=>$data['email-from']])->select('GroupEmailAddress','GroupName','GroupID','GroupReplyAddress')->get(); 
				$TicketData = array(
					"CompanyID"=>User::get_companyID(),
					"Requester"=>$data['EmailTo'],
					"Subject"=>isset($data['Subject'])?$data['Subject']:'',
					"Type"=>0,
					"Group"=>isset($email_from_data[0]->GroupID)?$email_from_data[0]->GroupID:0,
					"Status"=>TicketsTable::getDefaultStatus(),
					"Priority"=>TicketPriority::getDefaultPriorityStatus(),					
					"Description"=>isset($data['Message'])?$data['Message']:'',	 
					"AttachmentPaths"=>$files,
					"TicketType"=>TicketsTable::EMAIL,
					"created_at"=>date("Y-m-d H:i:s"),
					"created_by"=>User::get_user_full_name()
				);
				$TicketID = TicketsTable::insertGetId($TicketData);	
				 $data['In-Reply-To']	  = 	$email_from_data[0]->GroupEmailAddress;				
				 $data['EmailFrom']	   	  = 	$email_from_data[0]->GroupReplyAddress;
				 $data['CompanyName']  	  = 	$email_from_data[0]->GroupName;		
			 }else{
				 $data['EmailFrom']	   = 	$data['email-from'];
			 }
			 
			 
			if(isset($data['email_send'])&& $data['email_send']==1) {
					  
                $status = sendMail('emails.template', $data);
            }else{$status = array("status"=>1);}
			if($status['status']==0){
				 return generateResponse($status['message'],true,true);
			}
			
			$data['message_id'] 	=  isset($status['message_id'])?$status['message_id']:"";			
            $result 				= 	email_log_data($data,'emails.template');
           	$result->message 		= 	'Email Sent Successfully';
			$multiple_addresses		= 	strpos($data['EmailTo'],',');

			if($multiple_addresses == false){
				$user_data 				= 	User::where(["EmailAddress" => $data['EmailTo']])->get();
				if(count($user_data)>0) {
					$result->EmailTo = $user_data[0]['FirstName'].' '.$user_data[0]['LastName'];
				}
			} 
			 if(isset($data['createticket']) && TicketsTable::CheckTicketLicense()){ //check and create ticket
			 	TicketsTable::find($TicketID)->update(array("AccountEmailLogID"=>$result->AccountEmailLogID));
				 $TicketEmails1		=  new TicketEmails(array("TicketID"=>$TicketID,"TriggerType"=>array("RequesterNewTicketCreated")));				 
				 $TicketEmails 		=  new TicketEmails(array("TicketID"=>$TicketID,"TriggerType"=>"CCNewTicketCreated"));
			 }
			  DB::commit(); 
            return generateResponse('',false,false,$result);
        }catch (Exception $ex){ 	
			 DB::rollback(); 
        	 return $this->response->errorInternal($ex->getMessage());
        }
    }

    public function  GetMail()
    {
        $data = Input::all();

        $rules['EmailID'] = 'required';
        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            return generateResponse($validator->errors(),true);
        }
        try {
            $Email = AccountEmailLog::find($data['EmailID']);
        } catch (\Exception $e) {
            Log::info($e);
            return $this->response->errorInternal($e->getMessage());
        }
        return generateResponse('',false,false,$Email);
    }

    public function DeleteMail(){
        $data = Input::all();

        $rules['AccountEmailLogID'] = 'required';
        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            return generateResponse($validator->errors(),true);
        }

        try{
            AccountEmailLog::where(['AccountEmailLogID'=>$data['AccountEmailLogID']])->delete();
        }catch (\Exception $ex){
            Log::info($ex);
            return $this->response->errorInternal($ex->getMessage());
        }
        return generateResponse('successfull');
    }

    public function getAttachment($emailID,$attachmentID){
        if(intval($emailID)>0) {
            $email = AccountEmailLog::find($emailID);
            $attachments = unserialize($email->AttachmentPaths);
            $attachment = $attachments[$attachmentID];
            if(!empty($attachment)){
                return generateResponse('',false,false,$attachment);
            }else{
                return generateResponse('Not found',true,true);
            }
        }else{
            return generateResponse('Not found',true,true);
        }
    }

}