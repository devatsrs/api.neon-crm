<?php

namespace Api\Controllers;

use Api\Model\BillingClass;
use Api\Model\DataTableSql;
use Api\Model\User;
use App\Http\Requests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class BillingClassController extends BaseController
{

    public function __construct()
    {
        $this->middleware('jwt.auth');
    }

    /**
     * Show Billing Class
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
            $Name = '';
            if (isset($post_data['Name'])) {
                $Name = $post_data['Name'];
            }

            $sort_column = $columns[$post_data['iSortCol_0']];
            $query = "call prc_getBillingClass(" . $CompanyID . ",'" . $Name . "'," . (ceil($post_data['iDisplayStart'] / $post_data['iDisplayLength'])) . " ," . $post_data['iDisplayLength'] . ",'" . $sort_column . "','" . $post_data['sSortDir_0'] . "'";
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
     * Add new Billing Class
     *
     * @Store('/')
     */
    public function Store()
    {
        $post_data = Input::all();
        $CompanyID = User::get_companyID();

        $rules['Name'] = 'required|unique:tblBillingClass,Name,NULL,CompanyID,CompanyID,' . $CompanyID;
        $rules = $rules + BillingClass::$rules;
        $validator = Validator::make($post_data, $rules,BillingClass::$messages);
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
            $BillingClass = BillingClass::create($insertdata);

            return generateResponse('Billing Class added successfully',false,false,$BillingClass);
        } catch (\Exception $e) {
            Log::info($e);
            return $this->response->errorInternal('Internal Server');
        }
    }

    /**
     * Delete Billing Class
     *
     * @param $BillingClassID
     */
    public function Delete($BillingClassID)
    {
        try {
            if (intval($BillingClassID) > 0) {
                if (!BillingClass::checkForeignKeyById($BillingClassID)) {
                    try {
                        DB::beginTransaction();

                        $result = BillingClass::find($BillingClassID)->delete();
                        DB::commit();
                        if ($result) {
                            return generateResponse('Billing Class Successfully Deleted');
                        } else {
                            return generateResponse('Problem Deleting Billing Class.',true,true);
                        }
                    } catch (\Exception $ex) {
                        return generateResponse('Billing Class is in Use, You cant delete this Billing Class.',true,true);
                    }
                } else {
                    return generateResponse('Billing Class is in Use, You cant delete this Billing Class.',true,true);
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
     * Update Billing Class
     *
     * @param $BillingClassID
     */
    public function Update($BillingClassID)
    {
        if ($BillingClassID > 0) {
            $post_data = Input::all();
            $CompanyID = User::get_companyID();

            $rules['Name'] = 'required|unique:tblBillingClass,Name,' . $BillingClassID . ',BillingClassID,CompanyID,' . $CompanyID;
            $rules = $rules + BillingClass::$rules;
            $validator = Validator::make($post_data, $rules,BillingClass::$messages);
            if ($validator->fails()) {
                return generateResponse($validator->errors(),true);
            }

            $error_message = self::data_validate($post_data);
            if(!empty($error_message)){
                return generateResponse($error_message, true, true);
            }


            try {

                try {
                    $BillingClass = BillingClass::findOrFail($BillingClassID);
                } catch (\Exception $e) {
                    Log::info($e);
                    return generateResponse('Billing Class not found.',true,true);
                }
                $updatedata = array();
                $updatedata =  $post_data;
                $updatedata = self::convert_data($post_data,$BillingClass)+$updatedata;
                $updatedata['UpdatedBy'] = User::get_user_full_name();
                $BillingClass->update($updatedata);

                return generateResponse('Billing Class updated successfully');
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
    public function get($BillingClassID){
        $post_data = Input::all();
        if ($BillingClassID > 0) {
            $rules['BillingClassID'] = 'required';
            $validator = Validator::make($post_data, $rules);
            if ($validator->fails()) {
                return generateResponse($validator->errors(), true);
            }
            try {
                $BillingClass = BillingClass::findOrFail($BillingClassID);
            } catch (\Exception $e) {
                Log::info($e);
                return generateResponse('Billing Class not found.',true,true);
            }
            return generateResponse('success', false, false, $BillingClass);
        } else {
            return generateResponse('Provide Valid Integer Value.', true, true);
        }
    }

    public function convert_data($post_data,$BillingClass=array()){
        $class_data = array();
        $class_data['PaymentReminderStatus'] = isset($post_data['PaymentReminderStatus'])?1:0;
        $class_data['LowBalanceReminderStatus'] = isset($post_data['LowBalanceReminderStatus'])?1:0;
        $class_data['InvoiceReminderStatus'] = isset($post_data['InvoiceReminderStatus'])?1:0;
        if(!empty($BillingClass)){
            $PaymentReminderSettings = json_decode($BillingClass->PaymentReminderSettings);
            if(isset($PaymentReminderSettings->LastRunTime)){
                $post_data['PaymentReminder']['LastRunTime'] = $PaymentReminderSettings->LastRunTime;
            }
            if(isset($PaymentReminderSettings->NextRunTime)){
                $post_data['PaymentReminder']['NextRunTime'] = $PaymentReminderSettings->NextRunTime;
            }

            $LowBalanceReminderSettings = json_decode($BillingClass->LowBalanceReminderSettings);
            if(isset($LowBalanceReminderSettings->LastRunTime)){
                $post_data['LowBalanceReminder']['LastRunTime'] = $LowBalanceReminderSettings->LastRunTime;
            }
            if(isset($LowBalanceReminderSettings->NextRunTime)){
                $post_data['LowBalanceReminder']['NextRunTime'] = $LowBalanceReminderSettings->NextRunTime;
            }

        }
        if (isset($post_data['TaxRateID'])) {
            $class_data['TaxRateID'] = implode(',', array_unique($post_data['TaxRateID']));
        }
        if (isset($post_data['InvoiceReminder'])) {
            $class_data['InvoiceReminderSettings'] = json_encode($post_data['InvoiceReminder']);
        }
        if (isset($post_data['PaymentReminder'])) {
            $class_data['PaymentReminderSettings'] = json_encode($post_data['PaymentReminder']);
        }
        if (isset($post_data['LowBalanceReminder'])) {
            $class_data['LowBalanceReminderSettings'] = json_encode($post_data['LowBalanceReminder']);
        }
        return $class_data;
    }

    public function data_validate($post_data){
        $error_message = '';
        if (isset($post_data['InvoiceReminder']) && isset($post_data['InvoiceReminder']['Day'])) {
            $allDayNumbers = $post_data['InvoiceReminder']['Day'] === array_filter($post_data['InvoiceReminder']['Day'], 'is_numeric');
            if($allDayNumbers == false) {
                $error_message  = 'Please enter numeric value in Payment Reminder Days.';
            }
            $duplicatedays = $post_data['InvoiceReminder']['Day'] === array_unique($post_data['InvoiceReminder']['Day']);
            if($duplicatedays == false) {
                $error_message  = 'Duplicate Days are not allowed.';
            }
            $allTemplates = $post_data['InvoiceReminder']['TemplateID'] === array_filter($post_data['InvoiceReminder']['TemplateID'], 'is_numeric');
            if($allTemplates == false) {
                $error_message  = 'Please Select Template in All Payment Reminder.';
            }
        }elseif(isset($post_data['InvoiceReminderStatus'])){
            if(empty($post_data['InvoiceReminder']['Day'])){
                $error_message  = 'Please add reminder.';
            }
        }
        if(isset($post_data['LowBalanceReminderStatus'])){
            if(empty($post_data['LowBalanceReminder']['TemplateID'])) {
                $error_message = 'Low Balance Reminder Template is required.';
            }
            if(empty($post_data['LowBalanceReminder']['Time'])) {
                $error_message = 'Low Balance Reminder Time is required .';
            }
            if(empty($post_data['LowBalanceReminder']['Interval'])) {
                $error_message = 'Low Balance Reminder Interval is required.';
            }
        }
        if(isset($post_data['PaymentReminderStatus'])){
            if(empty($post_data['PaymentReminder']['TemplateID'])) {
                $error_message = 'Account Payment Reminder Template is required.';
            }
            if(empty($post_data['PaymentReminder']['Time'])) {
                $error_message = 'Account Payment Reminder Time is required .';
            }
            if(empty($post_data['PaymentReminder']['Interval'])) {
                $error_message = 'Account Payment Reminder Interval is required.';
            }
        }

        return $error_message;
    }

}
