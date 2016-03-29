<?php

namespace Api\Controllers;

use Api\Model\Lead;
use App\Http\Requests;
use Dingo\Api\Facade\API;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class LeadController extends BaseController
{

    public function __construct()
    {
        $this->middleware('jwt.auth');
    }

    public function GetLead($id){
        try{
            $lead = Lead::find($id);
        }catch (\Exception $ex){
            Log::info($ex);
            return $this->response->errorInternal($ex->getMessage());
        }
        return API::response()->array(['status' => 'success', 'data'=>['lead'=>$lead] , 'status_code' => 200])->statusCode(200);
    }

    public function GetLeads(){
        try{
            $leads = Lead::getLeadList();
        }catch (\Exception $ex){
            Log::info($ex);
            return $this->response->errorInternal($ex->getMessage());
        }
        return API::response()->array(['status' => 'success', 'data'=>['leads'=>$leads] , 'status_code' => 200])->statusCode(200);
    }
}
