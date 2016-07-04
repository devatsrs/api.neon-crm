<?php
namespace Api\Controllers;

use Api\Model\CompanyConfiguration;
use App\AmazonS3;
use App\RemoteSSH;
use Dingo\Api\Http\Request;
use Api\Model\Account;
use Api\Model\Opportunity;
use Api\Model\User;
use Api\Model\Tags;
use Api\Model\Lead;
use Api\Model\CRMBoardColumn;
use App\Http\Requests;
use Faker\Provider\Uuid;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class OpportunityController extends BaseController {

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

    public function getOpportunities($id){
        if(empty($id) || !is_numeric($id) || $id==0 ) {
            return generateResponse('Board id is not provided or not valid',true,true);
        }
        $companyID = User::get_companyID();
        $data = Input::all();
        $data['account_owners'] = isset($data['account_owners'])?empty($data['account_owners'])?0:$data['account_owners']:0;
        $data['AccountID'] = isset($data['AccountID'])?empty($data['AccountID'])?0:$data['AccountID']:0;
        $data['opportunityName'] = isset($data['opportunityName'])?empty($data['opportunityName'])?'':$data['opportunityName']:'';
        $data['Tags'] = isset($data['Tags'])?empty($data['Tags'])?'':$data['Tags']:'';
        $data['Status'] = isset($data['Status'])?empty($data['Status'])?'':implode(',',$data['Status']):'';
        if(isset($data['opportunityClosed']) && $data['opportunityClosed']==Opportunity::Close){
            $data['Status'] = Opportunity::Close;
        }
        $query = "call prc_GetOpportunities (".$companyID.", ".$id.",'".$data['opportunityName']."',"."'".$data['Tags']."',".$data['account_owners'].", ".$data['AccountID'].",'".$data['Status']."')";
        try{
            $result = DB::select($query);
            $columnsWithOpportunities = [];
            $columns = [];
            foreach($result as $row){
                $columns[$row->BoardColumnID] = ['Name'=>$row->BoardColumnName];
                if(!empty($row->OpportunityName)) {
                    $users = [];
                    if(!empty($row->TaggedUsers)){
                        $users = User::whereIn('UserID',explode(',',$row->TaggedUsers))->select(['FirstName','LastName','UserID','Color'])->get();
                    }
                    $columnsWithOpportunities[$row->BoardColumnID][] = ['TaggedUsers'=>$users,'opportunity'=>$row];
                }else{
                    $columnsWithOpportunities[$row->BoardColumnID][] = '';
                }
            }
            $return['columns'] = $columns;
            $return['columnsWithOpportunities'] = $columnsWithOpportunities;
            return generateResponse('',false,false,$return);
        }catch (\Exception $ex){
            Log::info($ex);
            return $this->response->errorInternal($ex->getMessage());
        }
    }

    public function getAttachments($id){
        $attachementPaths = Opportunity::where(['OpportunityID'=>$id])->pluck('AttachmentPaths');
        if(!empty($attachementPaths)){
            $attachementPaths = json_decode($attachementPaths);
        }
        return generateResponse('',false,false,$attachementPaths);
    }

    public function saveAttachment($id){
        $data = Input::all();
        $opportunityattachment = $data['file'];

        if($opportunityattachment){
            $AttachmentPaths = Opportunity::where(['OpportunityID'=>$id])->pluck('AttachmentPaths');
            $AttachmentPaths = json_decode($AttachmentPaths,true);
            $opportunityattachment = json_decode($opportunityattachment,true);
            if(count($AttachmentPaths)>0) {
                $opportunityattachment = array_merge($AttachmentPaths , $opportunityattachment);
            }
            $opportunity_data['AttachmentPaths'] = json_encode($opportunityattachment);
            $result = Opportunity::where(['OpportunityID'=>$id])->update($opportunity_data);
            if($result){
                return generateResponse('Attachment saved successfully');
            }else{
                return generateResponse('Problem saving attachment',true,true);
            }
        } else{
            return generateResponse('No attachment found',true,true);
        }
    }

    public function deleteAttachment($opportunityID,$attachmentID){
        $attachmentPaths = Opportunity::where(['OpportunityID'=>$opportunityID])->pluck('AttachmentPaths');
        if(!empty($attachmentPaths)){
            $attachmentPaths = json_decode($attachmentPaths,true);

            $delete_status = false;
            if(isset($attachmentPaths[$attachmentID]["filepath"])){

                $delete_status = AmazonS3::delete($attachmentPaths[$attachmentID]["filepath"]);
            }
			unset($attachmentPaths[$attachmentID]);
            $data = ['AttachmentPaths'=>json_encode($attachmentPaths)];

            try{

                Opportunity::where(['opportunityID'=>$opportunityID])->update($data);
                if(!$delete_status){
                    return generateResponse('Failed to delete file',true,true);
                }
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
    public function addOpportunity(){
        $data = Input::all();
        $companyID = User::get_companyID();
        $message = '';
        $data ["CompanyID"] = $companyID;
        $rules = array(
            'CompanyID' => 'required',
            'OpportunityName' => 'required',
            'Company'=>'required',
            'FirstName'=>'required',
            'LastName'=>'required',
            'Email'=>'required',
            //'Phone'=>'required',
            'BoardID'=>'required',
        );
        $messages = array(
            'BoardID.required' => 'Opportunity Board field is required.'
        );

        if($data['leadcheck']=='No') {
            if($data['leadOrAccount'] == 'Account'){
                $rules['Title']='required';
            }
            $rules['Company'] = 'required|unique:tblAccount,AccountName,NULL,CompanyID,CompanyID,' . $companyID . '';
        }
        $validator = Validator::make($data, $rules, $messages);
        if ($validator->fails()) {
            return generateResponse($validator->errors(),true);
        }
        try {
            if ($data['leadcheck'] == 'No') {
                $AccountType = $data['leadOrAccount'] == 'Lead' ? 0 : 1;
                $tobeinsert = ['CompanyID' => $companyID,
                    'Owner' => $data['UserID'],
                    'AccountName' => $data['Company'],
                    'Title' => $data['Title'],
                    'FirstName' => $data['FirstName'],
                    'LastName' => $data['LastName'],
                    'Email' => $data['Email'],
                    'Phone' => $data['Phone'],
                    'AccountType' => $AccountType,
                    'Status' => 1,
                    'created_by' => User::get_user_full_name(),
                    'created_at' => DB::raw('Now()')
                ];
                if ($AccountType == 0) {
                    $AccountID = Lead::insertGetId($tobeinsert);
                    $message = ' And lead is created successfully.';
                } else {
                    $AccountID = Account::insertGetId($tobeinsert);
                    $message = ' And Account is created successfully.';
                }
                $data['AccountID'] = $AccountID;
            }
            //Add new tags to db against opportunity
            Tags::insertNewTags(['tags' => $data['Tags'], 'TagType' => Tags::Opportunity_tag]);
            // place new opp. in first column of board
            $data["BoardColumnID"] = CRMBoardColumn::where(['BoardID' => $data['BoardID'], 'Order' => 0])->pluck('BoardColumnID');
            $count = Opportunity::where(['CompanyID' => $companyID, 'BoardID' => $data['BoardID'], 'BoardColumnID' => $data["BoardColumnID"]])->count();
            $data['Order'] = $count;
            $data["CreatedBy"] = User::get_user_full_name();
            $data['Status'] = isset($data['Status']) && !empty($data['Status'])?$data['Status']:Opportunity::Open;

			$data = cleanarray($data,['OppertunityID','leadcheck','leadOrAccount']);

            Opportunity::create($data);
        }
        catch (\Exception $ex){
            Log::info($ex);
            return $this->response->errorInternal($ex->getMessage());
        }
        return generateResponse('Opportunity Successfully Created'.$message);
    }


	/**
	 * Update the specified resource in storage.
	 * PUT /dealboard/{id}/update
	 *
	 * @param  int  $id
	 * @return Response
	 */
    //@clarification:will not update attribute against leads
    public function updateOpportunity($id)
    {
        if( $id > 0 ) {
            $data = Input::all();
			Log::info($data);
			$old_Opportunity_data = Opportunity::find($id);
            $companyID = User::get_companyID();
            $data["CompanyID"] = $companyID;
			$TaskBoardUrl=	'';
            $rules = array(
                'CompanyID' => 'required',
                'OpportunityName' => 'required',
                'Company'=>'required',
                'FirstName'=>'required',
                'LastName'=>'required',
                'Email'=>'required',
                //'Phone'=>'required',
                'BoardID'=>'required'
            );

            $messages = array(
                'BoardID.required' => 'Opportunity Board field is required.'
            );

            $validator = Validator::make($data, $rules, $messages);

            if ($validator->fails()) {
                generateResponse($validator->errors(),true);
            }
			if(isset($data['TaskBoardUrl']) && $data['TaskBoardUrl']!=''){
					$TaskBoardUrl	=	$data['TaskBoardUrl'];
				}
			unset($data['TaskBoardUrl']);
            try {
				
                $data['ClosingDate'] = '';
                if(isset($data['TaggedUsers']) && !empty($data['TaggedUsers'])) {
                    Tags::insertNewTags(['tags' => $data['Tags'], 'TagType' => Tags::Opportunity_tag]);
                    $taggedUsers = implode(',', $data['TaggedUsers']);
                    $data['TaggedUsers'] = $taggedUsers;
                }else{
                    $data['TaggedUsers'] = '';
                }
                if(isset($data['opportunityClosed']) && $data['opportunityClosed']==Opportunity::Close){
                    $data['ClosingDate'] = date('Y-m-d H:i:s');
                    $data['Status'] = Opportunity::Close;
                }else{
                    if(empty($data['Status'])){
                        $data['Status'] = Opportunity::Open;
                    }
                }

				$data = cleanarray($data,['OpportunityID','opportunityClosed']);
                $Opportunity = Opportunity::find($id);
                if($Opportunity->BoardID!=$data['BoardID']){
                    $data["BoardColumnID"] = CRMBoardColumn::where(['BoardID' => $data['BoardID'], 'Order' => 0])->pluck('BoardColumnID');
                }
                $Opportunity->update($data);
				$data['TaskBoardUrl']	=	$TaskBoardUrl;				
				SendTaskMailUpdate($data,$old_Opportunity_data,'Opportunity'); //send task email to assign user
            } catch (\Exception $ex){
                Log::info($ex);
                return $this->response->errorInternal($ex->getMessage());
            }
            return generateResponse('Opportunity Successfully Updated');
        }else {
            return generateResponse('Opportunity id is missing',true,true);
        }
    }

    function updateColumnOrder($id){
        $data = Input::all();
        try {
            $cardorder = explode(',', $data['cardorder']);
            foreach ($cardorder as $index => $key) {
                Opportunity::where(['OpportunityID' => $key])->update(['Order' => $index,'BoardColumnID'=>$data['BoardColumnID']]);
            }
            return generateResponse('Opportunity Updated');
        }
        catch(Exception $ex){
            return $this->response->errorInternal($ex->getMessage());
        }
    }

    public function getLead($id){
        $lead =  Lead::find($id);
        return generateResponse('',false,false,$lead);
    }

    public function getDropdownLeadAccount($accountLeadCheck){
        $data = Input::all();
        $filter = [];
        if(!empty($data['UserID'])){
            $filter['Owner'] = $data['UserID'];
        }
        if($accountLeadCheck==1) {
            return generateResponse('',false,false,Lead::getLeadList($filter));
        }else {
            return generateResponse('',false,false,Account::getAccountList($filter));
        }
    }

    public function getAttachment($opportunityID,$attachmentID){
        if(intval($opportunityID)>0) {
            $opportunity = Opportunity::find($opportunityID);
            $attachments = json_decode($opportunity->AttachmentPaths,true);
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