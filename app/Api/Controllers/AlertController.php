<?php

namespace Api\Controllers;

use Api\Model\DataTableSql;
use Api\Model\User;
use App\Http\Requests;
use App\Lib\Alert;
use App\Lib\AlertLog;
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
                        AlertLog::where('AlertID',$AlertID)->delete();
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
            $settings = json_decode($Alert->Settings,true);
        }
        if($post_data['AlertGroup'] == Alert::GROUP_QOS){
            $class_data['LowValue'] = floatval($post_data['LowValue']);
            $class_data['HighValue'] = floatval($post_data['HighValue']);
            if(isset($settings['LastRunTime'])){
                if($Alert->Status == 0 && $class_data['Status'] == 1){
                    if ($settings['Time'] == 'HOUR') {
                        $post_data['QosAlert']['LastRunTime'] = $settings['LastRunTime'] = date("Y-m-d H:00:00", strtotime('-' . $settings['Interval'] . ' hour'));
                    } else if ($settings['Time'] == 'DAILY') {
                        $post_data['QosAlert']['LastRunTime'] = $settings['LastRunTime'] = date("Y-m-d 00:00:00", strtotime('-' . $settings['Interval'] . ' day'));
                    }
                    $post_data['QosAlert']['NextRunTime'] = next_run_time($settings);

                }else{
                    $post_data['QosAlert']['LastRunTime'] = $settings['LastRunTime'];
                    $post_data['QosAlert']['NextRunTime'] = $settings['NextRunTime'];
                }
            }
            $class_data['Settings'] = json_encode($post_data['QosAlert']);
        }else if ($post_data['AlertGroup'] == Alert::GROUP_CALL) {
            $post_data['CallAlert']['EmailToAccount'] = isset($post_data['CallAlert']['EmailToAccount'])?1:0;
            if(!empty($Alert) && $Alert->Status == 0 && $class_data['Status'] == 1){
                DB::connection('billing_db')->table('tblTempUsageDownloadLog')->where('created_at','<',date("Y-m-d"))->update(array('PostProcessStatus'=>1));
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
            if (empty($post_data['QosAlert']['CompanyGatewayID']) && empty($post_data['QosAlert']['CountryID']) && empty($post_data['QosAlert']['TrunkID']) && empty($post_data['QosAlert']['Prefix']) && empty($post_data['QosAlert']['AccountID'])) {
                $error_message = 'At least select one criteria is required.';
            }
            if (empty($post_data['LowValue']) && empty($post_data['HighValue'])) {
                $error_message = 'High or Low value is required.';
            }
            if(empty($post_data['QosAlert']['NoOfCall'])){
                $error_message = 'Number of Calls is required.';
            }
            if(empty($post_data['QosAlert']['ReminderEmail'])){
                $error_message = 'Email Address is required.';
            }

        } else if ($post_data['AlertGroup'] == Alert::GROUP_CALL) {

            if ($post_data['AlertType'] == 'block_destination') {
                if(empty($post_data['CallAlert']['BlacklistDestination'])) {
                    $error_message = 'At least one blacklist destination is required.';
                }
                if(empty($post_data['CallAlert']['ReminderEmail'])){
                    $error_message = 'Email Address is required.';
                }
            } else if ($post_data['AlertType'] == 'call_duration' || $post_data['AlertType'] == 'call_cost') {
                if (empty($post_data['CallAlert']['AccountIDs'])) {
                    $error_message = 'Account is required.';
                }else{
                    $tag = '"AccountIDs":"' . $post_data['CallAlert']['AccountIDs'] . '"';
                    if (!empty($post_data['AlertID'])) {
                        if (Alert::where('Settings', 'LIKE', '%' . $tag . '%')->where(['AlertType'=>$post_data['AlertType'],'CreatedByCustomer'=>0])->where('AlertID', '<>', $post_data['AlertID'])->count() > 0) {
                            $error_message = 'Account is already taken.';
                        }
                    } else {
                        if (Alert::where('Settings', 'LIKE', '%' . $tag . '%')->where(['AlertType'=>$post_data['AlertType'],'CreatedByCustomer'=>0])->count() > 0) {
                            $error_message = 'Account is already taken.';
                        }
                    }
                }

                if ($post_data['AlertType'] == 'call_duration' && empty($post_data['CallAlert']['Duration'])) {
                    $error_message = 'Duration is required.';
                } else if ($post_data['AlertType'] == 'call_cost' && empty($post_data['CallAlert']['Cost'])) {
                    $error_message = 'Cost is required.';
                }

            }else if ($post_data['AlertType'] == 'call_after_office') {
                if (empty($post_data['CallAlert']['AccountID'])) {
                    $error_message = 'Account is required.';
                }else{
                    $tag = '"AccountID":"' . $post_data['CallAlert']['AccountID'] . '"';
                    if (!empty($post_data['AlertID'])) {
                        if (Alert::where('Settings', 'LIKE', '%' . $tag . '%')->where(['AlertType'=>$post_data['AlertType'],'CreatedByCustomer'=>0])->where('AlertID', '<>', $post_data['AlertID'])->count() > 0) {
                            $error_message = 'Account is already taken.';
                        }
                    } else {
                        if (Alert::where('Settings', 'LIKE', '%' . $tag . '%')->where(['AlertType'=>$post_data['AlertType'],'CreatedByCustomer'=>0])->count() > 0) {
                            $error_message = 'Account is already taken.';
                        }
                    }
                }
                if (empty($post_data['CallAlert']['OpenTime'])) {
                    $error_message = 'Open Time is required.';
                } else if (empty($post_data['CallAlert']['CloseTime'])) {
                    $error_message = 'Close Time is required.';
                }

            }else if ($post_data['AlertType'] == 'vendor_balance_report') {
                if (empty($post_data['CallAlert']['Interval'])) {
                    $error_message = 'Monitoring Interval is required.';
                }
                if (empty($post_data['CallAlert']['Time'])) {
                    $error_message = 'Monitoring Time is required.';
                }
                if (empty($post_data['CallAlert']['VAccountID'])) {
                    $error_message = 'Vendor is required.';
                }
                if(empty($post_data['CallAlert']['ReminderEmail'])){
                    $error_message = 'ReminderEmail Email is required.';
                }

            }

        }
        return $error_message;
    }
    /**
     * Show Alert History
     *
     * Get a JSON representation of all
     *
     * @History('/')
     */
    public function History()
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
            $AlertType = $AlertID = '0';
            if (isset($post_data['AlertID'])) {
                $AlertID = $post_data['AlertID'];
            }
            if (isset($post_data['AlertType'])) {
                $AlertType = $post_data['AlertType'];
            }
            $post_data['StartDate'] = !empty($post_data['StartTime'])?$post_data['StartDate'].' '.$post_data['StartTime']:$post_data['StartDate'];
            $post_data['EndDate'] = !empty($post_data['EndTime'])?$post_data['EndDate'].' '.$post_data['EndTime']:$post_data['EndDate'];
            $post_data['Search'] = !empty($post_data['Search'])?$post_data['Search']:'';

            $sort_column = $columns[$post_data['iSortCol_0']];
            $query = "call prc_getAlertHistory(" . $CompanyID . ",'" . intval($AlertID) . "','" . $AlertType . "','".$post_data['StartDate']."','".$post_data['EndDate']."','".$post_data['Search']."'," . (ceil($post_data['iDisplayStart'] / $post_data['iDisplayLength'])) . " ," . $post_data['iDisplayLength'] . ",'" . $sort_column . "','" . $post_data['sSortDir_0'] . "'";
            if (isset($post_data['Export']) && $post_data['Export'] == 1) {
                $result = DB::select($query . ',1)');
            } else {
                $query .= ',0)';
                $result = DataTableSql::of($query)->make();
            }
            return generateResponse('',false,false,$result);
        } catch (\Exception $e) {
            Log::info($e);
            return $this->response->errorInternal('Internal Server');
        }
    }

}
