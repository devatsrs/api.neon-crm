<?php

namespace Api\Controllers;

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

class OpportunityBoardColumnController extends BaseController
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
    public function getColumns($id){
        if( $id > 0 ) {
            $companyID = User::get_companyID();
            $query = "call prc_GetCRMBoardColumns (".$companyID.",".$id.")";
            $result  = DB::select($query);
            $reponse_data = ['status' => 'success', 'data' => ['result' => $result], 'status_code' => 200];
            return API::response()->array($reponse_data)->statusCode(200);
        }else{
            return $this->response->errorBadRequest('Board id is missing');
        }
    }

    public function addColumn(){

        $data = Input::all();
        $companyID = User::get_companyID();
        $count = CRMBoardColumn::where(['CompanyID'=>$companyID,'BoardID'=>$data['BoardID']])->count();
        $data ["CompanyID"] = $companyID;
        $rules = array(
            'CompanyID' => 'required',
            'BoardColumnName' => 'required',
        );
        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            return $this->response->error($validator->errors(),'432');
        }
        $data["CreatedBy"] = User::get_user_full_name();
        $data['Order'] = $count;
        unset($data['BoardColumnID']);

        try{
            CRMBoardColumn::create($data);
        }catch (\Exception $ex){
            Log::info($ex);
            return $this->response->errorInternal($ex->getMessage());
        }
        return API::response()->array(['status' => 'success', 'message' => 'Opportunity Board Column Successfully Created', 'status_code' => 200])->statusCode(200);
    }

    public function updateColumn($id)
    {
        if( $id > 0 ) {
            $data = Input::all();
            $BoardColumn = CRMBoardColumn::findOrFail($id);

            $companyID = User::get_companyID();
            $data["CompanyID"] = $companyID;
            $data["ModifiedBy"] = User::get_user_full_name();
            $rules = array(
                'CompanyID' => 'required',
                'BoardColumnName' => 'required',
            );
            $validator = Validator::make($data, $rules);
            $data['SetCompleted'] = isset($data['SetCompleted'])?1:0;
            if ($validator->fails()) {
                return $this->response->error($validator->errors(),'432');
            }

            try{
                $BoardColumn->update($data);
            }catch (\Exception $ex){
                Log::info($ex);
                return $this->response->errorInternal($ex->getMessage());
            }
            return API::response()->array(['status' => 'success', 'message' => 'Opportunity Board Column Successfully Updated', 'status_code' => 200])->statusCode(200);
        }else {
            return $this->response->errorBadRequest('Board id is missing');
        }
    }

    function updateColumnOrder($id){
        $data = Input::all();
        try {
            $columnorder = explode(',', $data['columnorder']);
            foreach ($columnorder as $index => $key) {
                CRMBoardColumn::where(['BoardColumnID' => $key])->update(['Order' => $index]);
            }
            return API::response()->array(['status' => 'success', 'message' => 'Opportunity Board Column Updated', 'status_code' => 200])->statusCode(200);
        }
        catch(Exception $ex){
            Log::info($ex);
            return $this->response->errorInternal($ex->getMessage());
        }
    }
}
