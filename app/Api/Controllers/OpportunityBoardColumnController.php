<?php

namespace Api\Controllers;

use Dingo\Api\Http\Request;
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

    public function __construct(Request $request)
    {
        $this->middleware('jwt.auth');
        Parent::__Construct($request);
    }

    /**
     * Show opportunity board Columns
     *
     * Get a JSON representation of all the boards Columns
     *
     * @Get('/')
     */
    public function getColumns($id){
        if( $id > 0 ) {
            $companyID = User::get_companyID();
            $query = "call prc_GetCRMBoardColumns (".$companyID.",".$id.")";
            $result  = DB::select($query);
            return generateResponse('',false,false,$result);
        }else{
            return generateResponse('Board id is missing',true,true);
        }
    }

    public function addColumn(){

        $data = Input::all();
        $companyID = User::get_companyID();
        $count = CRMBoardColumn::where(['CompanyID'=>$companyID,'BoardID'=>$data['BoardID']])->count();

        $data ["CompanyID"] = $companyID;
        $data["CreatedBy"] = User::get_user_full_name();
        $data['Order'] = $count;
		$data = cleanarray($data,['BoardColumnID']);


        $rules = array(
            'CompanyID' => 'required',
            'BoardColumnName' => 'required',
        );
        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            generateResponse($validator->errors(),true);
        }


        try{
            CRMBoardColumn::create($data);
        }catch (\Exception $ex){
            Log::info($ex);
            return $this->response->errorInternal($ex->getMessage());
        }
        return generateResponse('Opportunity Board Column Successfully Created');
    }

    public function updateColumn($id)
    {
        if( $id > 0 ) {
            $data = Input::all();
            $BoardColumn = CRMBoardColumn::findOrFail($id);

            $companyID = User::get_companyID();
            $data["CompanyID"] = $companyID;
            $data["ModifiedBy"] = User::get_user_full_name();
            $data['SetCompleted'] = isset($data['SetCompleted'])?1:0;

            $rules = array(
                'CompanyID' => 'required',
                'BoardColumnName' => 'required',
            );
            $validator = Validator::make($data, $rules);
            if ($validator->fails()) {
                return generateResponse($validator->errors(),true);
            }

            try{
                $BoardColumn->update($data);
            }catch (\Exception $ex){
                Log::info($ex);
                return $this->response->errorInternal($ex->getMessage());
            }
            return generateResponse('Opportunity Board Column Successfully Updated');
        }else {
            return generateResponse('Board id is missing',true,true);
        }
    }

    function updateColumnOrder($id){
        $data = Input::all();
        try {
            $columnorder = explode(',', $data['columnorder']);
            foreach ($columnorder as $index => $key) {
                CRMBoardColumn::where(['BoardColumnID' => $key])->update(['Order' => $index]);
            }
            return generateResponse('Opportunity Board Column Updated');
        }
        catch(Exception $ex){
            Log::info($ex);
            return $this->response->errorInternal($ex->getMessage());
        }
    }
}
