<?php
namespace Api\Controllers;

use Api\Model\Opportunity;
use Api\Model\OpportunityComments;
use Api\Model\User;
use App\AmazonS3;
use App\Http\Requests;
use Dingo\Api\Facade\API;
use Faker\Provider\Uuid;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
class OpportunityCommentsController extends BaseController {

    public function __construct()
    {
        $this->middleware('jwt.auth');
    }

    /** Return opportunity comment and its attachments.
     * @param $id
     * @return mixed
     */
    public function get_comments($id){
        $select = ['CommentText','AttachmentPaths','created_at','CreatedBy'];
        $result = OpportunityComments::select($select)->where(['OpportunityID'=>$id])->orderby('created_at','desc')->get();
        $reponse_data = ['status' => 'success', 'data' => ['result' => $result], 'status_code' => 200];
        return API::response()->array($reponse_data)->statusCode(200);
    }

	/**
	 * Show the form for creating a new resource.
	 * GET /dealboard/create
	 *
	 * @return Response
	 */
    public function add_comment(){
        $data = Input::all();
        $rules = array(
            'OpportunityID' => 'required',
            'CommentText' => 'required'
        );
        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            return $this->response->error($validator->errors(),'432');
        }

        $commentattachments = [];
        $comment_data=[];
        if (isset($data['file'])) {
            $commentattachment = $data['file'];
            $allowed = getenv("CRM_ALLOWED_FILE_UPLOAD_EXTENSIONS");
            $allowedextensions = explode(',',$allowed);
            $allowedextensions = array_change_key_case($allowedextensions);
            foreach ($commentattachment as $attachment) {
                $ext = $attachment['fileExtension'];
                if (!in_array(strtolower($ext), $allowedextensions)) {
                    return $this->response->errorBadRequest($ext." file type is not allowed. Allowed file types are ".$allowed);
                }
            }

            $commentattachment = uploaded_File_Handler($data['file']);
            $commentattachments=[];
            foreach ($commentattachment as $attachment) {
                $ext = $ext = $attachment['Extension'];
                $originalfilename = $attachment['fileName'];
                $file_name = "OpportunityAttachment_" . Uuid::uuid() . '.' . $ext;
                $amazonPath = AmazonS3::generate_upload_path(AmazonS3::$dir['OPPORTUNITY_ATTACHMENT']);
                $destinationPath = getenv("UPLOAD_PATH") . '/' . $amazonPath;
                rename_win($attachment['file'],$destinationPath.$file_name);
                if (!AmazonS3::upload($destinationPath . $file_name, $amazonPath)) {
                    return $this->response->errorBadRequest('Failed to upload');
                }
                $fullPath = $amazonPath . $file_name;
                $commentattachments[] = ['filename' => $originalfilename, 'filepath' => $fullPath];
            }
        }

        if(!empty($commentattachments)){
            $comment_data['AttachmentPaths'] = json_encode($commentattachments);
        }

        $comment_data["CommentText"] = $data["CommentText"];
        $comment_data["OpportunityID"] = $data["OpportunityID"];
        $comment_data["CreatedBy"] = User::get_user_full_name();
        $companyID = User::get_companyID();
        $data ["CompanyID"] = $companyID;
        try{
            OpportunityComments::create($comment_data);
            $taggedUser = Opportunity::where(['OpportunityID'=>$data["OpportunityID"]])->pluck('TaggedUser');
            $users = User::whereIn('UserID',explode(',',$taggedUser))->select(['EmailAddress'])->list('EmailAddress');
            $emailData['Subject']='New Comment';
            $emailData['EmailTo'] = $users;
            $status = sendMail('emails.opportunity.AccountUserEmailSend',$emailData);
            if($status['status']==1){
                if($data['PrivateComment']==1) {
                    $account = Account::find($data['AccountID']);
                    $emailData['AccountID'] = $account->AccountID;
                    $emailData['EmailTo'] = $account->Email;
                    $status = sendMail('emails.account.AccountEmailSend', $data);
                    email_log($emailData);
                }
            }
        }catch (\Exception $ex){
            Log::info($ex);
            return $this->response->errorInternal($ex->getMessage());
        }
        return API::response()->array(['status' => 'success', 'message' => 'Comment save successfully', 'status_code' => 200])->statusCode(200);

    }

}