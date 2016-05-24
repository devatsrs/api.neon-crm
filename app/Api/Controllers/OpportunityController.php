<?php
namespace Api\Controllers;

use Api\Model\Opportunity;
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

class OpportunityController extends BaseController {

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

    public function getOpportunities($id){
        if(empty($id) || !is_numeric($id) || $id==0 ) {
            return generateResponse('Board id is not provided or not valid',true,true);
        }
        $companyID = User::get_companyID();
        $data = Input::all();
        $defaultSelectd = implode(',',Opportunity::$defaultSelectedStatus);
        $data['account_owners'] = isset($data['account_owners'])?empty($data['account_owners'])?0:$data['account_owners']:0;
        $data['AccountID'] = isset($data['AccountID'])?empty($data['AccountID'])?0:$data['AccountID']:0;
        $data['opportunityName'] = isset($data['opportunityName'])?empty($data['opportunityName'])?'':$data['opportunityName']:'';
        $data['Tags'] = isset($data['Tags'])?empty($data['Tags'])?'':$data['Tags']:'';
        $data['Status'] = isset($data['Status'])?empty($data['Status'])?$defaultSelectd:implode(',',$data['Status']):$defaultSelectd;
        if(isset($data['opportunityClosed']) && $data['opportunityClosed']==Opportunity::Close){
            $data['Status'] = Opportunity::Close;
        }
        $query = "call prc_GetOpportunities (".$companyID.", ".$id.",'".$data['opportunityName']."',"."'".$data['Tags']."',".$data['account_owners'].", ".$data['AccountID'].",'".$data['Status']."')";
        try{
            $result = DB::select($query);
            $columnsWithOpportunities = [];
            $columns = [];
            foreach($result as $row){
                $columns[$row->BoardColumnID] = ['Name'=>$row->BoardColumnName,'Height'=>$row->Height,'Width'=>$row->Width];
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

        $allowed = getenv("CRM_ALLOWED_FILE_UPLOAD_EXTENSIONS");
        $allowedextensions = explode(',',$allowed);
        $allowedextensions = array_change_key_case($allowedextensions);
        foreach ($opportunityattachment as $attachment) {
            $ext = $attachment['fileExtension'];
            if (!in_array(strtolower($ext), $allowedextensions)) {
                return generateResponse($ext." file type is not allowed. Allowed file types are ".$allowed,true,true);
            }
        }
        $opportunityattachment = uploaded_File_Handler($data['file']);
        $AttachmentPaths = Opportunity::where(['OpportunityID'=>$id])->pluck('AttachmentPaths');
        $opportunityattachments = [];

        foreach ($opportunityattachment as $attachment) {
            $ext = $ext = $attachment['Extension'];
            $originalfilename = $attachment['fileName'];
            $file_name = "OpportunityAttachment_" . Uuid::uuid() . '.' . $ext;
            $amazonPath = \App\AmazonS3::generate_upload_path(\App\AmazonS3::$dir['OPPORTUNITY_ATTACHMENT']);
            $destinationPath = getenv("UPLOAD_PATH") . '/' . $amazonPath;
            rename_win($attachment['file'],$destinationPath.$file_name);
            if (!\App\AmazonS3::upload($destinationPath . $file_name, $amazonPath)) {
                return generateResponse('Failed to upload',true,true);
            }
            $fullPath = $amazonPath . $file_name;
            $opportunityattachments[] = ['filename' => $originalfilename, 'filepath' => $fullPath];
        }

        if(count($opportunityattachments)>0){
            $AttachmentPaths = json_decode($AttachmentPaths,true);
            if(count($AttachmentPaths)>0) {
                $opportunityattachments = array_merge($AttachmentPaths , $opportunityattachments);
            }
            $opportunity_data['AttachmentPaths'] = json_encode($opportunityattachments);
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
            unset($attachmentPaths[$attachmentID]);
            $data = ['AttachmentPaths'=>json_encode($attachmentPaths)];

            try{
                Opportunity::where(['opportunityID'=>$opportunityID])->update($data);
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
            'Phone'=>'required',
            'BoardID'=>'required',
        );

        if($data['leadcheck']=='No') {
            if($data['leadOrAccount'] == 'Account'){
                $rules['Title']='required';
            }
            $rules['Company'] = 'required|unique:tblAccount,AccountName,NULL,CompanyID,CompanyID,' . $companyID . '';
        }
        $validator = Validator::make($data, $rules);
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

            unset($data['OppertunityID']);
            unset($data['leadcheck']);
            unset($data['leadOrAccount']);

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

            $companyID = User::get_companyID();
            $data["CompanyID"] = $companyID;
            $rules = array(
                'CompanyID' => 'required',
                'OpportunityName' => 'required',
                'Company'=>'required',
                'FirstName'=>'required',
                'LastName'=>'required',
                'Email'=>'required',
                'Phone'=>'required',
                'BoardID'=>'required'
            );
            $validator = Validator::make($data, $rules);

            if ($validator->fails()) {
                generateResponse($validator->errors(),true);
            }
            try {
                $data['ClosingDate'] = '';
                if(isset($data['TaggedUsers']) && !empty($data['TaggedUsers'])) {
                    Tags::insertNewTags(['tags' => $data['Tags'], 'TagType' => Tags::Opportunity_tag]);
                    $taggedUsers = implode(',', $data['TaggedUsers']);
                    $data['TaggedUsers'] = $taggedUsers;
                }
                if(isset($data['opportunityClosed']) && $data['opportunityClosed']==Opportunity::Close){
                    $data['ClosingDate'] = date('Y-m-d H:i:s');
                    $data['Status'] = Opportunity::Close;
                }else{
                    if(empty($data['Status'])){
                        $data['Status'] = Opportunity::Open;
                    }
                }
                unset($data['opportunityClosed']);
                unset($data['OpportunityID']);
                $Opportunity = Opportunity::find($id);
                if($Opportunity->BoardID!=$data['BoardID']){
                    $data["BoardColumnID"] = CRMBoardColumn::where(['BoardID' => $data['BoardID'], 'Order' => 0])->pluck('BoardColumnID');
                }
                $Opportunity->update($data);
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

}