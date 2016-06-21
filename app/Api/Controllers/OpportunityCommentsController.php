<?php
namespace Api\Controllers;

use Dingo\Api\Http\Request;
use Api\Model\Account;
use Api\Model\Opportunity;
use Api\Model\CRMComments;
use Api\Model\User;
use App\AmazonS3;
use App\Http\Requests;
use Dingo\Api\Facade\API;
use Faker\Provider\Uuid;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
class OpportunityCommentsController extends BaseController {

    public function __construct(Request $request)
    {
        $this->middleware('jwt.auth');
        Parent::__Construct($request);
    }

    /** Return opportunity comment and its attachments.
     * @param $id
     * @return mixed
     */
    public function get_comments($id){
        $select = ['CommentText','AttachmentPaths','created_at','CreatedBy','CommentID'];
        $result = CRMComments::select($select)->where(['ParentID'=>$id,'CommentType'=>CRMComments::opportunityComments])->orderby('created_at','desc')->get();
        return generateResponse('',false,false,$result);
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
            'CommentText'=>'required'
        );
        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            return generateResponse($validator->errors(),true);
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
                    return generateResponse($ext." file type is not allowed. Allowed file types are ".$allowed,true,true);
                }
            }

            $commentattachment = uploaded_File_Handler($data['file']);
            $commentattachments=[];
            foreach ($commentattachment as $attachment) {
                $ext = $attachment['Extension'];
                $originalfilename = $attachment['fileName'];
                $file_name = "OpportunityAttachment_" . Uuid::uuid() . '.' . $ext;

                $amazonPath = \App\AmazonS3::generate_upload_path(\App\AmazonS3::$dir['OPPORTUNITY_ATTACHMENT']);
                $destinationPath = getenv("UPLOAD_PATH") . '/' . $amazonPath;
                rename_win($attachment['file'],$destinationPath.$file_name);
                if (!\App\AmazonS3::upload($destinationPath . $file_name, $amazonPath)) {
                    return generateResponse('Failed to upload',true,true);
                }
                $fullPath = $amazonPath . $file_name;
                $commentattachments[] = ['filename' => $originalfilename, 'filepath' => $fullPath];
            }
        }

        if(!empty($commentattachments)){
            $comment_data['AttachmentPaths'] = json_encode($commentattachments);
            $emailData['AttachmentPaths'] = $commentattachments;
        }

        $comment_data["CommentText"] = $data["CommentText"];
        $comment_data["ParentID"] = $data["OpportunityID"];
        $comment_data["CommentType"] = CRMComments::opportunityComments;
        $comment_data["CreatedBy"] = User::get_user_full_name();
        $comment_data["UserID"] = User::get_userID();
        $companyID = User::get_companyID();
        $data ["CompanyID"] = $companyID;
        $data = cleanarray($data);
        try{
            CRMComments::create($comment_data);
            $opportunity = Opportunity::where(['OpportunityID'=>$data["OpportunityID"]])->get()[0];
            $taggedUsers = explode(',',$opportunity->TaggedUsers);
            $taggedUsers[] = $opportunity->UserID;
            $users = User::whereIn('UserID',$taggedUsers)->select(['EmailAddress'])->get('EmailAddress');
            $emailTo = [];
            foreach($users as $user){
                $emailTo[] = $user->EmailAddress;
            }
            $emailData['Subject']='New Comment';
            $status['status'] = 1;
            $emailData['Message'] = $comment_data['CommentText'];
            $emailData['CompanyID'] = $data ["CompanyID"];
            $emailData['EmailToName'] = '';
            $emailData['CreatedBy'] = User::get_user_full_name();
            $emailData['Task'] = $opportunity->OpportunityName.' Opportunity';
            $emailData['Logo'] = getCompanyLogo($this->request);
            //$emailData['mandrill'] =1;
            if(!empty($emailTo) && count($emailTo)>0){
                $emailData['EmailTo'] = $emailTo;
                $status = sendMail('emails.crm.AccountUserEmailSend',$emailData);
            }
            if($status['status']==1){
                if(isset($data['PrivateComment']) && $data['PrivateComment']==1) {
                    $account = Account::find($data['AccountID']);
                    $emailData['AccountID'] = $account->AccountID;
                    $emailData['EmailTo'] = $opportunity->Email;
                    $emailData['EmailToName'] = $opportunity->FirstName.' '.$opportunity->LastName;
                    $emailData['CompanyID'] = $data ["CompanyID"];
                    $status = sendMail('emails.crm.AccountUserEmailSend',$emailData);
                    $emailData['Message'] = $status['body'];
                    $status = email_log($emailData);
                    if($status['status']==0){
                        return generateResponse($status['message'],true,true);
                    }
                }
            }else{
                return generateResponse($status['message'],true,true);
            }
        }catch (\Exception $ex){
            Log::info($ex);
            return $this->response->errorInternal($ex->getMessage());
        }
        return generateResponse('Comment added successfully');

    }

    public function getAttachment($commentdID,$attachmentID){
        if(intval($commentdID)>0) {
            $comment = CRMComments::find($commentdID);
            $attachments = json_decode($comment->AttachmentPaths,true);
            $attachment = $attachments[$attachmentID];
            if(!empty($attachment)){
                return generateResponse('',false,false,$attachment);
            }else{

            }
        }else{
            return generateResponse('Not found',true,true);
        }
    }

}