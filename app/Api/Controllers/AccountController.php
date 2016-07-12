<?php

namespace Api\Controllers;

use Dingo\Api\Http\Request;
use Api\Model\AccountBalance;
use Api\Model\AccountBalanceHistory;
use Api\Model\DataTableSql;
use Api\Model\User;
use Api\Model\Account;
use Api\Model\Note;
use Api\Model\Invoice;
use Api\Model\Company;
use Api\Model\CompanySetting;
use App\Http\Requests;
use Dingo\Api\Facade\API;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Api\Model\Tags;
use Api\Model\PaymentGateway;
use Api\Model\AccountPaymentProfile;


class AccountController extends BaseController
{

    public function __construct(Request $request)
    {
        $this->middleware('jwt.auth');
        Parent::__Construct($request);
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
            return generateResponse($validator->errors(),true);
        }
        $AccountBalance = AccountBalance::where('AccountID', $post_data['account_id'])->first();
        return generateResponse('',false,false,array('UnbilledAmount' =>$AccountBalance->UnbilledAmount));
    }

    public function UpdateCredit()
    {
        $post_data = Input::all();

        $rules['account_id'] = 'required';
        $rules['credit'] = 'required';
        $rules['action'] = 'required';
        $validator = Validator::make($post_data, $rules);
        if ($validator->fails()) {
            return generateResponse($validator->errors(),true);
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
        return generateResponse('credit added successfully');
    }

    public function DeleteCredit()
    {

        return generateResponse('success');
    }

    public function GetTempCredit()
    {
        return generateResponse('success');
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
            return generateResponse($validator->errors(),true);
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
        return generateResponse('Temporary credit added successfully');

    }
    public function DeleteTempCredit()
    {
        return generateResponse('success');
    }

    public function GetAccountThreshold()
    {
        $post_data = Input::all();

        $rules['account_id'] = 'required';
        $validator = Validator::make($post_data, $rules);
        if ($validator->fails()) {
            return generateResponse($validator->errors(),true);
        }
        $BalanceThreshold = 0;
        try {
            $BalanceThreshold = AccountBalance::getThreshold($post_data['account_id']);
        } catch (\Exception $e) {
            Log::info($e);
            return $this->response->errorInternal($e->getMessage());
        }
        return generateResponse('success',false,false,array('BalanceThreshold' =>$BalanceThreshold));

    }

    public function UpdateAccountThreshold()
    {
        $post_data = Input::all();

        $rules['account_id'] = 'required';
        $rules['balance_threshold'] = 'required';
        $validator = Validator::make($post_data, $rules);
        if ($validator->fails()) {
            return generateResponse($validator->errors(),true);
        }
        try {
            AccountBalance::setThreshold($post_data['account_id'], $post_data['balance_threshold']);
        } catch (\Exception $e) {
            Log::info($e);
            return $this->response->errorInternal($e->getMessage());
        }
        return generateResponse('Balance Warning Threshold updated successfully');

    }
    public function DeleteAccountThreshold()
    {
        return generateResponse('success');
    }
    public function GetAccount($id){
        try{
            $account = Account::find($id);
        }catch (\Exception $ex){
            Log::info($ex);
            return $this->response->errorInternal($ex->getMessage());
        }
        return generateResponse('success',false,false,$account);
    }

	 public function add_note(){

        $data 	= 	Input::all();

	   $rules = array(
            'CompanyID' => 'required',
            'AccountID' => 'required',
            'Note' => 'required',
        );

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            return generateResponse($validator->errors(),true);
        }

		try{
			$data = cleanarray($data,[]);
            $result = Note::create($data);
            return generateResponse('',false,false,$result);
        }catch (\Exception $ex){
            Log::info($ex);
            return $this->response->errorInternal($ex->getMessage());
        }
    }

    public function GetNote()
    {
        $data = Input::all();

        $rules['NoteID'] = 'required';
        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            return generateResponse($validator->errors(),true);
        }
        try {
            $Note = Note::find($data['NoteID']);
        } catch (\Exception $e) {
            Log::info($e);
            return $this->response->errorInternal($e->getMessage());
        }
        return generateResponse('',false,false,$Note);
    }

	 public function UpdateNote(){

       $data 	= 	Input::all();

	   $rules = array(
            'NoteID' => 'required',
            'Note' => 'required',
        );

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            return generateResponse($validator->errors(),true);
        }

		try{
			 $data = cleanarray($data,[]);
			$result = Note::find($data['NoteID'])->update($data);
			$result = Note::find($data['NoteID']);

            return generateResponse('',false,false,$result);
        }catch (\Exception $ex){
            Log::info($ex);
            return $this->response->errorInternal($ex->getMessage());
        }
    }

    public function GetTimeLine()
    {
        $data                       =   Input::all();
        $companyID                  =   User::get_companyID();
        $rules['iDisplayStart']     =   'required|numeric|Min:0';
        $rules['iDisplayLength']    =   'required|numeric';
        $rules['AccountID']         =   'required|numeric';

        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            return generateResponse($validator->errors(),true);
        }
        try {
            $columns =  ['Timeline_type','ActivityTitle','ActivityDescription','ActivityDate','ActivityType','ActivityID','Emailfrom','EmailTo','EmailSubject','EmailMessage','AccountEmailLogID','NoteID','Note','CreatedBy','created_at','updated_at'];
            $query = "call prc_getAccountTimeLine(" . $data['AccountID'] . "," . $companyID . "," . $data['iDisplayStart'] . "," . $data['iDisplayLength'] . ")";
            $result_array = DB::select($query);
            return generateResponse('',false,false,$result_array);
       }
        catch (\Exception $ex){
            Log::info($ex);
            return $this->response->errorInternal($ex->getMessage());
        }
    }

    public function DeleteNote(){
        $data = Input::all();

        $rules['NoteID'] = 'required';
        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            return generateResponse($validator->errors(),true);
        }

        try{
            Note::where(['NoteID'=>$data['NoteID']])->delete();
        }catch (\Exception $ex){
            Log::info($ex);
            return $this->response->errorInternal($ex->getMessage());
        }
        return generateResponse('successfull');

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

        if(Company::isBillingLicence($this->request)) {
            Account::$rules['BillingType'] = 'required';
            Account::$rules['BillingTimezone'] = 'required';
        }

            Account::$rules['AccountName'] = 'required|unique:tblAccount,AccountName,NULL,CompanyID,CompanyID,' . $data['CompanyID'].',AccountType,1';
            Account::$rules['Number'] = 'required|unique:tblAccount,Number,NULL,CompanyID,CompanyID,' . $data['CompanyID'];

            $validator = Validator::make($data, Account::$rules, Account::$messages);

            if ($validator->fails()) {
                return generateResponse($validator->errors(),true);
            }
			
			 if (strpbrk($data['AccountName'], '\/?*:|"<>')) {
                 return generateResponse("Account Name contains illegal character",true);
            }
			
			 $data = cleanarray($data,['token']);

			
			try{
 	         	$account = Account::create($data);
                if (trim(Input::get('Number')) == ''){
                    CompanySetting::setKeyVal('LastAccountNo', $account->Number);
                }
                $data['NextInvoiceDate'] = Invoice::getNextInvoiceDate($account->AccountID);
                $account->update($data);
                return generateResponse('Account Successfully Created',false,false,$account);
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
        
		$data = cleanarray($data,['table-4_length','cardID']);
	
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
			$data = cleanarray($data,['password']);
        }else{
            if($account->VerificationStatus == Account::VERIFIED && $account->Status == 1 ) {
                /* Send mail to Customer */
                $password       = $data['password'];
                $data['password']       = Hash::make($password);
            }
        }
        $data['Number'] = trim($data['Number']);

       if(Company::isBillingLicence($this->request)) {
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
            return generateResponse($validator->errors(),true);
            exit;
        }
		
        $data['CustomerCLI'] = implode(',',array_unique(explode(',',isset($data['CustomerCLI'])?$data['CustomerCLI']:"")));
		$data = cleanarray($data,['token']);
       try{ 
	   		$account->update($data); 
            $data['NextInvoiceDate'] = Invoice::getNextInvoiceDate($id);
            $invoice_count = Account::getInvoiceCount($id);
            if($invoice_count == 0){
                $data['LastInvoiceDate'] = isset($data['BillingStartDate'])?$data['BillingStartDate']:"";
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
           return generateResponse('Account Successfully Updated ');
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
            return generateResponse($validator->errors(),true);
        }		
		
	    try{
		    $account = Account::where(['phone'=>$data['contactNo']])->get();
        }catch (\Exception $ex){
            Log::info($ex);
            return $this->response->errorInternal($ex->getMessage());
        }
        return generateResponse('success',false,false,$account);
	}

    public function GetCreditInfo()
    {
        $post_data = Input::all();
        $rules['AccountID'] = 'required';
        $validator = Validator::make($post_data, $rules);
        if ($validator->fails()) {
            return generateResponse($validator->errors(),true);
        }
        try {
            $AccountBalance = AccountBalance::where('AccountID', $post_data['AccountID'])->first(['AccountID', 'PermanentCredit', 'UnbilledAmount', 'TemporaryCredit', 'TemporaryCreditDateTime', 'BalanceThreshold','BalanceAmount']);
        }catch (\Exception $ex){
            Log::info($ex);
            return $this->response->errorInternal($ex->getMessage());
        }
        return generateResponse('success',false,false,$AccountBalance);
    }

    public function UpdateCreditInfo()
    {
        $post_data = Input::all();
        $rules['AccountID'] = 'required';
        $rules['BalanceThreshold'] = 'required';
        $rules['PermanentCredit'] = 'required';
        $validator = Validator::make($post_data, $rules);
        if ($validator->fails()) {
            return generateResponse($validator->errors(),true);
        }
        $AccountBalancedata = $AccountBalance = array();
        if (isset($post_data['PermanentCredit'])) {
            $AccountBalancedata['PermanentCredit'] = $post_data['PermanentCredit'];
        }
        if (isset($post_data['TemporaryCredit'])) {
            $AccountBalancedata['TemporaryCredit'] = $post_data['TemporaryCredit'];
        }
        if (isset($post_data['TemporaryCreditDateTime'])) {
            $AccountBalancedata['TemporaryCreditDateTime'] = $post_data['TemporaryCreditDateTime'];
        }
        if (isset($post_data['BalanceThreshold'])) {
            $AccountBalancedata['BalanceThreshold'] = $post_data['BalanceThreshold'];
        }
        if(isset($post_data['EmailToCustomer'])){
            $AccountBalancedata['EmailToCustomer'] = $post_data['EmailToCustomer'];
        }

        try {
            if (!empty($AccountBalancedata) && AccountBalance::where('AccountID', $post_data['AccountID'])->count()) {
                $AccountBalance = AccountBalance::where('AccountID', $post_data['AccountID'])->update($AccountBalancedata);
                $AccountBalancedata['AccountID'] = $post_data['AccountID'];
            } elseif (AccountBalance::where('AccountID', $post_data['AccountID'])->count() == 0) {
                $AccountBalancedata['AccountID'] = $post_data['AccountID'];
                AccountBalance::create($AccountBalancedata);
            }
            unset($AccountBalancedata['EmailToCustomer']);
            AccountBalanceHistory::addHistory($AccountBalancedata);
            return generateResponse('Account Successfully Updated');
        } catch (\Exception $e) {
            Log::info($e);
            return $this->response->errorInternal();
        }
    }
    public function GetCreditHistoryGrid(){
        $post_data = Input::all();
        try {
            $companyID = User::get_companyID();
            $rules['iDisplayStart'] = 'required|Min:1';
            $rules['iDisplayLength'] = 'required';
            $rules['iDisplayLength'] = 'required';
            $rules['sSortDir_0'] = 'required';
            $rules['AccountID'] = 'required';
            $validator = Validator::make($post_data, $rules);
            if ($validator->fails()) {
                return generateResponse($validator->errors(),true);
            }
            $post_data['iDisplayStart'] += 1;
            $columns = ['PermanentCredit', 'TemporaryCredit', 'Threshold', 'CreatedBy','created_at'];
            $sort_column = $columns[$post_data['iSortCol_0']];
            $query = "call prc_GetAccountBalanceHistory (" . $companyID . "," . $post_data['AccountID'] . "," . (ceil($post_data['iDisplayStart'] / $post_data['iDisplayLength'])) . " ," . $post_data['iDisplayLength'] . ",'" . $sort_column . "','" . $post_data['sSortDir_0'] . "'";
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
            return $this->response->errorInternal($e->getMessage());
        }
    }


}
