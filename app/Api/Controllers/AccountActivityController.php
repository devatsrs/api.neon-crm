<?php
namespace Api\Controllers;

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

	    $CompanyID  = User::get_companyID();
        $account    = Account::find($data['AccountID']);

	   
	    if(getenv('EmailToCustomer') == 1){
			$data['EmailTo']	= 	$data['email-to'];
        }else{
            $data['EmailTo'] = User::getEmail($CompanyID);		
        }
        $validator = Validator::make($data,$rules);
        if ($validator->fails()) {
            return generateResponse($validator->errors(),true);
        }
		
		
		// image upload start
        $emailattachments 		= 		[];
        if (isset($data['file'])) {
            $emailattachment = $data['file'];
            $allowed = getenv("CRM_ALLOWED_FILE_UPLOAD_EXTENSIONS");
            $allowedextensions = explode(',',$allowed);
            $allowedextensions = array_change_key_case($allowedextensions);
            foreach ($emailattachment as $attachment) {				
                $ext = $attachment['fileExtension'];
                if (!in_array(strtolower($ext), $allowedextensions)) {
                    return generateResponse($message,true);
                }
            }

            $emailattachment = uploaded_File_Handler($data['file']);
            $emailattachments  = [];
            foreach ($emailattachment as $attachment) {
                $ext = $ext = $attachment['Extension'];
                $originalfilename = $attachment['fileName'];
                $file_name = "EmailAttachment_" . Uuid::uuid() . '.' . $ext;
                $amazonPath = AmazonS3::generate_upload_path(AmazonS3::$dir['EMAIL_ATTACHMENT']);
                $destinationPath = getenv("UPLOAD_PATH") . '/' . $amazonPath;
                rename_win($attachment['file'],$destinationPath.$file_name);
                if (!AmazonS3::upload($destinationPath . $file_name, $amazonPath)) {
                    return generateResponse('Failed to upload',true);
                }
                $fullPath = $amazonPath . $file_name;
                $emailattachments[] = ['filename' => $originalfilename, 'filepath' => $fullPath];
            }
        }
		
		   if(!empty($emailattachments)){
            $data['AttachmentPaths'] = $emailattachments;
        }

        $JobLoggedUser = User::find(User::get_userID());
        $Signature = '';
        if(!empty($JobLoggedUser)){
            if(isset($JobLoggedUser->EmailFooter) && trim($JobLoggedUser->EmailFooter) != '')
            {
                $Signature = $JobLoggedUser->EmailFooter;
            }
        }

        $extra = ['{{FirstName}}','{{LastName}}','{{Email}}','{{Address1}}','{{Address2}}','{{Address3}}','{{City}}','{{State}}','{{PostCode}}','{{Country}}','{{Signature}}'];
        $replace = [$account->FirstName,$account->LastName,$account->Email,$account->Address1,$account->Address2,$account->Address3,$account->City,$account->State,$account->PostCode,$account->Country,$Signature];

        $data['extra'] = $extra;
        $data['replace'] = $replace;
		// image upload end

        $data['mandrill'] = 0;
		$data = cleanarray($data,[]);
        try{
            if(isset($data['email_send'])&& $data['email_send']==1) {
                $status = sendMail('emails.account.AccountEmailSend', $data);
            }

            $result 				= 	email_log_data($data,'emails.account.AccountEmailSend');
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

}