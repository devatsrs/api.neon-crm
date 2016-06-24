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
            'Subject'=>'required',
            'Message'=>'required'
        );

	    $CompanyID  = User::get_companyID();
        $account    = Account::find($data['AccountID']);

	   
	    if(getenv('EmailToCustomer') == 1){
			$data['EmailTo']	= 	$data['email-to'];
        }else{
            $data['EmailTo'] = Company::getEmail($CompanyID);
        }
        $validator = Validator::make($data,$rules);
        if ($validator->fails()) {
            return generateResponse($validator->errors(),true);
        }

        if (isset($data['file']) && !empty($data['file'])) {
            $data['AttachmentPaths'] = json_decode($data['file'],true);
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
        try{
            if(isset($data['email_send'])&& $data['email_send']==1) {
                $status = sendMail('emails.account.AccountEmailSend', $data);
            }

            $result 				= 	email_log_data($data,'emails.account.AccountEmailSend');
           	$result['message'] 		= 	'Email Sent Successfully';
			$user_data 				= 	User::where(["EmailAddress" => $data['email-to']])->get();
            if(count($user_data)>0) {

                $result->EmailTo = $user_data[0]['FirstName'].' '.$user_data[0]['LastName'];
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
            $attachments = json_decode($email->AttachmentPaths,true);
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