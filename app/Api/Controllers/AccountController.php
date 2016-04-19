<?php

namespace Api\Controllers;

use Api\Model\AccountBalance;
use Api\Model\Account;
use Api\Model\Note;
use Api\Model\User;
use Api\Model\DataTableSql;
use App\Http\Requests;
use Dingo\Api\Facade\API;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class AccountController extends BaseController
{

    public function __construct()
    {
        $this->middleware('jwt.auth');
    }

    /**
     * Show account balance
     *
     * Get a JSON representation of all the dogs
     *  get/post variables
     * @Get('/')
     */
    public function GetCredit()
    {
        $post_data = Input::all();
        $rules['account_id'] = 'required';
        $validator = Validator::make($post_data, $rules);
        if ($validator->fails()) {
            return $this->response->errorBadRequest($validator->errors());
        }
        $AccountBalance = AccountBalance::where('AccountID', $post_data['account_id'])->first();
        $reponse_data = ['status' => 'success', 'data' => ['CurrentCredit' => $AccountBalance->CurrentCredit], 'status_code' => 200];

        return API::response()->array($reponse_data)->statusCode(200);
    }

    public function UpdateCredit()
    {
        $post_data = Input::all();

        $rules['account_id'] = 'required';
        $rules['credit'] = 'required';
        $rules['action'] = 'required';
        $validator = Validator::make($post_data, $rules);
        if ($validator->fails()) {
            return $this->response->errorBadRequest($validator->errors());
        }
        if (!in_array($post_data['action'], array('add', 'sub'))) {
            return $this->response->errorBadRequest('action is not valid');
        }
        try {
            if ($post_data['action'] == 'add') {
                AccountBalance::addCredit($post_data['account_id'], $post_data['credit']);
            } elseif ($post_data['action'] == 'sub') {
                AccountBalance::subCredit($post_data['account_id'], $post_data['credit']);
            }

        } catch (\Exception $e) {
            Log::info($e);
            return $this->response->errorInternal($e->getMessage());
        }
        return API::response()->array(['status' => 'success', 'message' => 'credit added successfully', 'status_code' => 200])->statusCode(200);
    }
    public function DeleteCredit(){

        return API::response()->array(['status' => 'success', 'message' => 'success', 'status_code' => 200])->statusCode(200);
    }

    public function GetTempCredit()
    {
        return API::response()->array(['status' => 'success', 'message' => 'success', 'status_code' => 200])->statusCode(200);
    }

    public function UpdateTempCredit()
    {
        $post_data = Input::all();

        $rules['account_id'] = 'required';
        $rules['credit'] = 'required';
        $rules['action'] = 'required';
        $rules['date'] = 'required';

        $validator = Validator::make($post_data, $rules);
        if ($validator->fails()) {
            return $this->response->errorBadRequest($validator->errors());
        }
        if (!in_array($post_data['action'], array('add', 'sub'))) {
            return $this->response->errorBadRequest('provide valid action');
        }
        try {
            if ($post_data['action'] == 'add') {
                AccountBalance::addTempCredit($post_data['account_id'], $post_data['credit'], $post_data['date']);
            } elseif ($post_data['action'] == 'sub') {
                AccountBalance::subTempCredit($post_data['account_id'], $post_data['credit'], $post_data['date']);
            }
        } catch (\Exception $e) {
            Log::info($e);
            return $this->response->errorInternal($e->getMessage());
        }
        return API::response()->array(['status' => 'success', 'message' => 'Temporary credit added successfully', 'status_code' => 200])->statusCode(200);

    }
    public function DeleteTempCredit()
    {
        return API::response()->array(['status' => 'success', 'message' => 'success', 'status_code' => 200])->statusCode(200);
    }

    public function GetAccountThreshold()
    {
        $post_data = Input::all();

        $rules['account_id'] = 'required';
        $validator = Validator::make($post_data, $rules);
        if ($validator->fails()) {
            return $this->response->errorBadRequest($validator->errors());
        }
        $BalanceThreshold = 0;
        try {
            $BalanceThreshold = AccountBalance::getThreshold($post_data['account_id']);
        } catch (\Exception $e) {
            Log::info($e);
            return $this->response->errorInternal($e->getMessage());
        }
        return API::response()->array(['status' => 'success', 'data'=>['BalanceThreshold'=>$BalanceThreshold] , 'status_code' => 200])->statusCode(200);

    }

    public function UpdateAccountThreshold()
    {
        $post_data = Input::all();

        $rules['account_id'] = 'required';
        $rules['balance_threshold'] = 'required';
        $validator = Validator::make($post_data, $rules);
        if ($validator->fails()) {
            return $this->response->errorBadRequest($validator->errors());
        }
        try {
            AccountBalance::setThreshold($post_data['account_id'],$post_data['balance_threshold']);
        } catch (\Exception $e) {
            Log::info($e);
            return $this->response->errorInternal($e->getMessage());
        }
        return API::response()->array(['status' => 'success', 'message' => 'Balance Warning Threshold updated successfully' , 'status_code' => 200])->statusCode(200);

    }
    public function DeleteAccountThreshold()
    {
        return API::response()->array(['status' => 'success', 'message' => 'success', 'status_code' => 200])->statusCode(200);
    }
    public function GetAccount($id){
        try{
            $account = Account::find($id);
        }catch (\Exception $ex){
            Log::info($ex);
            return $this->response->errorInternal($ex->getMessage());
        }
        $reponse_data = ['status' => 'success', 'data' => ['result' => $account], 'status_code' => 200];
        return API::response()->array($reponse_data)->statusCode(200);
    }

	 public function add_note(){

        $data 	= 	Input::all();

	   $rules = array(
            'CompanyID' => 'required',
            'AccountID' => 'required',
            'Note' => 'required',
        );

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            return $this->response->errorBadRequest($validator->errors());
        }

		try{
            $result = Note::create($data);
            $result['message'] = 'Note Successfully Updated';
			return API::response()->array(['status' => 'success', "NoteID" =>$result['NoteID'], 'data' => ['result' => $result], 'status_code' => 200])->statusCode(200);
        }catch (\Exception $ex){
            Log::info($ex);
            return $this->response->errorInternal($ex->getMessage());
        }
    }

    public function GetTimeLine()
    {
        $data                       =   Input::all();
        $companyID                  =   User::get_companyID();
        $rules['iDisplayStart']     =   'required|numeric|Min:0';
        $rules['iDisplayLength']    =   'required|numeric';
        $rules['AccountID']         =   'required|numeric';

        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            return $this->response->error($validator->errors(),'432');
        }
        try {
            $columns =  ['Timeline_type','ActivityTitle','ActivityDescription','ActivityDate','ActivityType','ActivityID','Emailfrom','EmailTo','EmailSubject','EmailMessage','AccountEmailLogID','NoteID','Note','CreatedBy','created_at','updated_at'];
            $query = "call prc_getAccountTimeLine(" . $data['AccountID'] . "," . $companyID . "," . $data['iDisplayStart'] . "," . $data['iDisplayLength'] . ")";

            $result_array = DataTableSql::of($query, 'sqlsrv')->make();

            $reponse_data = ['status' => 'success', 'data' => ['result' => $result_array], 'status_code' => 200];
            return API::response()->array($reponse_data)->statusCode(200);
        }
        catch (\Exception $ex){
            Log::info($ex);
            return $this->response->errorInternal($ex->getMessage());
        }
    }

}
