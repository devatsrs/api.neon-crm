<?php

namespace Api\Controllers;

use Api\Model\DataTableSql;
use Api\Model\Discount;
use Api\Model\DiscountPlan;
use Api\Model\DiscountScheme;
use Api\Model\User;
use App\Http\Requests;
use Dingo\Api\Facade\API;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class DiscountPlanController extends BaseController
{

    public function __construct()
    {
        $this->middleware('jwt.auth');
    }

    /**
     * Show Discount Plan
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
            $Name = $CodedeckID = '';
            if (isset($post_data['Name'])) {
                $Name = $post_data['Name'];
            }
            if (isset($post_data['CodedeckID'])) {
                $CodedeckID = $post_data['CodedeckID'];
            }
            $sort_column = $columns[$post_data['iSortCol_0']];
            $query = "call prc_getDiscountPlan(" . $CompanyID . ",'" . $Name . "'," . (ceil($post_data['iDisplayStart'] / $post_data['iDisplayLength'])) . " ," . $post_data['iDisplayLength'] . ",'" . $sort_column . "','" . $post_data['sSortDir_0'] . "'";
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
     * Add new Discount Plan
     *
     * @Store('/')
     */
    public function Store()
    {
        $post_data = Input::all();
        $CompanyID = User::get_companyID();

        $rules['Name'] = 'required|unique:tblDiscountPlan,Name,NULL,CompanyID,CompanyID,' . $CompanyID;
        $rules['DestinationGroupSetID'] = 'required|numeric';
        $rules['CurrencyID'] = 'required|numeric';

        $validator = Validator::make($post_data, $rules);
        if ($validator->fails()) {
            return generateResponse($validator->errors(),true);
        }
        try {
            $insertdata = array();
            foreach ($rules as $columnname => $column) {
                $insertdata[$columnname] = $post_data[$columnname];
            }
            if (isset($post_data['Description'])) {
                $insertdata['Description'] = $post_data['Description'];
            }
            $insertdata['CompanyID'] = $CompanyID;
            $insertdata['CreatedBy'] = User::get_user_full_name();
            $insertdata['created_at'] = get_currenttime();
            $DiscountPlan = DiscountPlan::create($insertdata);
            return generateResponse('Discount Plan added successfully');
        } catch (\Exception $e) {
            Log::info($e);
            return $this->response->errorInternal('Internal Server');
        }
    }

    /**
     * Delete Discount Plan
     *
     * @param $DiscountPlanID
     */
    public function Delete($DiscountPlanID)
    {
        try {
            if (intval($DiscountPlanID) > 0) {
                if (!DiscountPlan::checkForeignKeyById($DiscountPlanID)) {
                    try {
                        DB::beginTransaction();
                        DiscountScheme::join('tblDiscount','tblDiscountScheme.DiscountID','=','tblDiscount.DiscountID')->where('DiscountPlanID',$DiscountPlanID)->delete();
                        Discount::where("DiscountPlanID",$DiscountPlanID)->delete();
                        $result = DiscountPlan::find($DiscountPlanID)->delete();
                        DB::commit();
                        if ($result) {
                            return generateResponse('Discount Plan Successfully Deleted');
                        } else {
                            return generateResponse('Problem Deleting Discount Plan.',true,true);
                        }
                    } catch (\Exception $ex) {
                        Log::info($ex);
                        try {
                            DB::rollback();
                        } catch (\Exception $err) {
                            Log::error($err);
                        }
                        return generateResponse('Discount Plan is in Use, You cant delete this Discount Plan.',true,true);
                    }
                } else {
                    return generateResponse('Discount Plan is in Use, You cant delete this Discount Plan.',true,true);
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
     * Update Discount Plan
     *
     * @param $DiscountPlanID
     */
    public function Update($DiscountPlanID)
    {
        if ($DiscountPlanID > 0) {
            $post_data = Input::all();
            $CompanyID = User::get_companyID();

            $rules['Name'] = 'required|unique:tblDiscountPlan,Name,' . $DiscountPlanID . ',DiscountPlanID,CompanyID,' . $CompanyID;
            $rules['DestinationGroupSetID'] = 'required|numeric';
            $rules['CurrencyID'] = 'required|numeric';
            $validator = Validator::make($post_data, $rules);
            if ($validator->fails()) {
                return generateResponse($validator->errors(),true);
            }
            try {
                try {
                    $DiscountPlan = DiscountPlan::findOrFail($DiscountPlanID);
                } catch (\Exception $e) {
                    return generateResponse('Discount Plan not found.',true,true);
                }
                $updatedata = array();
                foreach ($rules as $columnname => $column) {
                    $updatedata[$columnname] = $post_data[$columnname];
                }
                if (isset($post_data['Description'])) {
                    $updatedata['Description'] = $post_data['Description'];
                }
                $updatedata['UpdatedBy'] = User::get_user_full_name();
                $DiscountPlan->update($updatedata);
                return generateResponse('Discount Plan updated successfully');
            } catch (\Exception $e) {
                Log::info($e);
                return $this->response->errorInternal('Internal Server');
            }
        } else {
            return generateResponse('Provide Valid Integer Value.',true,true);
        }

    }

}
