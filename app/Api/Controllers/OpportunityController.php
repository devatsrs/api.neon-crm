<?php
namespace Api\Controllers;

use Api\Model\Opportunity;
use Api\Model\User;
use Api\Model\Tags;
use Api\Model\Contact;
use Api\Model\Lead;
use Api\Model\OpportunityBoardColumn;
use Api\Model\DataTableSql;
use App\Http\Requests;
use Curl\Curl;
use Dingo\Api\Facade\API;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Mockery\CountValidator\Exception;
use Tymon\JWTAuth\Facades\JWTAuth;
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
        $companyID = User::get_companyID();
        $data = Input::all();
        $data['account_owners'] = empty($data['account_owners'])?0:$data['account_owners'];
        $data['AccountID'] = empty($data['AccountID'])?0:$data['AccountID'];
        $query = "call prc_GetOpportunities (".$companyID.", ".$id.",'".$data['opportunityName']."',".$data['account_owners'].", ".$data['AccountID'].")";
        try{
            $result = DB::select($query);
            $boradsWithOpportunities = [];
            foreach($result as $row){
                $columns[$row->OpportunityBoardColumnID] = $row->OpportunityBoardColumnName;
                if(!empty($row->OpportunityName)) {
                    $boradsWithOpportunities[$row->OpportunityBoardColumnID][] = $row;
                }else{
                    $boradsWithOpportunities[$row->OpportunityBoardColumnID][] = '';
                }
            }
            $return['columns'] = $columns;
            $return['boradsWithOpportunities'] = $boradsWithOpportunities;
            $reponse_data = ['status' => 'success', 'data' => ['result' => $return], 'status_code' => 200];
            return API::response()->array($reponse_data)->statusCode(200);
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
        $reponse_data = ['status' => 'success', 'data' => ['result' => $attachementPaths], 'status_code' => 200];
        return API::response()->array($reponse_data)->statusCode(200);
    }

    public function saveAttachment($id){
        $data = Input::all();

        $curl = new \Curl\Curl();
        $curl->setOpt(CURLOPT_POSTFIELDS, true);
        $files = new \CURLFile($data['file']['image']['name']);
        //$opportunityattachment = Input::file('file');
        $reponse_data = ['status' => 'success', 'data' => ['result' => $files], 'status_code' => 200];
        return API::response()->array($reponse_data)->statusCode(200);
        /*$AttachmentPaths = Opportunity::where(['OpportunityID'=>$id])->pluck('AttachmentPaths');
        $opportunityattachments = [];
        $opportunityattachment = Input::file('opportunityattachment');
        $allowed = getenv("CRM_ALLOWED_FILE_UPLOAD_EXTENSIONS");
        $allowedextensions = explode(',',$allowed);
        $allowedextensions = array_change_key_case($allowedextensions);
        foreach ($opportunityattachment as $attachment) {
            $ext = $attachment->getClientOriginalExtension();
            if (!in_array(strtolower($ext), $allowedextensions)) {
                return $this->response->errorBadRequest($ext." file type is not allowed. Allowed file types are ".$allowed);
            }
        }
        foreach ($opportunityattachment as $attachment) {
            $ext = $attachment->getClientOriginalExtension();
            $originalfilename = $attachment->getClientOriginalName();
            $file_name = "OpportunityAttachment_" . GUID::generate() . '.' . $ext;
            $amazonPath = AmazonS3::generate_upload_path(AmazonS3::$dir['OPPORTUNITY_ATTACHMENT']);
            $destinationPath = getenv("UPLOAD_PATH") . '/' . $amazonPath;
            $attachment->move($destinationPath, $file_name);
            if (!AmazonS3::upload($destinationPath . $file_name, $amazonPath)) {
                return $this->response->errorBadRequest('Failed to upload');
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
                return API::response()->array(['status' => 'success', 'message' => 'Attachment saved successfully', 'status_code' => 200])->statusCode(200);
            }else{
                return $this->response->errorInternal('Problem saving attachment.');
            }
        } else{
            return $this->response->errorNotFound('No attachment found');
        }*/
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
    public function addOpportunity(){
        $data = Input::all();
        $companyID = User::get_companyID();
        $message = '';
        $data ["CompanyID"] = $companyID;
        $rules = array(
            'CompanyID' => 'required',
            'OpportunityName' => 'required',
            'Company'=>'required',
            'Email'=>'required',
            'Phone'=>'required',
            'OpportunityBoardID'=>'required',
        );
        if($data['leadcheck']=='No') {
            $rules['Company'] = 'required|unique:tblAccount,AccountName,NULL,CompanyID,CompanyID,' . $companyID . '';
        }
        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            return $this->response->error($validator->errors(),'432');
        }
        if($data['leadcheck']=='No'){
            $AccountType = $data['leadOrAccount']=='Lead'?0:1;
            $tobeinsert = ['CompanyID'=>$companyID,
                            'Owner'=>$data['UserID'],
                            'AccountName'=>$data['Company'],
                            'Email'=>$data['Email'],
                            'Phone'=>$data['Phone'],
                            'AccountType'=>$AccountType,
                            'Status' => 1,
                            'created_by'=>User::get_user_full_name(),
                            'created_at'=>DB::raw('Now()')
                            ];
            if($AccountType==0){
                $AccountID = Lead::insertGetId($tobeinsert);
                $contact = ['CompanyId'=>$companyID,
                            'AccountID'=>$AccountID,
                            'FirstName'=>$data['ContactName']];
                Contact::create($contact);
                $message = 'and lead is created successfully.';
            }else{
                $AccountID = Account::insertGetId($tobeinsert);
                $contact = ['CompanyId'=>$companyID,
                    'AccountID'=>$AccountID,
                    'FirstName'=>$data['ContactName']];
                Contact::create($contact);
                $message = 'and Account is created successfully.';
            }
            $data['AccountID'] = $AccountID;
        }
        //Add new tags to db against opportunity
        Tags::insertNewTags(['tags'=>$data['Tags'],'TagType'=>Tags::Opportunity_tag]);
        // place new opp. in first column of board
        $data["OpportunityBoardColumnID"] = OpportunityBoardColumn::where(['OpportunityBoardID'=>$data['OpportunityBoardID'],'Order'=>0])->pluck('OpportunityBoardColumnID');
        $count = Opportunity::where(['CompanyID'=>$companyID,'OpportunityBoardID'=>$data['OpportunityBoardID'],'OpportunityBoardColumnID'=>$data["OpportunityBoardColumnID"]])->count();
        $data['Order'] = $count;
        $data["CreatedBy"] = User::get_user_full_name();

        unset($data['Company']);
        unset($data['PhoneNumber']);
        unset($data['Email']);

        unset($data['OppertunityID']);
        unset($data['leadcheck']);
        unset($data['leadOrAccount']);

        try{
            Opportunity::create($data);
        }catch (\Exception $ex){
            Log::info($ex);
            return $this->response->errorInternal($ex->getMessage());
        }
        return API::response()->array(['status' => 'success', 'message' => 'Opportunity Successfully Created'.$message, 'status_code' => 200])->statusCode(200);
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
                'Email'=>'required',
                'Phone'=>'required',
                'OpportunityBoardID'=>'required'
            );
            $validator = Validator::make($data, $rules);

            if ($validator->fails()) {
                return $this->response->error($validator->errors(),'432');
            }

            if($data['leadcheck']=='Yes'){
                unset($data['Company']);
                unset($data['PhoneNumber']);
                unset($data['Email']);
            }
            Tags::insertNewTags(['tags'=>$data['Tags'],'TagType'=>Tags::Opportunity_tag]);
            unset($data['leadcheck']);
            unset($data['OpportunityID']);
            unset($data['leadOrAccount']);

            try{
                Opportunity::where(['OpportunityID'=>$id])->update($data);
            }catch (\Exception $ex){
                Log::info($ex);
                return $this->response->errorInternal($ex->getMessage());
            }
            return API::response()->array(['status' => 'success', 'message' => 'Opportunity Board Successfully Updated', 'status_code' => 200])->statusCode(200);
        }else {
            return $this->response->errorBadRequest('Opportunity id is missing');
        }
    }

    function updateColumnOrder($id){
        $data = Input::all();
        try {
            $cardorder = explode(',', $data['cardorder']);
            foreach ($cardorder as $index => $key) {
                Opportunity::where(['OpportunityID' => $key])->update(['Order' => $index,'OpportunityBoardColumnID'=>$data['OpportunityBoardColumnID']]);
            }
            return API::response()->array(['status' => 'success', 'message' => 'Opportunity Updated', 'status_code' => 200])->statusCode(200);
        }
        catch(Exception $ex){
            return $this->response->errorInternal($ex->getMessage());
        }
    }
    //@TODO: convert into procedure
    public function getLead($id){
        $lead =  Lead::where(['tblAccount.AccountID'=>$id])->select(['AccountName as Company','tblAccount.Phone','tblAccount.Email','tblAccount.AccountType',DB::raw("concat(tblContact.FirstName,' ', tblContact.LastName) as ContactName")])
            ->leftjoin('tblContact', 'tblContact.Owner', '=', 'tblAccount.AccountID')->get();
        $reponse_data = ['status' => 'success', 'data' => ['result' => $lead], 'status_code' => 200];
        return API::response()->array($reponse_data)->statusCode(200);
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

}