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
use Api\Model\Messages;
use App\Http\Requests;
use Dingo\Api\Facade\API;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Faker\Provider\Uuid;
use App\AmazonS3;

class MailboxController extends BaseController {

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
		$data 				= 		Input::all();  		
		$data['EmailTo']	= 		$data['email-to'];
		
		
		
		if($data['EmailCall']!=Messages::Draft)
		{		
			$rules = array(
				"email-to" =>'required',
				'Subject'=>'required',
				'Message'=>'required'			
			);
			
			$validator = Validator::make($data,$rules);
			if ($validator->fails()) {
				return generateResponse($validator->errors(),true);
			}
		}else 
		{
			if($data['email-to']=='' && $data['Subject']=='' && $data['Message']=='')	
			{
				$rules = array(
					"email-to" =>'required',
					'Subject'=>'required',
					'Message'=>'required'			
				);
			
				$validator = Validator::make($data,$rules);
				if ($validator->fails()) {
					return generateResponse($validator->errors(),true);
				}
				
			}
		}

        if (isset($data['file']) && !empty($data['file'])) {
            $data['AttachmentPaths'] = json_decode($data['file'],true);
        }
		
		if(isset($data['EmailParent'])){
			$ParentEmail 		   =  AccountEmailLog::find($data['EmailParent']);
			$data['In-Reply-To']   =  $ParentEmail->MessageID;
		}

        //$JobLoggedUser = User::find(User::get_userID());
       // $replace_array = Account::create_replace_array($account,array(),$JobLoggedUser);
       // $data['Message'] = template_var_replace($data['Message'],$replace_array);
	  // Log::info("api");	Log::info(print_r($data,true));	
	
		$data			  	= 	cleanarray($data,[]);	
		$data['AccountID']	=	isset($status['AccountID'])?$status['AccountID']:0;
		$status			  	= 	array();
		$result				=	array();
		$message_sent		=	'';
		
		$data['CompanyName']  = User::get_user_full_name(); //logined user's name as from name
		
        try{
            if(isset($data['EmailCall'])&& $data['EmailCall']==Messages::Sent) {				
                $status = sendMail('emails.template', $data);				
				$message_sent 		= 	'Email Sent Successfully';
            }else if(isset($data['EmailCall'])&& $data['EmailCall']==Messages::Draft){
				
				if(isset($data['AttachmentPaths']) && count($data['AttachmentPaths'])>0)
				{
					foreach($data['AttachmentPaths'] as $attachment_data) { 
						$file = \Webpatser\Uuid\Uuid::generate()."_". basename($attachment_data['filepath']); 
						$Attachmenturl = \App\AmazonS3::unSignedUrl($attachment_data['filepath']);
						file_put_contents($file,file_get_contents($Attachmenturl));						
					}
				} 
				$message_sent 		= 	'Email saved Successfully';
			}
			
			if(isset($data['AccountEmailLogID']) && $data['AccountEmailLogID']>0){ //delete old draft entry
					AccountEmailLog::find($data['AccountEmailLogID'])->delete();
			}
				
			
			$data['message_id'] 	=   isset($status['message_id'])?$status['message_id']:"";			
            $result 				= 	email_log_data($data,'emails.template');           	
			$result->message_sent	=   $message_sent;
			$multiple_addresses		= 	strpos($data['EmailTo'],',');

			if($multiple_addresses == false){
				$user_data 				= 	User::where(["EmailAddress" => $data['EmailTo']])->get();
				if(count($user_data)>0) {
					$result->EmailTo = $user_data[0]['FirstName'].' '.$user_data[0]['LastName'];
				}
			}
			
            return generateResponse('',false,false,$result);
        }catch (Exception $ex){
        	 return $this->response->errorInternal($ex->getMessage());
        }
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