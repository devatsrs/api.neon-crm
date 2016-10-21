<?php

namespace Api\Controllers;

use Api\Model\DataTableSql;
use Api\Model\User;
use App\Http\Requests;
use App\Lib\Alert;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AlertController extends BaseController
{

    public function __construct()
    {
        $this->middleware('jwt.auth');
    }

    /**
     * Show Alert
     *
     * Get a JSON representation of all
     *
     * @DataGrid('/')
     */
    public function DataGrid()
    {
        $post_data = Input::all();
        try {
            $CompanyID = User::get_companyID();
            $rules['iDisplayStart'] = 'required|Min:1';
            $rules['iDisplayLength'] = 'required';
            $rules['iDisplayLength'] = 'required';
            $rules['sSortDir_0'] = 'required';
            $validator = Validator::make($post_data, $rules);
            if ($validator->fails()) {
                return generateResponse($validator->errors(),true);
            }
            $post_data['iDisplayStart'] += 1;
            $columns = ['Name', 'CreatedBy', 'created_at'];
            $AlertType = $AlertGroup = '';
            if (isset($post_data['AlertType'])) {
                $AlertType = $post_data['AlertType'];
            }
            if (isset($post_data['AlertGroup'])) {
                $AlertGroup = $post_data['AlertGroup'];
            }

            $sort_column = $columns[$post_data['iSortCol_0']];
            $query = "call prc_getAlert(" . $CompanyID . ",'" . $AlertGroup . "','" . $AlertType . "'," . (ceil($post_data['iDisplayStart'] / $post_data['iDisplayLength'])) . " ," . $post_data['iDisplayLength'] . ",'" . $sort_column . "','" . $post_data['sSortDir_0'] . "'";
            if (isset($post_data['Export']) && $post_data['Export'] == 1) {
                $result = DB::select($query . ',1)');
            } else {
                $query .= ',0)';
                Log::info($query);
                $result = DataTableSql::of($query)->make();
            }
            return generateResponse('',false,false,$result);
        } catch (\Exception $e) {
            Log::info($e);
            return $this->response->errorInternal('Internal Server');
        }
    }

    /**
     * Add new Alert
     *
     * @Store('/')
     */
    public function Store()
    {
        $post_data = Input::all();
        $CompanyID = User::get_companyID();


        $rules = Alert::$rules;
        $validator = Validator::make($post_data, $rules,Alert::$messages);
        if ($validator->fails()) {
            return generateResponse($validator->errors(),true);
        }
        $error_message = self::data_validate($post_data);
        if(!empty($error_message)){
            return generateResponse($error_message, true, true);
        }
        try {
            $insertdata = array();
            $insertdata =  $post_data;
            $insertdata = self::convert_data($post_data)+$insertdata;
            $insertdata['CompanyID'] = $CompanyID;
            $insertdata['CreatedBy'] = User::get_user_full_name();
            $insertdata['created_at'] = get_currenttime();
            $Alert = Alert::create($insertdata);

            return generateResponse('Alert added successfully',false,false,$Alert);
        } catch (\Exception $e) {
            Log::info($e);
            return $this->response->errorInternal('Internal Server');
        }
    }

    /**
     * Delete Alert
     *
     * @param $AlertID
     */
    public function Delete($AlertID)
    {
        try {
            if (intval($AlertID) > 0) {
                if (!Alert::checkForeignKeyById($AlertID)) {
                    try {
                        DB::beginTransaction();

                        $result = Alert::find($AlertID)->delete();
                        DB::commit();
                        if ($result) {
                            return generateResponse('Alert Successfully Deleted');
                        } else {
                            return generateResponse('Problem Deleting Alert.',true,true);
                        }
                    } catch (\Exception $ex) {
                        return generateResponse('Alert is in Use, You cant delete this Alert.',true,true);
                    }
                } else {
                    return generateResponse('Alert is in Use, You cant delete this Alert.',true,true);
                }
            } else {
                return generateResponse('Provide Valid Integer Value.',true,true);
            }
        } catch (\Exception $e) {
            Log::info($e);
            return $this->response->errorInternal('Internal Server');
        }
    }

    /**
     * Update Alert
     *
     * @param $AlertID
     */
    public function Update($AlertID)
    {
        if ($AlertID > 0) {
            $post_data = Input::all();
            $CompanyID = User::get_companyID();

            $rules['Name'] = 'required|unique:tblAlert,Name,' . $AlertID . ',AlertID,CompanyID,' . $CompanyID;
            $rules = $rules + Alert::$rules;
            $validator = Validator::make($post_data, $rules,Alert::$messages);
            if ($validator->fails()) {
                return generateResponse($validator->errors(),true);
            }

            $error_message = self::data_validate($post_data);
            if(!empty($error_message)){
                return generateResponse($error_message, true, true);
            }


            try {

                try {
                    $Alert = Alert::findOrFail($AlertID);
                } catch (\Exception $e) {
                    Log::info($e);
                    return generateResponse('Alert not found.',true,true);
                }
                $updatedata = array();
                $updatedata =  $post_data;
                $updatedata = self::convert_data($post_data,$Alert)+$updatedata;
                $updatedata['UpdatedBy'] = User::get_user_full_name();
                $Alert->update($updatedata);

                return generateResponse('Alert updated successfully');
            } catch (\Exception $e) {
                Log::info($e);
                return $this->response->errorInternal('Internal Server');
            }
        } else {
            return generateResponse('Provide Valid Integer Value.',true,true);
        }

    }

    /**
     *
     */
    public function get($AlertID){
        $post_data = Input::all();
        if ($AlertID > 0) {
            $rules['AlertID'] = 'required';
            $validator = Validator::make($post_data, $rules);
            if ($validator->fails()) {
                return generateResponse($validator->errors(), true);
            }
            try {
                $Alert = Alert::findOrFail($AlertID);
            } catch (\Exception $e) {
                Log::info($e);
                return generateResponse('Alert not found.',true,true);
            }
            return generateResponse('success', false, false, $Alert);
        } else {
            return generateResponse('Provide Valid Integer Value.', true, true);
        }
    }

    public function convert_data($post_data,$Alert=array()){
        $class_data = array();
        $class_data['Status'] = isset($post_data['Status'])?1:0;
        if(!empty($Alert)){
            $Settings = json_decode($Alert->Settings);
        }
        if($post_data['AlertGroup'] == Alert::GROUP_QOS){
            $class_data['LowValue'] = floatval($post_data['LowValue']);
            $class_data['HighValue'] = floatval($post_data['HighValue']);
            if(isset($Settings->LastRunTime)){
                $post_data['QosAlert']['LastRunTime'] = $Settings->LastRunTime;
            }
            if(isset($Settings->NextRunTime)){
                $post_data['QosAlert']['NextRunTime'] = $Settings->NextRunTime;
            }
            $class_data['Settings'] = json_encode($post_data['QosAlert']);
        }else if ($post_data['AlertGroup'] == Alert::GROUP_CALL) {
            if(isset($Settings->LastRunTime)){
                $post_data['CallAlert']['LastRunTime'] = $Settings->LastRunTime;
            }
            if(isset($Settings->NextRunTime)){
                $post_data['CallAlert']['NextRunTime'] = $Settings->NextRunTime;
            }
            $class_data['Settings'] = json_encode($post_data['CallAlert']);
        }

        return $class_data;
    }

    public function data_validate($post_data)
    {
        $error_message = '';

        if ($post_data['AlertGroup'] == Alert::GROUP_QOS) {
            if (empty($post_data['QosAlert']['Interval'])) {
                $error_message = 'Qos Alert Interval is required.';
            }
            if (empty($post_data['QosAlert']['Time'])) {
                $error_message = 'Qos Alert Time is required.';
            }
        } else if ($post_data['AlertGroup'] == Alert::GROUP_CALL) {

            if ($post_data['AlertType'] == 'block_destination' && empty($post_data['CallAlert']['BlacklistDestination'])) {
                $error_message = 'At least one blacklist destination is required.';
            } else if ($post_data['AlertType'] == 'call_duration' || $post_data['AlertType'] == 'call_cost' || $post_data['AlertType'] == 'call_after_office') {
                if (empty($post_data['CallAlert']['AccountID'])) {
                    $error_message = 'Account is required.';
                }
                $tag = '"AccountID":"' . $post_data['CallAlert']['AccountID'] . '"';
                if (!empty($post_data['AlertID'])) {
                    if (Alert::where('Settings', 'LIKE', '%' . $tag . '%')->where('AlertType', $post_data['AlertType'])->where('AlertID', '<>', $post_data['AlertID'])->count() > 0) {
                        $error_message = 'Account is already taken.';
                    }
                }else{
                    if (Alert::where('Settings', 'LIKE', '%' . $tag . '%')->where('AlertType', $post_data['AlertType'])->count() > 0) {
                        $error_message = 'Account is already taken.';
                    }
                }
                if ($post_data['AlertType'] == 'call_duration' && empty($post_data['CallAlert']['Duration'])) {
                    $error_message = 'Duration is required.';
                } else if ($post_data['AlertType'] == 'call_cost' && empty($post_data['CallAlert']['Cost'])) {
                    $error_message = 'Cost is required.';
                } else if ($post_data['AlertType'] == 'call_after_office' && empty($post_data['CallAlert']['OpenTime'])) {
                    $error_message = 'Open Time is required.';
                } else if ($post_data['AlertType'] == 'call_after_office' && empty($post_data['CallAlert']['CloseTime'])) {
                    $error_message = 'Close Time is required.';
                }

            }

        }
        return $error_message;
    }

}
