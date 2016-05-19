<?php
namespace Api\Controllers;

use Api\Model\DataTableSql;
use Api\Model\Task;
use Api\Model\User;
use Api\Model\Tags;
use Api\Model\Lead;
use Api\Model\CRMBoardColumn;
use Api\Model\AccountEmailLog;
use Api\Model\Account;
use App\AmazonS3;
use App\Http\Requests;
use Dingo\Api\Facade\API;
use Faker\Provider\Uuid;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TaskController extends BaseController {

    public function __construct()
    {
        $this->middleware('jwt.auth');
    }
    /**
     * Display a listing of the resource.
     * GET /Deal board
     *
     * @return Response

     */

    public function getTasks($id){
        $companyID = User::get_companyID();
        $data = Input::all();
        if(!isset($data['fetchType'])){
            $data['fetchType'] = 'Grid';
        }
        $data['AccountOwner'] = isset($data['AccountOwner'])?empty($data['AccountOwner'])?0:$data['AccountOwner']:'';
        $data['AccountIDs'] = isset($data['AccountIDs'])?empty($data['AccountIDs'])?0:$data['AccountIDs']:0;
        $data['Priority'] = isset($data['Priority'])?empty($data['Priority']) || $data['Priority']=='false'?0:$data['Priority']:0;
        $data['TaskStatus'] = isset($data['TaskStatus'])?empty($data['TaskStatus'])?0:$data['TaskStatus']:0;
        if(isset($data['DueDateFilter'])){
            $data['DueDateFrom'] = $data['DueDateFilter']!=Task::CustomDate?$data['DueDateFilter']:isset($data['DueDateFrom'])?$data['DueDateFrom']:'';
            $data['DueDateTo'] = $data['DueDateFilter']!=Task::CustomDate?$data['DueDateFilter']:isset($data['DueDateTo'])?$data['DueDateTo']:'';
        }
        if($data['fetchType']=='Grid') {
            $rules['iDisplayStart'] = 'required|Min:1';
            $rules['iDisplayLength'] = 'required';
            $rules['sSortDir_0'] = 'required';
            $validator = Validator::make($data, $rules);
            if ($validator->fails()) {
                return generateResponse($validator->errors(),true);
            }

            $columns = ['Subject', 'DueDate', 'Status', 'Priority','UserID'];
            $sort_column = $columns[$data['iSortCol_0']];

            $query = "call prc_GetTasksGrid (" . $companyID . ", " . $id . ",'" . $data['taskName'] . "'," . $data['AccountOwner'] . ", " . $data['AccountIDs'] . ", " . $data['Priority'] .",'".$data['DueDateFrom']."','".$data['DueDateTo']."',".$data['TaskStatus'].",".(ceil($data['iDisplayStart'] / $data['iDisplayLength'])) . " ," . $data['iDisplayLength'] . ",'" . $sort_column . "','" . $data['sSortDir_0'] . "')";
            try {
                $result = DataTableSql::of($query)->make();
                return generateResponse('',false,false,$result);
            }catch (\Exception $ex){
                Log::info($ex);
                return $this->response->errorInternal($ex->getMessage());
            }
        }elseif($data['fetchType']=='Board') {
            $query = "call prc_GetTasksBoard (" . $companyID . ", " . $id . ",'" . $data['taskName'] . "'," . $data['AccountOwner'] . ", " . $data['AccountIDs'] . ", " . $data['Priority'] .",'".$data['DueDateFrom']."','".$data['DueDateTo']."',".$data['TaskStatus'].")";
            try{
                $result = DB::select($query);
                $columnsWithITask = [];
                $columns = [];
                foreach($result as $row){
                    $columns[$row->BoardColumnID] = ['Name'=>$row->BoardColumnName/*,'Height'=>$row->Height,'Width'=>$row->Width*/];
                    if(!empty($row->Subject)) {
                        $users = [];
                        if(!empty($row->TaggedUsers)){
                            $users = User::whereIn('UserID',explode(',',$row->TaggedUsers))->select(['FirstName','LastName','UserID'])->get();
                        }
                        $columnsWithITask[$row->BoardColumnID][] = ['TaggedUsers'=>$users,'task'=>$row];
                    }else{
                        $columnsWithITask[$row->BoardColumnID][] = '';
                    }
                }
                $return['columns'] = $columns;
                $return['columnsWithITask'] = $columnsWithITask;
                return generateResponse('',false,false,$return);
            }catch (\Exception $ex){
                Log::info($ex);
                return $this->response->errorInternal($ex->getMessage());
            }
        }
    }

    public function getAttachments($id){
        $attachementPaths = Task::where(['TaskID'=>$id])->pluck('AttachmentPaths');
        if(!empty($attachementPaths)){
            $attachementPaths = json_decode($attachementPaths);
        }
        return generateResponse('',false,false,$attachementPaths);
    }

    public function saveAttachment($id){
        $data = Input::all();
        $taskattachment = $data['file'];

        $allowed = getenv("CRM_ALLOWED_FILE_UPLOAD_EXTENSIONS");
        $allowedextensions = explode(',',$allowed);
        $allowedextensions = array_change_key_case($allowedextensions);
        foreach ($taskattachment as $attachment) {
            $ext = $attachment['fileExtension'];
            if (!in_array(strtolower($ext), $allowedextensions)) {
                return generateResponse($ext." file type is not allowed. Allowed file types are ".$allowed,true,true);
            }
        }
        $taskattachment = uploaded_File_Handler($data['file']);
        $AttachmentPaths = Task::where(['TaskID'=>$id])->pluck('AttachmentPaths');
        $taskattachments = [];

        foreach ($taskattachment as $attachment) {
            $ext = $ext = $attachment['Extension'];
            $originalfilename = $attachment['fileName'];
            $file_name = "TaskAttachment_" . Uuid::uuid() . '.' . $ext;
            $amazonPath = \App\AmazonS3::generate_upload_path(\App\AmazonS3::$dir['TASK_ATTACHMENT']);
            $destinationPath = getenv("UPLOAD_PATH") . '/' . $amazonPath;
            rename_win($attachment['file'],$destinationPath.$file_name);
            if (!\App\AmazonS3::upload($destinationPath . $file_name, $amazonPath)) {
                return generateResponse('Failed to upload',true,true);
            }
            $fullPath = $amazonPath . $file_name;
            $taskattachments[] = ['filename' => $originalfilename, 'filepath' => $fullPath];
        }

        if(count($taskattachments)>0){
            $AttachmentPaths = json_decode($AttachmentPaths,true);
            if(count($AttachmentPaths)>0) {
                $taskattachments = array_merge($AttachmentPaths , $taskattachments);
            }
            $task_data['AttachmentPaths'] = json_encode($taskattachments);
            $result = Task::where(['TaskID'=>$id])->update($task_data);
            if($result){
                return generateResponse('Attachment saved successfully');
            }else{
                return generateResponse('Problem saving attachment.',true,true);
            }
        } else{
            return generateResponse('No attachment found.',true,true);
        }
    }

    public function deleteAttachment($taskID,$attachmentID){
        $attachmentPaths = Task::where(['TaskID'=>$taskID])->pluck('AttachmentPaths');
        if(!empty($attachmentPaths)){
            $attachmentPaths = json_decode($attachmentPaths,true);
            unset($attachmentPaths[$attachmentID]);
            $data = ['AttachmentPaths'=>json_encode($attachmentPaths)];

            try{
                Task::where(['taskID'=>$taskID])->update($data);
            }catch (\Exception $ex){
                Log::info($ex);
                return $this->response->errorInternal($ex->getMessage());
            }
            return generateResponse('Attachment deleted successfully');
        }else{
            return generateResponse('No attachment found',true,true);
        }
    }
    /**
     * Show the form for creating a new resource.
     * GET /dealboard/create
     *
     * @return Response
     */

    public function addTask(){
        $data = Input::all();
       
        $companyID = User::get_companyID();
        $message = '';
        $data ["CompanyID"] = $companyID;
        $rules = array(
            'CompanyID' => 'required',
            'Subject' => 'required',
            'UsersIDs'=>'required',
            'TaskStatus'=>'required'
        );
        $messages = array(
            'UsersIDs.required' => 'Assign To field is required.'
        );
        $validator = Validator::make($data, $rules, $messages);
        if ($validator->fails()) {
            return generateResponse($validator->errors(),true);
        }
        $data['DueDate'] = isset($data['StartTime']) && !empty($data['StartTime'])?$data['DueDate'].' '.$data['StartTime']:$data['DueDate'];
        $Task_view = isset($data['Task_view'])?1:0;
        unset($data['StartTime']);
		unset($data['scrol']);
        unset($data['Task_view']);

        try {

            $count = Task::where(['CompanyID' => $companyID, 'BoardID' => $data['BoardID'], 'BoardColumnID' => $data["TaskStatus"]])->count();
            $data['Order'] = $count;
            $data['CreatedBy'] = User::get_user_full_name();
            $data['BoardColumnID'] = $data["TaskStatus"];

            
            
			if(isset($data['AccountIDs'])){
				if(is_array($data['AccountIDs'])){
                	$taggedUser = implode(',', $data['AccountIDs']);
                	$data['AccountIDs'] = $taggedUser;
				}
            }

            unset($data["TaskStatus"]);
            unset($data['TaskID']);
			unset($data['StartTime']);
            Log::Info($data);
            $result  			=   Task::create($data);
          if(isset($data['Task_type']) && $data['Task_type']!=0)
            {
                $new_date =  date("Y-m-d H:i:s", time() + 1);
                if($data['Task_type']==3){ //notes
                    $sql = "update tblNote set created_at = '".$new_date."' , updated_at ='".$new_date."'  where NoteID ='".$data['ParentID']."'";
                    db::statement($sql);
                    Log::Info($sql);
                }

                if($data['Task_type']==2){ //email
                    $sql = "update AccountEmailLog set created_at = '".$new_date."', updated_at ='".$new_date."'  where AccountEmailLogID ='".$data['ParentID']."'";
                    db::statement($sql);
                    $Email      = AccountEmailLog::where(['AccountEmailLogID'=>$data['ParentID']])->get();
                    $Email      = $Email[0];
                    $account    = Account::find($data['AccountIDs']);
                    $JobLoggedUser = User::find(User::get_userID());
                    $Signature = '';
                    if(!empty($JobLoggedUser)){
                        if(isset($JobLoggedUser->EmailFooter) && trim($JobLoggedUser->EmailFooter) != '')
                        {
                            $Signature = $JobLoggedUser->EmailFooter;
                        }
                    }

                    $extra      = ['{{FirstName}}','{{LastName}}','{{Email}}','{{Address1}}','{{Address2}}','{{Address3}}','{{City}}','{{State}}','{{PostCode}}','{{Country}}','{{Signature}}'];
                    $replace    = [$account->FirstName,$account->LastName,$account->Email,$account->Address1,$account->Address2,$account->Address3,$account->City,$account->State,$account->PostCode,$account->Country,$Signature];

                    $Email['extra'] = $extra;
                    $Email['replace'] = $replace;
                    $Email['AttachmentPaths'] = unserialize($Email['AttachmentPaths']);
                    $Email['cc'] = $Email['Cc'];
                    $Email['bcc'] = $Email['Bcc'];
                    $Email['address']   =   $Email['Emailfrom'];
                    $Email['name']   =  $Email['CreatedBy'];

                    $status = sendMail('emails.account.AccountEmailSend', $Email);
                }
            }
		   $sql 				= 	"CALL `prc_GetTasksSingle`(".$result['TaskID'].")";
		   $result  			= 	DB::select($sql);	
        }
        catch (\Exception $ex){
            Log::info($ex);
            return $this->response->errorInternal($ex->getMessage());
        }

        if($Task_view) {
            return generateResponse('Task Successfully Created'.$message);
        }
        else {
            return generateResponse($message,false,false,$result);
        }
    }


    /**
     * Update the specified resource in storage.
     * PUT /dealboard/{id}/update
     *
     * @param  int  $id
     * @return Response
     */
    //@clarification:will not update attribute against leads
    public function updateTask($id)
    {
        if( $id > 0 ) {
            $data = Input::all();
            $companyID = User::get_companyID();
            $data["CompanyID"] = $companyID;
            $rules = array(
                'CompanyID' => 'required',
                'Subject' => 'required',
                'UsersIDs'=>'required',
                'TaskStatus'=>'required'
            );

            $messages = array(
                'UsersIDs' => 'User field is required.',
            );
            $validator = Validator::make($data, $rules,$messages);

            if ($validator->fails()) {
                return generateResponse($validator->errors(),true);
            }
            try {
                //Tags::insertNewTags(['tags' => $data['Tags'], 'TagType' => Tags::Task_tag]);
                if(isset($data['TaggedUser'])) {
                    $taggedUser = implode(',', $data['TaggedUser']);
                    $data['TaggedUser'] = $taggedUser;
                }
                $data['BoardColumnID'] = $data["TaskStatus"];
                $data['DueDate'] = isset($data['StartTime']) && !empty($data['StartTime'])?$data['DueDate'].' '.$data['StartTime']:$data['DueDate'];
                $data['Priority'] = isset($data['Priority'])?1:0;
                unset($data["TaskStatus"]);
                unset($data['TaskID']);
                unset($data['StartTime']);
                Task::where(['TaskID' => $id])->update($data);
            } catch (\Exception $ex){
                Log::info($ex);
                return $this->response->errorInternal($ex->getMessage());
            }
            return generateResponse('',false,false,$data);
        }else {
            return generateResponse('Task id is missing',true,true);
        }
    }

    function updateColumnOrder($id){
        $data = Input::all();
        try {
            $cardorder = explode(',', $data['cardorder']);
            foreach ($cardorder as $index => $key) {
                Task::where(['TaskID' => $key])->update(['Order' => $index,'BoardColumnID'=>$data['BoardColumnID']]);
            }
            return generateResponse('Task Updated');
        }
        catch(Exception $ex){
            return $this->response->errorInternal($ex->getMessage());
        }
    }

    public function getDropdownLeadAccount($accountLeadCheck){
        $data = Input::all();
        $filter = [];
        if(!empty($data['UserID'])){
            $filter['Owner'] = $data['UserID'];
        }
        if($accountLeadCheck==1) {
            return json_encode(['result'=>Lead::getLeadList($filter)]);
        }else {
            return json_encode(['result'=>Account::getAccountList($filter)]);
        }
    }

    public function getPriority(){
        $Priorities = Task::$periority;
        return generateResponse('',false,false,$Priorities);
    }

    public function get_allowed_extensions(){
        $allowed     =  getenv("CRM_ALLOWED_FILE_UPLOAD_EXTENSIONS");
        $allowedextensions   =  explode(',',$allowed);
        $allowedextensions   =  array_change_key_case($allowedextensions);
        return generateResponse('',false,false,$allowedextensions);
    }

}