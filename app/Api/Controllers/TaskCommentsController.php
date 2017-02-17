<?php
namespace Api\Controllers;

use Dingo\Api\Http\Request;
use Api\Model\Account;
use Api\Model\Task;
use Api\Model\CRMComments;
use Api\Model\User;
use Api\Model\UserProfile;
use App\AmazonS3;
use App\EmailsTemplates;
use App\Http\Requests;
use Dingo\Api\Facade\API;
use Faker\Provider\Uuid;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class TaskCommentsController extends BaseController {

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
        $result = CRMComments::select($select)->where(['ParentID'=>$id,'CommentType'=>CRMComments::taskComments])->orderby('created_at','desc')->get();
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
		 $LogginedUser   =  \Api\Model\User::get_userID();
        $rules = array(
            'TaskID' => 'required',
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
        $comment_data["ParentID"] = $data["TaskID"];
        $comment_data["CommentType"] = CRMComments::taskComments;
        $comment_data["CreatedBy"] = User::get_user_full_name();
        $comment_data["UserID"] = User::get_userID();
        $comment_data["CompanyID"] = $companyID;
        $data ["CompanyID"] = $companyID;
        $data = cleanarray($data);
        try{
            CRMComments::create($comment_data);
            $task = Task::where(['TaskID'=>$data["TaskID"]])->get()[0];
            $taggedUsers = explode(',',$task->TaggedUsers);
            $taggedUsers[] = $task->UsersIDs;
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
            $emailData['Task'] = $task->Subject.' Task';
			$emailData['Subject_task'] = $task->Subject.' Task';			
            $emailData['Logo'] = getCompanyLogo($this->request);
			$emailData['CommentText'] = $comment_data['CommentText'];
			$emailData['UserProfileImage']  	=  UserProfile::get_user_picture_url($LogginedUser);
			$emailData['user']  	=  \Api\Model\User::get_user_full_name();
			$emailData['CommentText'] = $comment_data['CommentText'];
			$emailData['type']		 =	 'Task';
            //$emailData['mandrill'] =1;
            if(!empty($emailTo) && count($emailTo)>0){
                $emailData['EmailTo'] = $emailTo;			 
				$body					=  EmailsTemplates::SendOpportunityTaskTagEmail(Task::TASKCOMMENTTEMPLATE,$comment_data,'body',$emailData);
				$emailData['Subject']	=  EmailsTemplates::SendOpportunityTaskTagEmail(Task::TASKCOMMENTTEMPLATE,$comment_data,"subject",$emailData);
				$emailData['EmailFrom']	=  EmailsTemplates::GetEmailTemplateFrom(Task::TASKCOMMENTTEMPLATE);
				$status = sendMail($body,$emailData,0);
				
            }
            if($status['status']==1){
                if(isset($data['PrivateComment']) && $data['PrivateComment']==1) {
                    $account = Account::find($data['AccountID']);
                    $emailData['AccountID'] = $account->AccountID;
                    $emailData['EmailTo'] = $account->Email;
                    $emailData['EmailToName'] = $account->FirstName.' '.$account->LastName;
                    $emailData['CompanyID'] = $data ["CompanyID"];
					if(EmailsTemplates::CheckEmailTemplateStatus(Task::TASKCOMMENTTEMPLATE)){	
                    $status = sendMail($body,$emailData,0);
                    $emailData['Message'] = $body;
					$emailData['message_id'] 	=  isset($status['message_id'])?$status['message_id']:"";
                    $status = email_log($emailData);
                    if($status['status']==0){
                        return generateResponse($status['message'],true,true);
                    }
					}else{$status['status'] =1;}
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