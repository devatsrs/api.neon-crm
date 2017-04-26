<?php

namespace Api\Controllers;

use Api\Model\DataTableSql;
use Api\Model\Discount;
use Api\Model\DiscountScheme;
use Api\Model\User;
use App\Http\Requests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class DiscountController extends BaseController
{

    public function __construct()
    {
        $this->middleware('jwt.auth');
    }

    /**
     * Show Discount
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
            $rules['DiscountPlanID'] = 'required';
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
            $query = "call prc_getDiscount(" . $CompanyID . ",'".intval($post_data['DiscountPlanID'])."','" . $Name . "'," . (ceil($post_data['iDisplayStart'] / $post_data['iDisplayLength'])) . " ," . $post_data['iDisplayLength'] . ",'" . $sort_column . "','" . $post_data['sSortDir_0'] . "'";
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
     * Add new Discount
     *
     * @Store('/')
     */
    public function Store()
    {
        $post_data = Input::all();
        $CompanyID = User::get_companyID();

        //discount
        $rules['DestinationGroupID'] = 'required|numeric';
        $rules['DiscountPlanID'] = 'required|numeric';
        $rules['Service'] = 'required|numeric';

        //discount scheme
        $rules['Threshold'] = 'required|numeric';
        $rules['Discount'] = 'required|numeric';
        $rules['Service'] = 'required|numeric';

        $validator = Validator::make($post_data, $rules);
        if ($validator->fails()) {
            return generateResponse($validator->errors(),true);
        }
        if(Discount::where(array('DiscountPlanID'=>$post_data['DiscountPlanID'],'DestinationGroupID'=>$post_data['DestinationGroupID']))->count()){
            return generateResponse('Destination Group Already Taken.',true,true);
        }

        try {
            $discountdata = array();
            $discountdata['DestinationGroupID'] = $post_data['DestinationGroupID'];
            $discountdata['DiscountPlanID'] = $post_data['DiscountPlanID'];
            $discountdata['Service'] = $post_data['Service'];
            $discountdata['CreatedBy'] = User::get_user_full_name();
            $discountdata['created_at'] = get_currenttime();
            $Discount = Discount::create($discountdata);

            $discountschemedata = array();
            $discountschemedata['Discount'] = $post_data['Discount'];
            $discountschemedata['Threshold'] = $post_data['Threshold']*60;
            $discountschemedata['DiscountID'] = $Discount->DiscountID;
            $discountschemedata['Unlimited'] = isset($post_data['Unlimited'])?1:0;
            $discountschemedata['CreatedBy'] = User::get_user_full_name();
            $discountschemedata['created_at'] = get_currenttime();
            DiscountScheme::create($discountschemedata);


            return generateResponse('Discount added successfully');
        } catch (\Exception $e) {
            Log::info($e);
            return $this->response->errorInternal('Internal Server');
        }
    }

    /**
     * Delete Discount
     *
     * @param $DiscountID
     */
    public function Delete($DiscountID)
    {
        try {
            if (intval($DiscountID) > 0) {
                if (!Discount::checkForeignKeyById($DiscountID)) {
                    try {
                        DB::beginTransaction();
                        $result = DiscountScheme::where('DiscountID',$DiscountID)->delete();
                        $result = Discount::find($DiscountID)->delete();
                        DB::commit();
                        if ($result) {
                            return generateResponse('Discount Successfully Deleted');
                        } else {
                            return generateResponse('Problem Deleting Discount.',true,true);
                        }
                    } catch (\Exception $ex) {
                        return generateResponse('Discount is in Use, You cant delete this Discount.',true,true);
                    }
                } else {
                    return generateResponse('Discount is in Use, You cant delete this Discount.',true,true);
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
     * Update Discount
     *
     * @param $DiscountID
     */
    public function Update($DiscountID)
    {
        if ($DiscountID > 0) {
            $post_data = Input::all();
            $CompanyID = User::get_companyID();

            //discount
            $rules['DestinationGroupID'] = 'required|numeric';
            $rules['DiscountPlanID'] = 'required|numeric';
            $rules['DiscountSchemeID'] = 'required|numeric';
            $rules['Service'] = 'required|numeric';

            //discount scheme
            $rules['Threshold'] = 'required|numeric';
            $rules['Discount'] = 'required|numeric';
            $rules['Service'] = 'required|numeric';

            $validator = Validator::make($post_data, $rules);
            if ($validator->fails()) {
                return generateResponse($validator->errors(),true);
            }
            if(Discount::where('DiscountID','!=',$DiscountID)->where(array('DiscountPlanID'=>$post_data['DiscountPlanID'],'DestinationGroupID'=>$post_data['DestinationGroupID']))->count()){
                return generateResponse('Destination Group Already Taken.',true,true);
            }
            try {

                try {
                    $DiscountSchemeID = $post_data['DiscountSchemeID'];
                    $Discount = Discount::findOrFail($DiscountID);
                    $DiscountScheme = DiscountScheme::findOrFail($DiscountSchemeID);
                } catch (\Exception $e) {
                    Log::info($e);
                    return generateResponse('Discount not found.',true,true);
                }
                $discountdata = array();
                $discountdata['DestinationGroupID'] = $post_data['DestinationGroupID'];
                $discountdata['DiscountPlanID'] = $post_data['DiscountPlanID'];
                $discountdata['Service'] = $post_data['Service'];
                $discountdata['UpdatedBy'] = User::get_user_full_name();
                $Discount->update($discountdata);

                $discountschemedata = array();
                $discountschemedata['Discount'] = $post_data['Discount'];
                $discountschemedata['Threshold'] = $post_data['Threshold']*60;
                $discountschemedata['DiscountID'] = $Discount->DiscountID;
                $discountschemedata['Unlimited'] = isset($post_data['Unlimited'])?1:0;
                $discountschemedata['UpdatedBy'] = User::get_user_full_name();
                $DiscountScheme->update($discountschemedata);

                return generateResponse('Discount updated successfully');
            } catch (\Exception $e) {
                Log::info($e);
                return $this->response->errorInternal('Internal Server');
            }
        } else {
            return generateResponse('Provide Valid Integer Value.',true,true);
        }

    }

}
