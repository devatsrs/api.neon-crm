<?php
namespace Api\Controllers;

use Api\Model\Company;
use App\CalendarAPI;
use Dingo\Api\Http\Request;
use Api\Model\DataTableSql;
use Api\Model\Task;
use Api\Model\User;
use Api\Model\Tags;
use Api\Model\Lead;
use Api\Model\CRMBoardColumn;
use Api\Model\AccountEmailLog;
use Api\Model\Note;
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
    
    public function __construct(Request $request)
    {
        $this->middleware('jwt.auth');
        Parent::__Construct($request);
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
        $data['taskClosed'] = isset($data['taskClosed'])?empty($data['taskClosed']) || $data['taskClosed']=='false'?0:$data['taskClosed']:0;
        if(isset($data['DueDateFilter'])){
            $data['DueDateFrom'] = $data['DueDateFilter']!=Task::CustomDate?$data['DueDateFilter']:(isset($data['DueDateFrom'])?$data['DueDateFrom']:'');
            $data['DueDateTo'] = $data['DueDateFilter']!=Task::CustomDate?$data['DueDateFilter']:(isset($data['DueDateTo'])?$data['DueDateTo']:'');
        }
        if($data['fetchType']=='Grid') {
            $rules['iDisplayStart'] = 'required|Min:1';
            $rules['iDisplayLength'] = 'required';
            $rules['sSortDir_0'] = 'required';
            $validator = Validator::make($data, $rules);
            if ($validator->fails()) {
                return generateResponse($validator->errors(),true);
            }

            $columns = ['Subject', 'DueDate', 'Status','UserID','RelatedTo'];
            $sort_column = $columns[$data['iSortCol_0']];

            $query = "call prc_GetTasksGrid (" . $companyID . ", " . $id . ",'" . $data['taskName'] . "'," . $data['AccountOwner'] . ", " . $data['AccountIDs'] . ", " . $data['Priority'] .",'".$data['DueDateFrom']."','".$data['DueDateTo']."',".$data['TaskStatus'].",".$data['taskClosed'].",".(ceil($data['iDisplayStart'] / $data['iDisplayLength'])) . " ," . $data['iDisplayLength'] . ",'" . $sort_column . "','" . $data['sSortDir_0'] . "')";  
            try {
                $result = DataTableSql::of($query)->make();
                return generateResponse('',false,false,$result);
            }catch (\Exception $ex){
                Log::info($ex);
                return $this->response->errorInternal($ex->getMessage());
            }
        }elseif($data['fetchType']=='Board') {
            $query = "call prc_GetTasksBoard (" . $companyID . ", " . $id . ",'" . $data['taskName'] . "'," . $data['AccountOwner'] . ", " . $data['AccountIDs'] . ", " . $data['Priority'] .",'".$data['DueDateFrom']."','".$data['DueDateTo']."',".$data['TaskStatus'].",".$data['taskClosed'].")";

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
        $taskattachments = $data['file'];

        if($taskattachments){
            $AttachmentPaths = Task::where(['TaskID'=>$id])->pluck('AttachmentPaths');
            $AttachmentPaths = json_decode($AttachmentPaths,true);
            $taskattachments = json_decode($taskattachments,true);
            if(count($AttachmentPaths)>0) {
                $taskattachments = array_merge($AttachmentPaths , $taskattachments);
            }
            $task_data['AttachmentPaths'] = json_encode($taskattachments);
            $result = Task::where(['TaskID'=>$id])->update($task_data);
            if($result){
                return generateResponse('Attachment saved successfully');
            }else{
                return generateResponse('Problem saving attachment',true,true);
            }
        } else{
            return generateResponse('No attachment found',true,true);
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
 
    public function GetTask(){
        $data = Input::all();

        $rules['TaskID'] = 'required';
        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            return generateResponse($validator->errors(),true);
        }
        try {
           $sql 				= 	"CALL `prc_GetTasksSingle`(".$data['TaskID'].")";
		   $result  			= 	DB::select($sql);
        } catch (\Exception $e) {
            Log::info($e);
            return $this->response->errorInternal($e->getMessage());
        }
         return generateResponse('Task Successfully Created',false,false,$result[0]);
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
		$TaskBoardUrl	= '';
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
		
       // $data['DueDate'] = (isset($data['StartTime']) && !empty($data['StartTime']) && isset($data['DueDate']) && !empty($data['DueDate']))?$data['DueDate'].' '.$data['StartTime']:$data['DueDate'];
		//$data['DueDate']	   =	!empty($data['DueDate'])?$data['DueDate']:'0000-00-00 00:00:00';
		   $duedate   = '0000-00-00'; $Starttime = '00:00:00'; 
		   if(isset($data['DueDate']) && !empty($data['DueDate'])){
				$duedate = $data['DueDate'];
		   }			   
		   if(isset($data['StartTime']) && !empty($data['StartTime'])){
				$Starttime = $data['StartTime'];
		   }		   
			if($duedate  == '0000-00-00'){
				unset($data['DueDate']);
				unset($data['StartTime']);
			}else{ 
				if($Starttime  == '00:00:00'){
					$data['DueDate'] = $data['DueDate'].' 23:59:59';
				}else{
					$data['DueDate'] = $data['DueDate'].' '.$data['StartTime'];
				}
			}
		
        $Task_view = isset($data['Task_view'])?1:0;
		$data = cleanarray($data,['StartTime','scrol','Task_view']);
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
			
			if(isset($data['TaskBoardUrl']) && $data['TaskBoardUrl']!=''){
					$TaskBoardUrl	=	$data['TaskBoardUrl'];
			}

			$data = cleanarray($data,['TaskStatus','TaskID','StartTime','TaskBoardUrl']);

            $result  				=   Task::create($data);
			$data['TaskBoardUrl']	=	$TaskBoardUrl;
			SendTaskMail($data); //send task email to assign user

            if(isset($data['DueDate']) && !empty($data['DueDate'])) {
                /**
                 * Creating Calendar Event Data
                 */
                $attendees = Task::get_all_attendees_email($result);
                $timezone = Company::getCompanyField($companyID, "TimeZone");
                $StartDate = date("Y-m-d H:i:s",strtotime($data['DueDate']));
                $options = [
                    "timezone" => $timezone,
                    "start_date" => $StartDate,
                    "due_date" => $data['DueDate'],
                    "description" => nl2br($data["Description"]),
                    "attendees" => $attendees,
                    "subject" => $data['Subject'],
                ];

               /* $response = $this->add_edit_calendar_event($options);

                //Update Event ID on DB to update.
                if (isset($response["event_id"]) && isset($response["change_key"]) && !empty($response["event_id"]) && !empty($response["change_key"])) {

                    $result->update(["CalendarEventID" => json_encode($response)]);
                }*/
            }


          if(isset($data['Task_type']) && $data['Task_type']!=0)
            {
                $new_date =  date("Y-m-d H:i:s", time() + 1);
                if($data['Task_type']==Task::Note){ //notes
					Note::find($data['ParentID'])->update(['created_at'=>$new_date,'updated_at'=>$new_date]);
                }
				
                if($data['Task_type']==Task::Mail) //email
                {
                    $Email      = AccountEmailLog::where(['AccountEmailLogID'=>$data['ParentID']])->get();
                    $Email      = $Email[0];
                    $account    = Account::find($data['AccountIDs']);
                    $JobLoggedUser = User::find(User::get_userID());

                    $replace_array = Account::create_replace_array($account,array(),$JobLoggedUser);
                    $Email['Message'] = template_var_replace($Email['Message'],$replace_array);


                    $Email['AttachmentPaths'] 	= 	unserialize($Email['AttachmentPaths']);
                    $Email['cc'] 				= 	$Email['Cc'];
                    $Email['bcc'] 				= 	$Email['Bcc'];
                    $Email['address']   		=   $Email['Emailfrom'];
                    $Email['name']   			=  	$Email['CreatedBy'];

                    $status 					= 	sendMail('emails.template', $Email);
					$message_id 				=   isset($status['message_id'])?$status['message_id']:"";
					AccountEmailLog::find($data['ParentID'])->update(["created_at"=>$new_date,"updated_at"=>$new_date,"MessageID"=>$message_id]);
                }
            }
		   $sql 				= 	"CALL `prc_GetTasksSingle`(".$result['TaskID'].")";
		   $result  			= 	DB::select($sql);	
        }
        catch (\Exception $ex){
            Log::info($ex);
            return $this->response->errorInternal($ex->getMessage()); 
        }

        if($Task_view){
            return generateResponse('Task Successfully Created'.$message);
        }
        else {
            return generateResponse('Task Successfully Created',false,false,$result);
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
			$Task =  Task::find($id);
			$old_task_data['TaggedUsers']  	=	$Task->TaggedUsers;
			$old_task_data['CreatedBy']  	=	$Task->CreatedBy;
			$old_task_data['UsersIDs']  	=	$Task->UsersIDs;			
            $CalendarEventID = $Task->CalendarEventID;
			$required_data = 0;
            $data = Input::all();
            $companyID = User::get_companyID();
			$TaskBoardUrl=	'';
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
                if(isset($data['TaggedUsers'])) {
                    $taggedUser = implode(',', $data['TaggedUsers']);
                    $data['TaggedUsers'] = $taggedUser;
                }else{
                    $data['TaggedUsers'] = '';
                }
                if(isset($data['taskClosed']) && $data['taskClosed']==Task::Close){
                    $data['ClosingDate'] = date('Y-m-d H:i:s');
                    $data['taskClosed'] = Task::Close;
                }else{
                    $data['taskClosed'] = Task::Open;
                }
                $data['BoardColumnID'] = $data["TaskStatus"];

                // $data['DueDate'] = isset($data['StartTime']) && !empty($data['StartTime'])?$data['DueDate'].' '.$data['StartTime']:$data['DueDate'];

			    $duedate   = '0000-00-00'; $Starttime = '00:00:00';
			   if(isset($data['DueDate']) && !empty($data['DueDate'])){
			 		$duedate = $data['DueDate'];
			   }			   
			   if(isset($data['StartTime']) && !empty($data['StartTime'])){
			 		$Starttime = $data['StartTime'];
			   }
			   
				if($duedate  == '0000-00-00'){
					unset($data['DueDate']);
					unset($data['StartTime']);
					//$data['DueDate'] = "''";
				}else{ 
					if($Starttime  == '00:00:00'){
						$data['DueDate'] = $data['DueDate'].' 23:59:59';
					}else{
						$data['DueDate'] = $data['DueDate'].' '.$data['StartTime'];
					}
				}
				//$data['DueDate']	   =	!empty($data['DueDate'])?$data['DueDate']:"'0000-00-00 00:00:00'";
                $data['Priority'] = isset($data['Priority'])?1:0;
				if(isset($data['required_data']) && $data['required_data']!=''){
					$required_data = 1;
				}
				if(isset($data['TaskBoardUrl']) && $data['TaskBoardUrl']!=''){
					$TaskBoardUrl	=	$data['TaskBoardUrl'];
				}
				
				$data = cleanarray($data,['TaskStatus','TaskID','StartTime','TaskBoardUrl','required_data']);
                $Task->update($data);
				$data['TaskBoardUrl']	=	$TaskBoardUrl;				
				$status = SendTaskMailUpdate($data,$old_task_data,'Task'); //send task email to assign user


                if(isset($data['DueDate']) && !empty($data['DueDate'])) {

                    /**
                     * Creating Calendar Event Data
                     */
                    $attendees = Task::get_all_attendees_email($Task);
                    $timezone = Company::getCompanyField($companyID, "TimeZone");
                    $StartDate = date("Y-m-d H:i:s",strtotime($data['DueDate']));
                    $options = [
                        "timezone" => $timezone,
                        "start_date" => $StartDate,
                        "due_date" => $data['DueDate'],
                        "description" => nl2br($data["Description"]),
                        "attendees" => $attendees,
                        "subject" => $data['Subject'],
                    ];

                    if (!empty($CalendarEventID)) {

                        $CalendarEventIDJson = json_decode($CalendarEventID, true);
                        if (isset($CalendarEventIDJson["event_id"]) && isset($CalendarEventIDJson["change_key"]) && !empty($CalendarEventIDJson["event_id"]) && !empty($CalendarEventIDJson["change_key"])) {

                            $options["event_id"] = $CalendarEventIDJson["event_id"];
                            $options["change_key"] = $CalendarEventIDJson["change_key"];

                        }
                    }

                    $response = $this->add_edit_calendar_event($options);

                    //Update Event ID on DB to update.
                    if (isset($response["event_id"]) && isset($response["change_key"]) && !empty($response["event_id"]) && !empty($response["change_key"])) {

                        $Task->update(["CalendarEventID" => json_encode($response)]);
                    }
                }

            } catch (\Exception $ex){
                Log::info($ex);
                return $this->response->errorInternal($ex->getMessage());
            }
			if($required_data==1){
				$sql 				= 	"CALL `prc_GetTasksSingle`(".$id.")";
		   		$result  			= 	DB::select($sql);
				return generateResponse('Task Successfully Created',false,false,$result);	
			}else{
            	return generateResponse('',false,false,$data);
			}
        }else {
            return generateResponse('Task id is missing',true,true);
        }

    }
	
	 public function DeleteTask(){
        $data = Input::all();

        $rules['TaskID'] = 'required';
        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            return generateResponse($validator->errors(),true);
        }

        try{

            $CalendarEventID = Task::where(['TaskID'=>$data['TaskID']])->pluck("CalendarEventID");
            if (!empty($CalendarEventID)) {

                $CalendarEventIDJson = json_decode($CalendarEventID, true);
                if (isset($CalendarEventIDJson["event_id"]) && isset($CalendarEventIDJson["change_key"]) && !empty($CalendarEventIDJson["event_id"]) && !empty($CalendarEventIDJson["change_key"])) {

                    $options["event_id"] = $CalendarEventIDJson["event_id"];
                    $options["change_key"] = $CalendarEventIDJson["change_key"];

                    $response = $this->delete_calendar_event($options);
                }
            }

            Task::where(['TaskID'=>$data['TaskID']])->delete();

        }catch (\Exception $ex){
            Log::info($ex);
            return $this->response->errorInternal($ex->getMessage());
        }
        return generateResponse('successfull');
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
	
	

    public function getAttachment($taskID,$attachmentID){
        if(intval($taskID)>0) {
            $task = Task::find($taskID);
            $attachments = json_decode($task->AttachmentPaths,true);
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

    /**
     * Create update Calendar Event for Task
     */
    public function add_edit_calendar_event( $options = array() ){

        Log::info("Calendar Event Options");
        Log::info($options);

        $calendar_request = new CalendarAPI();

        if(isset($options["event_id"]) && isset($options["change_key"]) && !empty($options["event_id"]) && !empty($options["change_key"]) ) {

            $response = $calendar_request->update_event($options);
        } else {

            $response = $calendar_request->create_event($options);
        }

        if(isset($response["event_id"]) && isset($response["change_key"]) && !empty($response["event_id"]) && !empty($response["change_key"]) ) {

            Log::info("Calendar Response");
            Log::info(print_r($response,true));
        }

        return $response;
    }

    /** Delete calendar event.
     * @param array $options
     * @return array|bool
     */
    public function delete_calendar_event( $options = array() ){

        Log::info("Calendar Event Options");
        Log::info($options);

        $calendar_request = new CalendarAPI();

        if(isset($options["event_id"]) && isset($options["change_key"]) && !empty($options["event_id"]) && !empty($options["change_key"]) ) {

            $response = $calendar_request->delete_event($options);
        } else {

            Log::info("No calendar event id found");
         }

        if(isset($response["event_id"]) && isset($response["change_key"]) && !empty($response["event_id"]) && !empty($response["change_key"]) ) {

            Log::info("Calendar Response");
            Log::info(print_r($response,true));
        }

        return $response;

    }
}