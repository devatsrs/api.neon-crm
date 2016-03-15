<?php

namespace Api\Controllers;

use Api\Model\AccountBalance;
use Api\Model\AccountBalanceHistory;
use Api\Model\DataTableSql;
use Api\Model\User;
use App\Http\Requests;
use Dingo\Api\Facade\API;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

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
     *
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

    public function DeleteCredit()
    {

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
        return API::response()->array(['status' => 'success', 'data' => ['BalanceThreshold' => $BalanceThreshold], 'status_code' => 200])->statusCode(200);

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
            AccountBalance::setThreshold($post_data['account_id'], $post_data['balance_threshold']);
        } catch (\Exception $e) {
            Log::info($e);
            return $this->response->errorInternal($e->getMessage());
        }
        return API::response()->array(['status' => 'success', 'message' => 'Balance Warning Threshold updated successfully', 'status_code' => 200])->statusCode(200);

    }
    public function DeleteAccountThreshold()
    {
        return API::response()->array(['status' => 'success', 'message' => 'success', 'status_code' => 200])->statusCode(200);
    }

    public function GetCreditInfo()
    {
        $post_data = Input::all();
        $rules['AccountID'] = 'required';
        $validator = Validator::make($post_data, $rules);
        if ($validator->fails()) {
            return $this->response->errorBadRequest($validator->errors());
        }
        $AccountBalance = AccountBalance::where('AccountID', $post_data['AccountID'])->first(['AccountID', 'PermanentCredit', 'CurrentCredit', 'TemporaryCredit', 'TemporaryCreditDateTime', 'BalanceThreshold']);
        $reponse_data = ['status' => 'success', 'data' => $AccountBalance, 'status_code' => 200];

        return API::response()->array($reponse_data)->statusCode(200);
    }

    public function UpdateCreditInfo()
    {
        $post_data = Input::all();
        $rules['AccountID'] = 'required';
        $validator = Validator::make($post_data, $rules);
        if ($validator->fails()) {
            return $this->response->errorBadRequest($validator->errors());
        }
        $AccountBalancedata = $AccountBalance = array();
        if (!empty($post_data['PermanentCredit'])) {
            $AccountBalancedata['PermanentCredit'] = $post_data['PermanentCredit'];
        }
        if (!empty($post_data['TemporaryCredit'])) {
            $AccountBalancedata['TemporaryCredit'] = $post_data['TemporaryCredit'];
        }
        if (!empty($post_data['TemporaryCreditDateTime'])) {
            $AccountBalancedata['TemporaryCreditDateTime'] = $post_data['TemporaryCreditDateTime'];
        }
        if (!empty($post_data['BalanceThreshold'])) {
            $AccountBalancedata['BalanceThreshold'] = $post_data['BalanceThreshold'];
        }
        if (!empty($AccountBalancedata) && AccountBalance::where('AccountID', $post_data['AccountID'])->count()) {
            $AccountBalance = AccountBalance::where('AccountID', $post_data['AccountID'])->update($AccountBalancedata);
            $AccountBalancedata['AccountID'] = $post_data['AccountID'];
        } elseif (AccountBalance::where('AccountID', $post_data['AccountID'])->count() == 0) {
            $AccountBalancedata['AccountID'] = $post_data['AccountID'];
            AccountBalance::create($AccountBalancedata);
        }
        AccountBalanceHistory::addHistory($AccountBalancedata);

        $reponse_data = ['status' => 'success', 'message' => 'Account Successfully Updated', 'data' => $AccountBalance, 'status_code' => 200];
        return API::response()->array($reponse_data)->statusCode(200);
    }
    public function GetCreditHistoryGrid(){
        $post_data = Input::all();
        try {
            $companyID = User::get_companyID();
            $rules['iDisplayStart'] = 'required|Min:1';
            $rules['iDisplayLength'] = 'required';
            $rules['iDisplayLength'] = 'required';
            $rules['sSortDir_0'] = 'required';
            $validator = Validator::make($post_data, $rules);
            if ($validator->fails()) {
                return $this->response->errorBadRequest($validator->errors());
            }
            $post_data['iDisplayStart'] += 1;
            $columns = ['PermanentCredit', 'TemporaryCredit', 'Threshold', 'CreatedBy','created_at'];

            $sort_column = $columns[$post_data['iSortCol_0']];
            $query = "call prc_GetAccountBalanceHistory (" . $companyID . "," . $post_data['AccountID'] . "," . (ceil($post_data['iDisplayStart'] / $post_data['iDisplayLength'])) . " ," . $post_data['iDisplayLength'] . ",'" . $sort_column . "','" . $post_data['sSortDir_0'] . "'";
            if (isset($post_data['Export']) && $post_data['Export'] == 1) {
                $result = DB::select($query . ',1)');
            } else {
                $query .= ',0)';
                $result = DataTableSql::of($query)->make();
            }
            Log::info($query);
            $reponse_data = ['status' => 'success', 'data' => ['result' => $result], 'status_code' => 200];
            return API::response()->array($reponse_data)->statusCode(200);
        } catch (\Exception $e) {
            Log::info($e);
            return $this->response->errorInternal($e->getMessage());
        }
    }


}
