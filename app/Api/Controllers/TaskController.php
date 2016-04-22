<?php
namespace Api\Controllers;

use Api\Model\DataTableSql;
use Api\Model\Task;
use Api\Model\User;
use Api\Model\Tags;
use Api\Model\Lead;
use Api\Model\CRMBoardColumn;
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
            return $this->response->error('fetch Type field is required','432');
        }
        $data['AccountOwner'] = empty($data['AccountOwner'])?0:$data['AccountOwner'];
        $data['AccountID'] = empty($data['AccountID'])?0:$data['AccountID'];
        $data['Priority'] = empty($data['Priority'])?0:$data['Priority'];
        $data['TaskStatus'] = empty($data['TaskStatus'])?0:$data['TaskStatus'];
        if(isset($data['DueDateFilter'])){
            $data['DueDate'] = $data['DueDateFilter']!=3?$data['DueDateFilter']:$data['DueDate'];
        }
        if($data['fetchType']=='Grid') {
            $rules['iDisplayStart'] = 'required|Min:1';
            $rules['iDisplayLength'] = 'required';
            $rules['iDisplayLength'] = 'required';
            $rules['sSortDir_0'] = 'required';
            $validator = Validator::make($data, $rules);
            if ($validator->fails()) {
                return $this->response->error($validator->errors(), '432');
            }

            $columns = ['Subject', 'DueDate', 'Status', 'Priority','UserID'];
            $sort_column = $columns[$data['iSortCol_0']];

            $query = "call prc_GetTasksGrid (" . $companyID . ", " . $id . ",'" . $data['taskName'] . "','" . $data['AccountOwner'] . "', " . $data['Priority'] .",'".$data['DueDate']. "',".$data['TaskStatus'].",".(ceil($data['iDisplayStart'] / $data['iDisplayLength'])) . " ," . $data['iDisplayLength'] . ",'" . $sort_column . "','" . $data['sSortDir_0'] . "')";
            Log::Info($query);
            try {
                $result = DataTableSql::of($query)->make();
                $reponse_data = ['status' => 'success', 'data' => ['result' => $result], 'status_code' => 200];
                return API::response()->array($reponse_data)->statusCode(200);
            }catch (\Exception $ex){
                Log::info($ex);
                return $this->response->errorInternal($ex->getMessage());
            }
        }elseif($data['fetchType']=='Board') {
            $query = "call prc_GetTasksBoard (" . $companyID . ", " . $id . ",'" . $data['taskName'] . "','" . $data['AccountOwner'] . "', " . $data['Priority'].",'".$data['DueDate']."',".$data['TaskStatus'].")";
            try{
                $result = DB::select($query);
                $boardsWithITask = [];
                $columns = [];
                foreach($result as $row){
                    $columns[$row->BoardColumnID] = ['Name'=>$row->BoardColumnName/*,'Height'=>$row->Height,'Width'=>$row->Width*/];
                    if(!empty($row->Subject)) {
                        $users = [];
                        if(!empty($row->TaggedUser)){
                            $users = User::whereIn('UserID',explode(',',$row->TaggedUser))->select(['FirstName','LastName','UserID'])->get();
                        }
                        $boardsWithITask[$row->BoardColumnID][] = ['TaggedUser'=>$users,'task'=>$row];
                    }else{
                        $boardsWithITask[$row->BoardColumnID][] = '';
                    }
                }
                $return['columns'] = $columns;
                $return['boardsWithITask'] = $boardsWithITask;
                $reponse_data = ['status' => 'success', 'data' => ['result' => $return], 'status_code' => 200];
                return API::response()->array($reponse_data)->statusCode(200);
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
        $reponse_data = ['status' => 'success', 'data' => ['result' => $attachementPaths], 'status_code' => 200];
        return API::response()->array($reponse_data)->statusCode(200);
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
                return $this->response->errorBadRequest($ext." file type is not allowed. Allowed file types are ".$allowed);
            }
        }
        $taskattachment = uploaded_File_Handler($data['file']);
        $AttachmentPaths = Task::where(['TaskID'=>$id])->pluck('AttachmentPaths');
        $taskattachments = [];

        foreach ($taskattachment as $attachment) {
            $ext = $ext = $attachment['Extension'];
            $originalfilename = $attachment['fileName'];
            $file_name = "TaskAttachment_" . Uuid::uuid() . '.' . $ext;
            $amazonPath = AmazonS3::generate_upload_path(AmazonS3::$dir['TASK_ATTACHMENT']);
            $destinationPath = getenv("UPLOAD_PATH") . '/' . $amazonPath;
            rename_win($attachment['file'],$destinationPath.$file_name);
            if (!AmazonS3::upload($destinationPath . $file_name, $amazonPath)) {
                return $this->response->errorBadRequest('Failed to upload');
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
                return API::response()->array(['status' => 'success', 'message' => 'Attachment saved successfully', 'status_code' => 200])->statusCode(200);
            }else{
                return $this->response->errorInternal('Problem saving attachment.');
            }
        } else{
            return $this->response->errorNotFound('No attachment found');
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
            return API::response()->array(['status' => 'success', 'message' => 'Attachment deleted successfully', 'status_code' => 200])->statusCode(200);
        }else{
            return $this->response->errorNotFound('No attachment found');
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
            'TaskStatus'=>'required',
            'DueDate'=>'required',
            'Priority'=>'required'
        );
        $messages = array(
            'UsersIDs.required' => 'Assign To field is required.',
            'DueDate.required' => 'Due Date is required',
        );
        $validator = Validator::make($data, $rules, $messages);
        if ($validator->fails()) {
            return $this->response->error($validator->errors(),'432');
        }
		
		unset($data['scrol']);
        try {

            //Add new tags to db against task
            //Tags::insertNewTags(['tags' => $data['Tags'], 'TagType' => Tags::Task_tag]);
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
            Log::Info($data);
            $result  			=   Task::create($data);
		   $sql 				= 	"CALL `prc_GetTasksSingle`(".$result['TaskID'].")";
		   $result  			= 	DB::select($sql);	
        }
        catch (\Exception $ex){
            Log::info($ex);
            return $this->response->errorInternal($ex->getMessage());
        }
	
	    return API::response()->array(['status' => 'success' , 'data' => ['result' => $result],  'message' => 'Task Successfully Created'.$message, 'status_code' => 200])->statusCode(200);
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
                'TaskStatus'=>'required',
                'DueDate'=>'required',
                'Priority'=>'required'
            );

            $messages = array(
                'UsersIDs' => 'User field is required.',
            );
            $validator = Validator::make($data, $rules,$messages);

            if ($validator->fails()) {
                return $this->response->error($validator->errors(),'432');
            }
            try {
                //Tags::insertNewTags(['tags' => $data['Tags'], 'TagType' => Tags::Task_tag]);
                if(isset($data['TaggedUser'])) {
                    $taggedUser = implode(',', $data['TaggedUser']);
                    $data['TaggedUser'] = $taggedUser;
                }
                if(isset($data['UsersIDs'])) {
                    $taggedUser = implode(',', $data['UsersIDs']);
                    $data['UsersIDs'] = $taggedUser;
                }
                if(isset($data['AccountIDs'])) {
                    $taggedUser = implode(',', $data['AccountIDs']);
                    $data['AccountIDs'] = $taggedUser;
                }
                $data['BoardColumnID'] = $data["TaskStatus"];
                unset($data["TaskStatus"]);
                unset($data['TaskID']);
                Log::info($data);
                Task::where(['TaskID' => $id])->update($data);
            } catch (\Exception $ex){
                Log::info($ex);
                return $this->response->errorInternal($ex->getMessage());
            }
            return API::response()->array(['status' => 'success', 'message' => $data, 'status_code' => 200])->statusCode(200);
        }else {
            return $this->response->errorBadRequest('Task id is missing');
        }
    }

    function updateColumnOrder($id){
        $data = Input::all();
        try {
            $cardorder = explode(',', $data['cardorder']);
            foreach ($cardorder as $index => $key) {
                Task::where(['TaskID' => $key])->update(['Order' => $index,'BoardColumnID'=>$data['BoardColumnID']]);
            }
            return API::response()->array(['status' => 'success', 'message' => 'Task Updated', 'status_code' => 200])->statusCode(200);
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
        $reponse_data = ['status' => 'success', 'data' => ['result' => $Priorities], 'status_code' => 200];
        return API::response()->array($reponse_data)->statusCode(200);
    }

}