<?php

namespace Api\Controllers;

use Api\Model\AccountBalance;
use Api\Model\Account;
use Api\Model\Company;
use Api\Model\CompanySetting;
use Api\Model\Invoice;
use App\Http\Requests;
use Dingo\Api\Facade\API;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Api\Model\Note;
use Api\Model\User;
use Api\Model\Tags;
use Api\Model\PaymentGateway;
use Api\Model\AccountPaymentProfile;
use Api\Model\DataTableSql;

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
     *  get/post variables
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
    public function DeleteCredit(){

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
        return API::response()->array(['status' => 'success', 'data'=>['BalanceThreshold'=>$BalanceThreshold] , 'status_code' => 200])->statusCode(200);

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
            AccountBalance::setThreshold($post_data['account_id'],$post_data['balance_threshold']);
        } catch (\Exception $e) {
            Log::info($e);
            return $this->response->errorInternal($e->getMessage());
        }
        return API::response()->array(['status' => 'success', 'message' => 'Balance Warning Threshold updated successfully' , 'status_code' => 200])->statusCode(200);

    }
    public function DeleteAccountThreshold()
    {
        return API::response()->array(['status' => 'success', 'message' => 'success', 'status_code' => 200])->statusCode(200);
    }
    public function GetAccount($id){
        try{
            $account = Account::find($id);
        }catch (\Exception $ex){
            Log::info($ex);
            return $this->response->errorInternal($ex->getMessage());
        }
        $reponse_data = ['status' => 'success', 'data' => ['result' => $account], 'status_code' => 200];
        return API::response()->array($reponse_data)->statusCode(200);
    }
	
	public function add_account()
	{
            $data 				 	=	 Input::all();
            $companyID 			 	=	 User::get_companyID();
            $data['CompanyID'] 	 	= 	 $companyID;
            $data['AccountType'] 	= 	1;
            $data['IsVendor'] 	 	= 	isset($data['IsVendor']) ? 1 : 0;
            $data['IsCustomer']  	= 	isset($data['IsCustomer']) ? 1 : 0;
            $data['created_by']  	= 	User::get_user_full_name();
            $data['AccountType'] 	= 	1;
            if(isset($data['AccountName'])){
				$data['AccountName'] 	= 	trim($data['AccountName']);
			}
			
            if (isset($data['TaxRateId'])) {
                $data['TaxRateId'] = implode(',', array_unique($data['TaxRateId']));
            }
			
             $data['Status'] = isset($data['Status']) ? 1 : 0;

            if (empty($data['Number'])) {
                $data['Number'] = Account::getLastAccountNo();
            }
            $data['Number'] = trim($data['Number']);

        if(Company::isBillingLicence()) {
            Account::$rules['BillingType'] = 'required';
            Account::$rules['BillingTimezone'] = 'required';
        }

            Account::$rules['AccountName'] = 'required|unique:tblAccount,AccountName,NULL,CompanyID,CompanyID,' . $data['CompanyID'].',AccountType,1';
            Account::$rules['Number'] = 'required|unique:tblAccount,Number,NULL,CompanyID,CompanyID,' . $data['CompanyID'];

            $validator = Validator::make($data, Account::$rules, Account::$messages);

            if ($validator->fails()) {
				return $this->response->error($validator->errors(),'432');
            }
			
			 if (strpbrk($data['AccountName'], '\/?*:|"<>')) {
				 return API::response()->array(['status' => 'failed', 'message' => 'Account Name contains illegal character', 'status_code' => 432])->statusCode(432);
            }
			
			unset($data['token']);
			
			try{
 	         	$account = Account::create($data);
                if (trim(Input::get('Number')) == ''){
                    CompanySetting::setKeyVal('LastAccountNo', $account->Number);
                }
                $data['NextInvoiceDate'] = Invoice::getNextInvoiceDate($account->AccountID);
                $account->update($data);
            	 $reponse_data = ['status' => 'success', "message" => "Account Successfully Created",'LastID' => $account->AccountID, 'data' => ['result' => $account], 'status_code' => 200];
            return API::response()->array($reponse_data)->statusCode(200);				
            }catch (\Exception $ex){
                 Log::info($ex);
           		 return $this->response->errorInternal($ex->getMessage());
            }           
	}
	
	public function update_account($id) {
        $data 		= 	Input::all();
        $account 	= 	Account::find($id);
        $newTags 	= 	array_diff(explode(',',isset($data['tags'])?$data['tags']:''),Tags::getTagsArray());
        if(count($newTags)>0){
            foreach($newTags as $tag){
                Tags::create(array('TagName'=>$tag,'CompanyID'=>User::get_companyID(),'TagType'=>Tags::Account_tag));
            }
        }
        $message 				= 	$password = "";
        $companyID 				= 	User::get_companyID();
        $data['CompanyID'] 		= 	$companyID;
        $data['IsVendor'] 		= 	isset($data['IsVendor']) ? 1 : 0;
        $data['IsCustomer'] 	= 	isset($data['IsCustomer']) ? 1 : 0;
        $data['updated_by'] 	= 	User::get_user_full_name();
		$data['AccountName'] 	= 	trim(isset($data['AccountName'])?$data['AccountName']:'');

        $shipping = array('firstName'=>$account['FirstName'],
            'lastName'=>$account['LastName'],
            'address'=> isset($data['Address1'])?$data['Address1']:"",
            'city'=> isset($data['City'])?$data['City']:"",
            'state'=>isset($data['state'])?$account['state']:"",
            'zip'=>isset($data['PostCode'])?$data['PostCode']:"",
            'country'=>isset($data['Country'])?$data['Country']:"",
            'phoneNumber'=>$account['Mobile']);
        unset($data['table-4_length']);
        unset($data['cardID']);

        if(isset($data['TaxRateId'])) {
            $data['TaxRateId'] = implode(',', array_unique($data['TaxRateId']));
        }
        if (strpbrk($data['AccountName'],'\/?*:|"<>')) {
				return API::response()->array(['status' => 'failed', 'message' => 'Account Name contains illegal character', 'status_code' => 432])->statusCode(432);
        }
        $data['Status'] = isset($data['Status']) ? 1 : 0;
        if(!isset($data['Number']) || trim($data['Number']) == ''){
            $data['Number'] = Account::getLastAccountNo();
        }

        if(empty($data['password'])){ /* if empty, dont update password */
            unset($data['password']);
        }else{
            if($account->VerificationStatus == Account::VERIFIED && $account->Status == 1 ) {
                /* Send mail to Customer */
                $password       = $data['password'];
                $data['password']       = Hash::make($password);
            }
        }
        $data['Number'] = trim($data['Number']);

       if(Company::isBillingLicence()) {
            Account::$rules['BillingType'] = 'required';
            Account::$rules['BillingTimezone'] = 'required';
            $icount = Invoice::where(["AccountID" => $id])->count();
            if($icount>0){
                Account::$rules['InvoiceTemplateID'] = 'required';
            }
        }

        Account::$rules['AccountName'] = 'required|unique:tblAccount,AccountName,' . $account->AccountID . ',AccountID,CompanyID,'.$data['CompanyID'].',AccountType,1';
        Account::$rules['Number'] = 'required|unique:tblAccount,Number,' . $account->AccountID . ',AccountID,CompanyID,'.$data['CompanyID'];

        $validator = Validator::make($data, Account::$rules,Account::$messages);

        if ($validator->fails()) {
			return $this->response->error($validator->errors(),'432');
            exit;
        }
		
        $data['CustomerCLI'] = implode(',',array_unique(explode(',',isset($data['CustomerCLI'])?$data['CustomerCLI']:"")));
		unset($data['token']);
       try{ 
	   		$account->update($data); 
            $data['NextInvoiceDate'] = Invoice::getNextInvoiceDate($id);
            $invoice_count = Account::getInvoiceCount($id);
            if($invoice_count == 0){
                $data['LastInvoiceDate'] = $data['BillingStartDate'];
            }
            $account->update($data);
            if(trim(Input::get('Number')) == ''){
                CompanySetting::setKeyVal('LastAccountNo',$account->Number);
            }
            if(isset($data['password'])) {
                $this->sendPasswordEmail($account, $password, $data);
            }
            $PaymentGatewayID = PaymentGateway::where(['Title'=>PaymentGateway::$gateways['Authorize']])
                ->where(['CompanyID'=>$companyID])
                ->pluck('PaymentGatewayID');
            $PaymentProfile = AccountPaymentProfile::where(['AccountID'=>$id])
                ->where(['CompanyID'=>$companyID])
                ->where(['PaymentGatewayID'=>$PaymentGatewayID])
                ->first();
				
            if(!empty($PaymentProfile)){
                $options = json_decode($PaymentProfile->Options);
                $ProfileID = $options->ProfileID;
                $ShippingProfileID = $options->ShippingProfileID;

                //If using Authorize.net
                $isAuthorizedNet = getenv('AMAZONS3_KEY');
                if(!empty($isAuthorizedNet)) {
                    $AuthorizeNet = new AuthorizeNet();
                    $result = $AuthorizeNet->UpdateShippingAddress($ProfileID, $ShippingProfileID, $shipping);
                }
            }
			 $reponse_data = ['status' => 'success', "message" => "Account Successfully Updated ". $message , 'status_code' => 200];
            return API::response()->array($reponse_data)->statusCode(200);

			
        }catch (\Exception $ex){
                 Log::info($ex);
           		 return $this->response->errorInternal($ex->getMessage());
        } 
        //return Redirect::route('accounts.index')->with('success_message', 'Accounts Successfully Updated');;
    }
	
	public function GetAccountLeadByContactNumber()
	{
		$data 				= 		Input::all();
		
		$rules = array(
            'contactNo' =>      'required|numeric',
        );

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            return $this->response->error($validator->errors(),'432');
        }		
		
	    try{
		    $account = Account::where(['Number'=>$data['contactNo']])->get();			
        }catch (\Exception $ex){
            Log::info($ex);
            return $this->response->errorInternal($ex->getMessage());
        }
        return API::response()->array(['status' => 'success', 'data'=>['result'=>$account] , 'status_code' => 200])->statusCode(200);
    
	}
	
	
}
