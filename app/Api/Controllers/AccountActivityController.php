<?php
namespace Api\Controllers;
use Api\Model\AccountBalance;
use Api\Model\Account;
use Api\Model\Note;
use Api\Model\User;
use Api\Model\DataTableSql;
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
	
	public function __construct()
    {
        $this->middleware('jwt.auth');
    }
	/**
	 * Store a newly created resource in storage.
	 * POST /accountsubscription
	 *
	 * @return Response
	 */

    function sendtestmail()
    {
        $to = "umer.ahmed@code-desk.com";
        $subject = "HTML email";

        $message = "
<html>
<head>
<title>HTML email</title>
</head>
<body>
<p>This email contains HTML Tags!</p>
<table>
<tr>
<th>Firstname</th>
<th>Lastname</th>
</tr>
<tr>
<td>John</td>
<td>Doe</td>
</tr>
</table>
</body>
</html>
";

// Always set content-type when sending HTML email
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";

// More headers
        $headers .= 'From: <noreply@wave-tel.com>' . "\r\n";


        mail($to,$subject,$message,$headers);
    }
    public function sendMail(){
        $this->sendtestmail();
		
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
            $data['EmailTo'] = User::getEmail($CompanyID);		
        }
        $validator = Validator::make($data,$rules);
        if ($validator->fails()) {
            //return $this->response->errorBadRequest($validator->errors());
            return $this->response->error($validator->errors(),'432');
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
                    return $this->response->errorBadRequest($ext." file type is not allowed. Allowed file types are ".$allowed);
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
                    return $this->response->errorBadRequest('Failed to upload');
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
        try{
            $status 				= 	sendMail('emails.account.AccountEmailSend',$data);           
            $result 				= 	email_log_data($data);
           	$result['message'] 		= 	'Email Sent Successfully';
			$user_data 				= 	User::where(["EmailAddress" => $data['email-to']])->get();
            if(count($user_data)>0) {

                $result->EmailTo = $user_data[0]['FirstName'].' '.$user_data[0]['LastName'];
            }
            return API::response()->array(['status' => 'success', "LogID" =>$result['AccountEmailLogID'], 'data' => ['result' => $result], 'status_code' => 200])->statusCode(200);
        }catch (Exception $ex){
        	 return $this->response->errorInternal($ex->getMessage());
        }
    }

}