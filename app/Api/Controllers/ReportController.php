<?php
namespace Api\Controllers;

use Api\Model\DataTableSql;
use Api\Model\Report;
use Api\Model\User;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ReportController extends BaseController {
    protected $tokenClass;

    public function __construct()
    {
        $this->middleware('jwt.auth');
    }



    public function Store(){
        $post_data = Input::all();
        if(isset($post_data['LicenceKey'])){unset($post_data['LicenceKey']);};
        if(isset($post_data['CompanyName'])){unset($post_data['CompanyName']);};
        if(isset($post_data['LoginType'])){unset($post_data['LoginType']);};

        $CompanyID = User::get_companyID();
        $rules['Name'] = 'required|unique:tblReport,Name,NULL,ReportID,CompanyID,' . $CompanyID;;

        $verifier = App::make('validation.presence');
        $verifier->setConnection('neon_report');

        $validator = Validator::make($post_data, $rules);
        $validator->setPresenceVerifier($verifier);

        if ($validator->fails()) {
            return generateResponse($validator->errors(),true);
        }
        try {
            $insertdata = array();
            $insertdata['CompanyID'] = $CompanyID;
            $insertdata['Name'] = $post_data['Name'];
            $insertdata['Settings'] = json_encode($post_data);
            $insertdata['CreatedBy'] = User::get_user_full_name();
            $insertdata['created_at'] = get_currenttime();
            $Report = Report::create($insertdata);

            return generateResponse('Report added successfully',false,false,$Report);
        } catch (\Exception $e) {
            Log::info($e);
            return $this->response->errorInternal('Internal Server');
        }
    }
    public function Delete($ReportID){
        try {
            if (intval($ReportID) > 0) {
                if (!Report::checkForeignKeyById($ReportID)) {
                    try {
                        DB::beginTransaction();
                        $result = Report::find($ReportID)->delete();
                        DB::commit();
                        if ($result) {
                            return generateResponse('Report Successfully Deleted');
                        } else {
                            return generateResponse('Problem Deleting Report.',true,true);
                        }
                    } catch (\Exception $ex) {
                        return generateResponse('Report is in Use, You cant delete this Report.',true,true);
                    }
                } else {
                    return generateResponse('Report is in Use, You cant delete this Report.',true,true);
                }
            } else {
                return generateResponse('Provide Valid Integer Value.',true,true);
            }
        } catch (\Exception $e) {
            Log::info($e);
            return $this->response->errorInternal('Internal Server');
        }
    }

    public function Update($ReportID){
        if ($ReportID > 0) {
            $post_data = Input::all();
            $CompanyID = User::get_companyID();
            if(isset($post_data['LicenceKey'])){unset($post_data['LicenceKey']);};
            if(isset($post_data['CompanyName'])){unset($post_data['CompanyName']);};
            if(isset($post_data['LoginType'])){unset($post_data['LoginType']);};
            $rules['Name'] = 'required|unique:tblReport,Name,' . $ReportID . ',ReportID,CompanyID,' . $CompanyID;


            $verifier = App::make('validation.presence');
            $verifier->setConnection('neon_report');

            $validator = Validator::make($post_data, $rules);
            $validator->setPresenceVerifier($verifier);
            if ($validator->fails()) {
                return generateResponse($validator->errors(),true);
            }
            try {

                try {
                    $Report = Report::findOrFail($ReportID);
                } catch (\Exception $e) {
                    Log::info($e);
                    return generateResponse('Report not found.',true,true);
                }
                $updatedata = array();
                $updatedata['Name'] = $post_data['Name'];
                $updatedata['Settings'] = json_encode($post_data);
                $updatedata['UpdatedBy'] = User::get_user_full_name();
                $Report->update($updatedata);

                return generateResponse('Report updated successfully');
            } catch (\Exception $e) {
                Log::info($e);
                return $this->response->errorInternal('Internal Server');
            }
        } else {
            return generateResponse('Provide Valid Integer Value.',true,true);
        }
    }
    public function UpdateSchedule($ReportID){
        if ($ReportID > 0) {
            $post_data = Input::all();
            $post_data['Schedule'] = isset($post_data['Schedule'])?1:0;
            if(isset($post_data['LicenceKey'])){unset($post_data['LicenceKey']);};
            if(isset($post_data['CompanyName'])){unset($post_data['CompanyName']);};
            if(isset($post_data['LoginType'])){unset($post_data['LoginType']);};

            if (empty($post_data['Report']['Interval'])) {
                $error_message = 'Schedule Interval is required.';
            }
            if (empty($post_data['Report']['Time'])) {
                $error_message = 'Schedule Time is required.';
            }
            if(empty($post_data['Report']['NotificationEmail'])){
                $error_message = 'Email Address is required.';
            }
            if(!empty($error_message)){
                return generateResponse($error_message, true, true);
            }
            try {
                try {
                    $Report = Report::findOrFail($ReportID);
                } catch (\Exception $e) {
                    Log::info($e);
                    return generateResponse('Report not found.',true,true);
                }

                $settings = json_decode($Report->ScheduleSettings,true);
                if(isset($settings['LastRunTime'])){
                    if($Report->Status == 0 && $post_data['Schedule'] == 1){
                        if ($settings['Time'] == 'DAILY') {
                            $post_data['Report']['LastRunTime'] = date("Y-m-d 00:00:00", strtotime('-' . $settings['Interval'] . ' day'));
                        } else if ($settings['Time'] == 'WEEKLY') {
                            $post_data['Report']['LastRunTime'] = date("Y-m-d 00:00:00", strtotime('-' . $settings['Interval'] . ' week'));
                        } else if ($settings['Time'] == 'MONTHLY') {
                            $post_data['Report']['LastRunTime'] = date("Y-m-d 00:00:00", strtotime('-' . $settings['Interval'] . ' month'));
                        } else if ($settings['Time'] == 'YEARLY') {
                            $post_data['Report']['LastRunTime'] = date("Y-m-d 00:00:00", strtotime('-' . $settings['Interval'] . ' year'));
                        }
                        $post_data['Report']['NextRunTime'] = next_run_time($settings);
                    }else{
                        $post_data['Report']['LastRunTime'] = $settings['LastRunTime'];
                        $post_data['Report']['NextRunTime'] = $settings['NextRunTime'];
                    }
                }

                $updatedata = array();
                $updatedata['ScheduleSettings'] = json_encode($post_data['Report']);
                $updatedata['Schedule'] = $post_data['Schedule'];
                $updatedata['UpdatedBy'] = User::get_user_full_name();
                $Report->update($updatedata);
                Log::info($updatedata);
                Log::info($post_data);

                return generateResponse('Report updated successfully');
            } catch (\Exception $e) {
                Log::info($e);
                return $this->response->errorInternal('Internal Server');
            }
        } else {
            return generateResponse('Provide Valid Integer Value.',true,true);
        }
    }
    /**
     * Show Report History
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
            $ReportID = '0';
            if (isset($post_data['ReportID'])) {
                $ReportID = $post_data['ReportID'];
            }

            $post_data['StartDate'] = !empty($post_data['StartTime'])?$post_data['StartDate'].' '.$post_data['StartTime']:$post_data['StartDate'];
            $post_data['EndDate'] = !empty($post_data['EndTime'])?$post_data['EndDate'].' '.$post_data['EndTime']:$post_data['EndDate'];
            $post_data['Search'] = !empty($post_data['Search'])?$post_data['Search']:'';

            $sort_column = $columns[$post_data['iSortCol_0']];
            $query = "call prc_getReportHistory(" . $CompanyID . ",'" . intval($ReportID) . "','".$post_data['StartDate']."','".$post_data['EndDate']."','".$post_data['Search']."'," . (ceil($post_data['iDisplayStart'] / $post_data['iDisplayLength'])) . " ," . $post_data['iDisplayLength'] . ",'" . $sort_column . "','" . $post_data['sSortDir_0'] . "'";
            if (isset($post_data['Export']) && $post_data['Export'] == 1) {
                $result = DB::connection('neon_report')->select($query . ',1)');
            } else {
                $query .= ',0)';
                Log::info($query);
                $result = DataTableSql::of($query,'neon_report')->make();
            }
            return generateResponse('',false,false,$result);
        } catch (\Exception $e) {
            Log::info($e);
            return $this->response->errorInternal('Internal Server');
        }
    }



}