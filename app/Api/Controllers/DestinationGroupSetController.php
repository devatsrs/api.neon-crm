<?php

namespace Api\Controllers;

use Api\Model\DataTableSql;
use Api\Model\DestinationGroupSet;
use Api\Model\User;
use App\Http\Requests;
use Dingo\Api\Facade\API;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class DestinationGroupSetController extends BaseController
{

    public function __construct()
    {
        $this->middleware('jwt.auth');
    }

    /**
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
            $query = "call prc_getDestinationGroupSet(" . $CompanyID . ",'" . $Name . "','" . intval($CodedeckID) . "'," . (ceil($post_data['iDisplayStart'] / $post_data['iDisplayLength'])) . " ," . $post_data['iDisplayLength'] . ",'" . $sort_column . "','" . $post_data['sSortDir_0'] . "'";
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
            return $this->response->errorInternal();
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

        $rules['Name'] = 'required|unique:tblDestinationGroupSet,Name,NULL,CompanyID,CompanyID,' . $CompanyID;
        $rules['CodedeckID'] = 'required';
        $validator = Validator::make($post_data, $rules);
        if ($validator->fails()) {
            return generateResponse($validator->errors(),true);
        }
        try {
            $insertdata = array();
            $insertdata['Name'] = $post_data['Name'];
            $insertdata['CodedeckID'] = $post_data['CodedeckID'];
            $insertdata['CompanyID'] = $CompanyID;
            $insertdata['CreatedBy'] = User::get_user_full_name();
            $insertdata['created_at'] = get_currenttime();
            $DestinationGroupSet = DestinationGroupSet::create($insertdata);
            return generateResponse('DestinationGroup Set added successfully');
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
    public function Delete($DestinationGroupSetID)
    {
        try {
            if (intval($DestinationGroupSetID) > 0) {
                if (!DestinationGroupSet::checkForeignKeyById($DestinationGroupSetID)) {
                    try {
                        $result = DestinationGroupSet::find($DestinationGroupSetID)->delete();
                        if ($result) {
                            return generateResponse('Destination Group Set Successfully Deleted');
                        } else {
                            return generateResponse('Problem Deleting Destination Group Set.',true,true);
                        }
                    } catch (\Exception $ex) {
                        return generateResponse('Destination Group Set is in Use, You cant delete this Destination Group Set.',true,true);
                    }
                } else {
                    return generateResponse('Destination Group Set is in Use, You cant delete this Destination Group Set.',true,true);
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
     * Update Destination Group ID
     *
     * @param $DestinationGroupID
     */
    public function Update($DestinationGroupSetID)
    {
        if ($DestinationGroupSetID > 0) {
            $post_data = Input::all();
            $CompanyID = User::get_companyID();

            $rules['Name'] = 'required|unique:tblDestinationGroupSet,Name,' . $DestinationGroupSetID . ',DestinationGroupSetID,CompanyID,' . $CompanyID;
            $rules['CodedeckID'] = 'required';
            $validator = Validator::make($post_data, $rules);
            if ($validator->fails()) {
                return generateResponse($validator->errors(),true);
            }
            try {
                try {
                    $DestinationGroupSet = DestinationGroupSet::findOrFail($DestinationGroupSetID);
                } catch (\Exception $e) {
                    $reponse_data = ['status' => 'failed', 'message' => 'Destination Group not found', 'status_code' => 200];
                    return API::response()->array($reponse_data)->statusCode(200);
                }
                $updatedata = array();
                if (isset($post_data['Name'])) {
                    $updatedata['Name'] = $post_data['Name'];
                }
                if (isset($post_data['CodedeckID'])) {
                    $updatedata['CodedeckID'] = $post_data['CodedeckID'];
                }
                $DestinationGroupSet->update($updatedata);
                return generateResponse('Destination Group Set updated successfully');
            } catch (\Exception $e) {
                Log::info($e);
                return $this->response->errorInternal('Internal Server');
            }
        } else {
            return generateResponse('Provide Valid Integer Value.',true,true);
        }

    }

}
