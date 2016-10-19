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
use App\Http\Requests;
use Dingo\Api\Facade\API;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Faker\Provider\Uuid;
use App\AmazonS3;

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
        $rules = array(
			"email-to" =>'required',
            'Subject'=>'required',
            'Message'=>'required'			
        );

        $account    		= 	Account::find($data['AccountID']);
 	    $data['EmailTo']	= 	$data['email-to'];

        $validator = Validator::make($data,$rules);
        if ($validator->fails()) {
            return generateResponse($validator->errors(),true);
        }

        if (isset($data['file']) && !empty($data['file'])) {
            $data['AttachmentPaths'] = json_decode($data['file'],true);
        }
		
		if(isset($data['EmailParent'])){
			$ParentEmail 		   =  AccountEmailLog::find($data['EmailParent']);
			$data['In-Reply-To']   =  $ParentEmail->MessageID;
		}

        $JobLoggedUser = User::find(User::get_userID());
        $replace_array = Account::create_replace_array($account,array(),$JobLoggedUser);
        $data['Message'] = template_var_replace($data['Message'],$replace_array);
		// image upload end
		
			
		
        $data['mandrill'] = 0;
		$data = cleanarray($data,[]);	
		//$data['CompanyName'] = $account->AccountName;
		$data['CompanyName'] = User::get_user_full_name(); //logined user's name as from name
		
        try{
            if(isset($data['email_send'])&& $data['email_send']==1) {
				
                $status = sendMail('emails.template', $data);
            }
			if($status['status']==0){
				 return generateResponse($status['message'],true,true);
			}
			
			$data['message_id'] 	=  isset($status['message_id'])?$status['message_id']:"";			
            $result 				= 	email_log_data($data,'emails.template');
           	$result['message'] 		= 	'Email Sent Successfully';
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