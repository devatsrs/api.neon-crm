<?php

namespace Api\Controllers;

use Api\Model\DataTableSql;
use Api\Model\DestinationGroup;
use Api\Model\DestinationGroupCode;
use Api\Model\User;
use App\Http\Requests;
use Dingo\Api\Facade\API;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class DestinationGroupController extends BaseController
{

    public function __construct()
    {
        $this->middleware('jwt.auth');
    }/**
 * Show Destination Group
 *
 * Get a JSON representation of all the Group
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
            $rules['sSortDir_0'] = 'required';
            $rules['DestinationGroupSetID'] = 'required';
            $validator = Validator::make($post_data, $rules);
            if ($validator->fails()) {
                return generateResponse($validator->errors(),true);
            }
            $post_data['iDisplayStart'] += 1;
            $columns = ['Name', 'CreatedBy', 'created_at'];
            $Name = $DestinationGroupSetID = '';
            if (isset($post_data['Name'])) {
                $Name = $post_data['Name'];
            }
            if (isset($post_data['DestinationGroupSetID'])) {
                $DestinationGroupSetID = $post_data['DestinationGroupSetID'];
            }
            $sort_column = $columns[$post_data['iSortCol_0']];
            $query = "call prc_getDestinationGroup(" . $CompanyID . ",'" . intval($DestinationGroupSetID) . "','" . $Name . "'," . (ceil($post_data['iDisplayStart'] / $post_data['iDisplayLength'])) . " ," . $post_data['iDisplayLength'] . ",'" . $sort_column . "','" . $post_data['sSortDir_0'] . "'";
            if (isset($post_data['Export']) && $post_data['Export'] == 1) {
                $result = DB::select($query . ',1)');
            } else {
                $query .= ',0)';
                $result = DataTableSql::of($query)->make();
            }
            Log::info($query);
            return generateResponse('',false,false,$result);
        } catch (\Exception $e) {
            Log::info($e);
            return $this->response->errorInternal('Internal Server');
        }
    }

    /**
     * Show Destination Group
     *
     * Get a JSON representation of all the Group
     *
     * @CodeDataGrid('/')
     */
    public function CodeDataGrid()
    {
        $post_data = Input::all();
        try {

            $rules['iDisplayStart'] = 'required|Min:1';
            $rules['DestinationGroupSetID'] = 'required';
            $rules['iDisplayLength'] = 'required';
            $validator = Validator::make($post_data, $rules);
            if ($validator->fails()) {
                return generateResponse($validator->errors(),true);
            }
            $DestinationGroupID = $CountryID = 0;
            $Code = $Description = '';
            $post_data['iDisplayStart'] += 1;
            if (isset($post_data['DestinationGroupID'])) {
                $DestinationGroupID = $post_data['DestinationGroupID'];
            }
            if (isset($post_data['Code'])) {
                $Code = $post_data['Code'];
            }
            if (isset($post_data['Description'])) {
                $Description = $post_data['Description'];
            }
            if (isset($post_data['CountryID'])) {
                $CountryID = (int)$post_data['CountryID'];
            }
            $query = "call prc_getDestinationCode(" . intval($post_data['DestinationGroupSetID']) . "," . intval($DestinationGroupID) . ",'".$CountryID."','".$Code."','".$Description."','".(ceil($post_data['iDisplayStart'] / $post_data['iDisplayLength']))."','".$post_data['iDisplayLength']."')";
            $result = DataTableSql::of($query)->make();

            Log::info($query);
            return generateResponse('',false,false,$result);
        } catch (\Exception $e) {
            Log::info($e);
            return $this->response->errorInternal('Internal Server');
        }
    }

    /**
     * Add new Destination Group
     *
     * @Store('/')
     */
    public function Store()
    {
        $post_data = Input::all();
        $CompanyID = User::get_companyID();

        $rules['Name'] = 'required|unique:tblDestinationGroup,Name,NULL,CompanyID,CompanyID,' . $CompanyID;
        $rules['DestinationGroupSetID'] = 'required';
        $validator = Validator::make($post_data, $rules);
        if ($validator->fails()) {
            return generateResponse($validator->errors(),true);
        }
        try {
            $insertdata = array();
            $insertdata['Name'] = $post_data['Name'];
            $insertdata['DestinationGroupSetID'] = $post_data['DestinationGroupSetID'];
            $insertdata['CompanyID'] = $CompanyID;
            $insertdata['CreatedBy'] = User::get_user_full_name();
            $insertdata['created_at'] = get_currenttime();
            $DestinationGroup = DestinationGroup::create($insertdata);


            return generateResponse('Destination Group added successfully');
        } catch (\Exception $e) {
            Log::info($e);
            return $this->response->errorInternal('Internal Server');
        }
    }

    /**
     * Delete Destination Group ID
     *
     * @param $DestinationGroupID
     */
    public function Delete($DestinationGroupID)
    {
        try {
            if (intval($DestinationGroupID) > 0) {
                if (!DestinationGroup::checkForeignKeyById($DestinationGroupID)) {
                    try {
                        DB::beginTransaction();
                        $result = DestinationGroupCode::where('DestinationGroupID',$DestinationGroupID)->delete();
                        $result = DestinationGroup::find($DestinationGroupID)->delete();
                        DB::commit();
                        if ($result) {
                            return generateResponse('Destination Group Successfully Deleted');
                        } else {
                            return generateResponse('Problem Deleting Destination Group.',true,true);
                        }
                    } catch (\Exception $ex) {
                        try {
                            DB::rollback();
                        } catch (\Exception $err) {
                            Log::error($err);
                        }
                        Log::info('Destination Group is in Use');
                        return generateResponse('Destination Group is in Use, You cant delete this Destination Group.',true,true);
                    }
                } else {
                    return generateResponse('Destination Group is in Use, You cant delete this Destination Group.',true,true);
                }
            } else {
                return generateResponse("Provide Valid Integer Value",true,true);
            }
        } catch (\Exception $e) {
            Log::info($e);
            return $this->response->errorInternal('Internal Server');
        }
    }

    /**
     * Update Destination Group ID
     *
     * @param $DestinationGroupID
     */
    public function Update($DestinationGroupID)
    {
        if ($DestinationGroupID > 0) {
            $post_data = Input::all();
            $CompanyID = User::get_companyID();

            //$rules['Name'] = 'required|unique:tblDestinationGroup,Name,' . $DestinationGroupID . ',DestinationGroupID,CompanyID,' . $CompanyID;
            $rules['DestinationGroupID'] = 'required';
            $validator = Validator::make($post_data, $rules);
            if ($validator->fails()) {
                return generateResponse($validator->errors(),true);
            }
            try {
                try {
                    $DestinationGroup = DestinationGroup::findOrFail($DestinationGroupID);
                } catch (\Exception $e) {
                    $reponse_data = ['status' => 'failed', 'message' => 'Destination Group not found', 'status_code' => 200];
                    return API::response()->array($reponse_data)->statusCode(200);
                }
                $updatedata = array();
                if (isset($post_data['Name'])) {
                    $updatedata['Name'] = $post_data['Name'];
                }
                $RateID= $Description =  $Code ='';
                $CountryID = 0;
                if(isset($post_data['RateID'])) {
                    $RateID = $post_data['RateID'];
                }
                if(isset($post_data['Code'])) {
                    $Code = $post_data['Code'];
                }
                if(isset($post_data['CountryID'])) {
                    $CountryID = intval($post_data['CountryID']);
                }
                if(isset($post_data['Description'])) {
                    $Description = $post_data['Description'];
                }
                $DestinationGroup->update($updatedata);
                $insert_query = "call prc_insertUpdateDestinationCode(?,?,?,?,?)";
                Log::info($insert_query);
                DB::statement($insert_query,array(intval($DestinationGroup->DestinationGroupID),$RateID,$CountryID,$Code,$Description));
                return generateResponse('Destination Group updated successfully');
            } catch (\Exception $e) {
                Log::info($e);
                return $this->response->errorInternal('Internal Server');
            }
        } else {
            return generateResponse('Provide Valid Integer Value.',true,true);
        }

    }
    /**
     * Update Destination Group ID
     *
     * @param $DestinationGroupID
     */
    public function UpdateName($DestinationGroupID)
    {
        if ($DestinationGroupID > 0) {
            $post_data = Input::all();
            $CompanyID = User::get_companyID();

            $rules['Name'] = 'required|unique:tblDestinationGroup,Name,' . $DestinationGroupID . ',DestinationGroupID,CompanyID,' . $CompanyID;
            $rules['DestinationGroupID'] = 'required';
            $validator = Validator::make($post_data, $rules);
            if ($validator->fails()) {
                return generateResponse($validator->errors(),true);
            }
            try {
                try {
                    $DestinationGroup = DestinationGroup::findOrFail($DestinationGroupID);
                } catch (\Exception $e) {
                    $reponse_data = ['status' => 'failed', 'message' => 'Destination Group not found', 'status_code' => 200];
                    return API::response()->array($reponse_data)->statusCode(200);
                }
                $updatedata = array();
                if (isset($post_data['Name'])) {
                    $updatedata['Name'] = $post_data['Name'];
                }
                $DestinationGroup->update($updatedata);
                return generateResponse('Destination Group updated successfully');
            } catch (\Exception $e) {
                Log::info($e);
                return $this->response->errorInternal('Internal Server');
            }
        } else {
            return generateResponse('Provide Valid Integer Value.',true,true);
        }

    }




}
