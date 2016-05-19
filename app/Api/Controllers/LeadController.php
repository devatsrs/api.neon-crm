<?php

namespace Api\Controllers;

use Api\Model\Lead;
use Api\Model\User;
use Api\Model\Tags;
use App\Http\Requests;
use Dingo\Api\Facade\API;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class LeadController extends BaseController
{

    public function __construct()
    {
        $this->middleware('jwt.auth');
    }

    public function GetLead($id){
        try{
            //$lead = Lead::find($id);
		    $lead = Lead::where(['AccountID'=>$id,"AccountType"=>0])->first();
        }catch (\Exception $ex){
            Log::info($ex);
            return $this->response->errorInternal($ex->getMessage());
        }
        return generateResponse('',false,false,$lead);
    }

    public function GetLeads(){
        try{
            $leads = Lead::getLeadList();
        }catch (\Exception $ex){
            Log::info($ex);
            return $this->response->errorInternal($ex->getMessage());
        }
        return generateResponse('',false,false,$leads);
    }
	
	public function add_lead()
    {
        $data 							= 		Input::all();
        $companyID 						= 		User::get_companyID();
        $data['CompanyID'] 				= 		$companyID;
        $data['IsVendor'] 				= 		isset($data['IsVendor']) ? 1 : 0;
        $data['IsCustomer'] 			= 		isset($data['IsCustomer']) ? 1 : 0;
        $data['AccountType'] 			= 		0;
        $data['AccountName'] 			= 		trim($data['AccountName']);
        $data['Status'] 				= 		isset($data['Status']) ? 1 : 0;
        Lead::$rules['AccountName'] 	= 		'required|unique:tblAccount,AccountName,NULL,CompanyID,CompanyID,'.$data['CompanyID'].'';
        $validator 						= 		Validator::make($data, Lead::$rules);
        $data['created_by'] 			= 		User::get_user_full_name();

        if ($validator->fails()) {
            return generateResponse($validator->errors(),true);
        }
		
		unset($data['token']);
        try{
			$lead 			= 	Lead::create($data);
            return generateResponse('',false,false,$lead);
        }catch (\Exception $ex){
           	  Log::info($ex);
           	 return $this->response->errorInternal($ex->getMessage());
        }
    }
	
    public function update_lead($id)
    {
        $data = Input::all();
        $lead = Lead::find($id);
        $newTags = array_diff(explode(',',isset($data['tags'])?$data['tags']:[]),Tags::getTagsArray());
        if(count($newTags)>0){
            foreach($newTags as $tag){
                Tags::create(array('TagName'=>$tag,'CompanyID'=>User::get_companyID(),'TagType'=>Tags::Account_tag));
            }
        }
        $companyID 				= 	User::get_companyID();
        $data['CompanyID'] 		= 	$companyID;
        $data['IsVendor'] 		= 	isset($data['IsVendor']) ? 1 : 0;
        $data['IsCustomer'] 	= 	isset($data['IsCustomer']) ? 1 : 0;
        $data['updated_by'] 	= 	User::get_user_full_name();
        $data['AccountName'] 	= 	trim(isset($data['AccountName'])?$data['AccountName']:'');
		
        $data['Status'] 		= 	isset($data['Status']) ? 1 : 0;
        $rules = array(
            'Owner' =>      'required',
            'CompanyID' =>  'required',
            'AccountName' => 'required|unique:tblAccount,AccountName,'.$lead->AccountID . ',AccountID,CompanyID,'.$data['CompanyID'],
        );

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            return generateResponse($validator->errors(),true);
        }		
		unset($data['token']);
		try{
	        $lead->update($data);
            return generateResponse("Lead Successfully Updated ");
		}catch (\Exception $ex){
            Log::info($ex);
           	return $this->response->errorInternal($ex->getMessage());
        }
    }
	
}
