<?php

namespace Api\Controllers;

use Dingo\Api\Http\Request;
use Api\Model\CRMBoard;
use Api\Model\CRMBoardColumn;
use Api\Model\User;
use Api\Model\DataTableSql;
use App\Http\Requests;
use Dingo\Api\Facade\API;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class OpportunityBoardController extends BaseController
{

    public function __construct(Request $request)
    {
        $this->middleware('jwt.auth');
        Parent::__Construct($request);
    }

    /**
     * Show opportunity board
     *
     * Get a JSON representation of all the boards
     *
     * @Get('/')
     */
    public function getBoards()
    {
        $data = Input::all();
        $companyID = User::get_companyID();
        $rules['iDisplayStart'] ='required|Min:1';
        $rules['iDisplayLength']='required';
        $rules['iSortCol_0']='required';

        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            return generateResponse($validator->errors(),true);
        }

        $columns = ['BoardName' ,'Status','CreatedBy','BoardID'];
        $data['Active'] = $data['Active']==''?CRMBoard::All:$data['Active'];
        $sort_column = $columns[$data['iSortCol_0']];
        $query = "call prc_GetCRMBoard (".$companyID.",".$data['Active'].",'".$data['BoardName']."',".CRMBoard::OpportunityBoard.",".( ceil($data['iDisplayStart']/$data['iDisplayLength']) )." ,".$data['iDisplayLength'].",'".$sort_column."','".$data['sSortDir_0']."'";
        if(isset($data['Export']) && $data['Export'] == 1) {
            $result  = DB::select($query.',1)');
        }else{
            $query .=',0)';
            $result = DataTableSql::of($query)->make();
        }
        return generateResponse('',false,false,$result);
    }

    public function addBoard()
    {
        $data = Input::all();
        $companyID = User::get_companyID();
        $data ["CompanyID"] = $companyID;

        $rules = array(
            'BoardName' => 'required|unique:tblCRMBoards,BoardName,NULL,BoardID,CompanyID,' . $data['CompanyID'],
            'CompanyID'=>'required|Numeric',
            'BoardType'=>'required|Numeric'
        );
        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            return generateResponse($validator->errors(),true);
        }

        if($data['BoardType']==CRMBoard::TaskBoard && CRMBoard::where(['BoardType'=>CRMBoard::TaskBoard])->count() > 0){
            return generateResponse('Task Board already created',true,true);
        }

        $data['Status'] = isset($data['Status']) ? 1 : 0;
        $data["CreatedBy"] = User::get_user_full_name();
        $data = cleanarray($data,['BoardColumnID']);
        try{
            $boardID = CRMBoard::insertGetId($data);
            if($data['BoardType']==CRMBoard::OpportunityBoard){
                CRMBoardColumn::addDefaultColumns($boardID,CRMBoard::OpportunityBoard);
            }else{
                CRMBoardColumn::addDefaultColumns($boardID,CRMBoard::TaskBoard);
            }
        }catch (\Exception $ex){
            Log::info($ex);
            return $this->response->errorInternal($ex->getMessage());
        }
        return generateResponse('Opportunity Board Successfully Created');
    }

    public function UpdateBoard($id)
    {
        if( $id > 0 ) {
            $data = Input::all();
            $Board = CRMBoard::findOrFail($id);

            $companyID = User::get_companyID();
            $data ["CompanyID"] = $companyID;
            $data['Status'] = isset($data['Status']) ? 1 : 0;
            $data["ModifiedBy"] = User::get_user_full_name();

            $rules = array(
                'BoardName' => 'required|unique:tblCRMBoards,BoardName,'.$id.',BoardID,CompanyID,' . $data['CompanyID'],
                'CompanyID' => 'required',
                'BoardName' => 'required',
            );
            $validator = Validator::make($data, $rules);
            if ($validator->fails()) {
                return generateResponse($validator->errors(),true);
            }
            $data = cleanarray($data);
            try{
                $Board->update($data);
            }catch (\Exception $ex){
                Log::info($ex);
                return $this->response->errorInternal($ex->getMessage());
            }
            return generateResponse('Opportunity Board Successfully Updated');
        }else {
            return generateResponse('Board id is missing',true);
        }
    }
}
