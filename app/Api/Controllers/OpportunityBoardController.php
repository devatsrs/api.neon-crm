<?php

namespace Api\Controllers;

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

    public function __construct()
    {
        $this->middleware('jwt.auth');
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
        $rules['iDisplayLength']='required';
        $rules['sSortDir_0']='required';
        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            return $this->response->error($validator->errors(),'432');
        }
        $columns = ['BoardName' ,'Status','CreatedBy','BoardID'];
        $data['Active'] = $data['Active']==''?2:$data['Active'];
        $sort_column = $columns[$data['iSortCol_0']];
        $query = "call prc_GetCRMBoard (".$companyID.",".$data['Active'].",'".$data['BoardName']."',".( ceil($data['iDisplayStart']/$data['iDisplayLength']) )." ,".$data['iDisplayLength'].",'".$sort_column."','".$data['sSortDir_0']."'";
        if(isset($data['Export']) && $data['Export'] == 1) {
            $result  = DB::select($query.',1)');
        }else{
            $query .=',0)';
            $result = DataTableSql::of($query)->make();
        }
        $reponse_data = ['status' => 'success', 'data' => ['result' => $result], 'status_code' => 200];
        return API::response()->array($reponse_data)->statusCode(200);
    }

    public function addBoard()
    {
        $data = Input::all();
        $companyID = User::get_companyID();
        $data ["CompanyID"] = $companyID;
        $rules = array(
            'BoardName' => 'required',
            'CompanyID'=>'required'
        );
        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            return $this->response->error($validator->errors(),'432');
        }
        $data['Status'] = isset($data['Status']) ? 1 : 0;
        $data["CreatedBy"] = User::get_user_full_name();
        try{
            $boardID = CRMBoard::insertGetId($data);
            CRMBoardColumn::addDefaultColumns($boardID);
        }catch (\Exception $ex){
            Log::info($ex);
            return $this->response->errorInternal($ex->getMessage());
        }
        return API::response()->array(['status' => 'success', 'message' => 'Opportunity Board Successfully Created', 'status_code' => 200])->statusCode(200);
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
                'CompanyID' => 'required',
                'BoardName' => 'required',
            );
            $validator = Validator::make($data, $rules);

            if ($validator->fails()) {
                return $this->response->error($validator->errors(),'432');
            }
            try{
                $Board->update($data);
            }catch (\Exception $ex){
                Log::info($ex);
                return $this->response->errorInternal($ex->getMessage());
            }
            return API::response()->array(['status' => 'success', 'message' => 'Opportunity Board Successfully Updated', 'status_code' => 200])->statusCode(200);
        }else {
            return $this->response->errorBadRequest('Board id is missing');
        }
    }
}
