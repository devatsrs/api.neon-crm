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

        if (isset($data['file']) && !empty($data['file'])) {
            $comment_data['AttachmentPaths'] = $data['file'];
            $emailData['AttachmentPaths'] = json_decode($data['file'],true);
        }
        $companyID = User::get_companyID();
        $comment_data["CommentText"] = $data["CommentText"];
        $comment_data["ParentID"] = $data["OpportunityID"];
        $comment_data["CommentType"] = CRMComments::opportunityComments;
        $comment_data["CreatedBy"] = User::get_user_full_name();
        $comment_data["UserID"] = User::get_userID();
        $comment_data["CompanyID"] = $companyID;
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

            $emailData['address'] = User::get_user_email();
            $emailData['name'] = User::get_user_full_name();

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
                return generateResponse('Not found',true,true);
            }
        }else{
            return generateResponse('Not found',true,true);
        }
    }

}