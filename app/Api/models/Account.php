<?php
namespace Api\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Api\Model\CompanySetting;
use Api\Model\Integration;
use Api\Model\IntegrationConfiguration;

use Api\Models\DynamicFields;
use Api\Models\DynamicFieldsValue;

class Account extends Model
{
    protected $guarded = array("AccountID");

    protected $table = 'tblAccount';

    protected $primaryKey = "AccountID";
	
	public static $SupportSlug	=	'support';

    const  NOT_VERIFIED = 0;
    const  PENDING_VERIFICATION = 1;
    const  VERIFIED = 2;
    public static $doc_status = array(self::NOT_VERIFIED => 'Not Verified', self::PENDING_VERIFICATION => 'Pending Verification', self::VERIFIED => 'Verified');

    const  DETAIL_CDR = 1;
    const  SUMMARY_CDR = 2;
    const  NO_CDR = 3;
    public static $cdr_type = array('' => 'Select a CDR Type', self::DETAIL_CDR => 'Detail CDR', self::SUMMARY_CDR => 'Summary CDR');

    const DynamicFields_Type = 'account';

    public static $rules = array(
        'Owner' => 'required',
        'CompanyID' => 'required',
        'Country' => 'required',
        'Number' => 'required|unique:tblAccount,Number',
        'AccountName' => 'required|unique:tblAccount,AccountName',
        'CurrencyId' => 'required',

    );

    public static $messages = array('CurrencyId.required' => 'The currency field is required');


    public static function getCompanyNameByID($id = 0)
    {

        return $AccountName = Account::where(["AccountID" => $id])->pluck('AccountName');

        //return $AccountName = Account::find($id)->pluck('AccountName');


        //return (isset($Acc[0]->AccountName))?$Acc[0]->AccountName:"";


    }

    public static function getCurrency($id = 0)
    {
        $currency = Account::select('Code')->join('tblCurrency', 'tblAccount.CurrencyId', '=', 'tblCurrency.CurrencyId')->where(['AccountID' => intval($id)])->first();
        if (!empty($currency)) {
            return $currency->Code;
        }
        return "";
    }

    public static function getRecentAccounts($limit)
    {
        $companyID = User::get_companyID();
        $account = Account::Where(["AccountType" => 1, "CompanyID" => $companyID, "Status" => 1])
            ->orderBy("tblAccount.AccountID", "desc")
            ->take($limit)
            ->get();

        return $account;

    }

    public static function getAccountsOwnersByRole()
    {
        $companyID = User::get_companyID();
        if (User::is('AccountManager')) {
            $UserID = User::get_userID();
            $account_owners = DB::table('tblAccount')->where(["Owner" => $UserID, "AccountType" => 1, "CompanyID" => $companyID, "Status" => 1])->orderBy('FirstName', 'asc')->get();
        } else {
            $account_owners = DB::table('tblAccount')->where(["AccountType" => 1, "CompanyID" => $companyID, "Status" => 1])->orderBy('FirstName', 'asc')->get();
        }

        return $account_owners;
    }

    public static function getLastAccountNo()
    {
        $LastAccountNo = CompanySetting::getKeyVal('LastAccountNo');
        if ($LastAccountNo == 'Invalid Key') {
            $LastAccountNo = 1;//Account::where(["CompanyID"=> User::get_companyID()])->max('Number');
            CompanySetting::setKeyVal('LastAccountNo', $LastAccountNo);
        }
        while (Account::where(["CompanyID" => User::get_companyID(), 'Number' => $LastAccountNo])->count() >= 1) {
            $LastAccountNo++;
        }
        return $LastAccountNo;
    }

    public static function getAccountIDList($data = array())
    {
        /*if (User::is('AccountManager')) {
            $data['Owner'] = User::get_userID();
        }*/
        $data['Status'] = 1;
        if (!isset($data['AccountType'])) {
            $data['AccountType'] = 1;
            $data['VerificationStatus'] = Account::VERIFIED;
        }
        $data['CompanyID'] = User::get_companyID();
        $row = Account::where($data)->select(array('AccountName', 'AccountID'))->orderBy('AccountName')->lists('AccountName', 'AccountID')->all();

        return $row;
    }

    public static function getCustomersGridPopup($opt = array())
    {

        if (isset($opt["CompanyID"]) && $opt["CompanyID"] > 0) {

            $companyID = $opt["CompanyID"];// User::get_companyID();

            $AccountID = isset($opt["AccountID"]) ? $opt["AccountID"] : 0;// Exclude AccountID
            if (isset($opt['Trunk'])) {
                $customer = Account::join('tblCustomerTrunk', 'tblCustomerTrunk.AccountID', '=', 'tblAccount.AccountID')
                    ->where(["tblAccount.CompanyID" => $companyID, 'IsCustomer' => 1, 'AccountType' => 1,
                        'tblAccount.Status' => 1, 'tblCustomerTrunk.Status' => 1]);
            } else {
                $customer = Account::where(["CompanyID" => $companyID, 'IsCustomer' => 1, 'AccountType' => 1, 'Status' => 1]);
            }
            /** only show his own accounts to Account Manager **/
            if (User::is('AccountManager')) {
                $UserID = User::get_userID();//  //$data['OwnerFilter'];
                $customer->where('Owner', $UserID);
            }

            /** Owner Dropdown filter for Admin  **/
            if (isset($opt['OwnerFilter']) && $opt['OwnerFilter'] != 0) {
                $UserID = $opt['OwnerFilter'];
                $customer->where('Owner', $UserID);
            }

            /** don't list current Account - used in CustomerRate **/
            if (isset($AccountID) && $AccountID > 0) {
                $customer->where('tblAccount.AccountID', '<>', $AccountID);
            }

            /** show only accounts having same codedeckid like currenct Account **/
            if (isset($opt['Trunk'])) {
                $codedeckid = CustomerTrunk::where(['AccountID' => $AccountID, 'TrunkID' => $opt['Trunk']])->pluck('CodeDeckId');
                $customer->where('tblCustomerTrunk.TrunkID', '=', $opt['Trunk']);
                $customer->where('tblCustomerTrunk.CodeDeckId', '=', $codedeckid);
            }
            /** Search Account Name **/
            if (isset($opt['Customer']) && $opt['Customer'] != '') {
                $customer->where('AccountName', 'LIKE', $opt['Customer'] . '%');
            }

            $customer->select(['tblAccount.AccountID', 'AccountName'])->distinct();

            return Datatables::of($customer)->make();
        }
    }

    public static function getAccountManager($AccountID)
    {
        $managerinfo = Account::join('tblUser', 'tblUser.UserID', '=', 'tblAccount.Owner')->where(array('AccountID' => $AccountID))->first(['tblUser.FirstName', 'tblUser.LastName', 'tblUser.EmailAddress', 'tblaccount.AccountName']);
        return $managerinfo;

    }

    // ignore item invoice
    public static function getInvoiceCount($AccountID)
    {
        return (int)Invoice::where(array('AccountID' => $AccountID))
            ->Where(function ($query) {
                $query->whereNull('ItemInvoice')
                    ->orwhere('ItemInvoice', '!=', 1);

            })->count();
    }

    public static function getOutstandingAmount($CompanyID, $AccountID, $decimal_places = 2)
    {

        $query = "call prc_getAccountOutstandingAmount ('" . $CompanyID . "',  '" . $AccountID . "')";
        $AccountOutstandingResult = DataTableSql::of($query, 'sqlsrv2')->getProcResult(array('AccountOutstanding'));
        $AccountOutstanding = $AccountOutstandingResult['data']['AccountOutstanding'];
        if (count($AccountOutstanding) > 0) {
            $AccountOutstanding = array_shift($AccountOutstanding);
            $Outstanding = $AccountOutstanding->Outstanding;
            $Outstanding = number_format($Outstanding, $decimal_places);
            return $Outstanding;
        }
    }

    public static function getOutstandingInvoiceAmount($CompanyID, $AccountID, $Invoiceids, $decimal_places = 2, $PaymentDue = 0)
    {
        $Outstanding = 0;
        $unPaidInvoices = DB::connection('sqlsrv2')->select('call prc_getPaymentPendingInvoice (' . $CompanyID . ',' . $AccountID . ',' . $PaymentDue . ')');
        foreach ($unPaidInvoices as $Invoiceid) {
            if (in_array($Invoiceid->InvoiceID, explode(',', $Invoiceids))) {
                $Outstanding += $Invoiceid->RemaingAmount;
            }
        }
        $Outstanding = number_format($Outstanding, $decimal_places, '.', '');
        return $Outstanding;
    }

    public static function getFullAddress($Account)
    {
        $Address = "";
        $Address .= !empty($Account->Address1) ? $Account->Address1 . ',' . PHP_EOL : '';
        $Address .= !empty($Account->Address2) ? $Account->Address2 . ',' . PHP_EOL : '';
        $Address .= !empty($Account->Address3) ? $Account->Address3 . ',' . PHP_EOL : '';
        $Address .= !empty($Account->City) ? $Account->City . ',' . PHP_EOL : '';
        $Address .= !empty($Account->PostCode) ? $Account->PostCode . ',' . PHP_EOL : '';
        $Address .= !empty($Account->Country) ? $Account->Country : '';
        return $Address;
    }

    public static function validate_cli($cli = 0)
    {
        $status = 0;
        $companyID = User::get_companyID();
        $accountCLI = DB::select('call prc_checkCustomerCli (' . $companyID . ',' . $cli . ')');

        if (count($accountCLI) > 0) {
            return false;
        } else {
            return true;
        }
    }

    public static function validate_ip($ip = 0)
    {
        $status = 0;
        $companyID = User::get_companyID();
        $AccountIPs = DB::select("call prc_checkAccountIP (" . $companyID . ",'" . $ip . "')");
        if (count($AccountIPs) > 0) {
            return false;
        } else {
            return true;
        }
    }

    public static function AuthIP($account)
    {
        $reponse_return = false;
        $companyID = User::get_companyID();
        $ipcount = CompanyGateway::where(array('CompanyID' => $companyID))->where('Settings', 'like', '%"NameFormat":"IP"%')->count();
        if ($ipcount > 0) {
            $AccountAuthenticate = AccountAuthenticate::where(array('AccountID' => $account->AccountID))->first();
            $AccountAuthenticateIP = AccountAuthenticate::where(array('AccountID' => $account->AccountID))->where(

                function ($query) {
                    $query->where('CustomerAuthRule', '=', 'IP')
                        ->orwhere('VendorAuthRule', '=', 'IP');
                }
            )->first();
            if (empty($AccountAuthenticate) || empty($AccountAuthenticateIP)) {
                /** if Authentication Rule Not Set as IP */
                $reponse_return = true;
            } else if (empty($AccountAuthenticateIP->CustomerAuthRule) && empty($AccountAuthenticateIP->VendorAuthRule)) {
                /** if Authentication Rule Set as IP and No IP Saved */
                $reponse_return = true;
            }
        }
        return $reponse_return;
    }
	
		public static function GetActiveTicketCategory(){
		$TicketsShow	 =	0;
        $companyID  	 = User::get_companyID();

		$Support	 	 =	Integration::where(["CompanyID" => $companyID,"Slug"=>Account::$SupportSlug])->first();	
	
		if(count($Support)>0)
		{
						
			$SupportSubcategory = Integration::select("*");
			$SupportSubcategory->join('tblIntegrationConfiguration', function($join)
			{
				$join->on('tblIntegrationConfiguration.IntegrationID', '=', 'tblIntegration.IntegrationID');
	
			})->where(["tblIntegration.CompanyID"=>$companyID])->where(["tblIntegration.ParentID"=>$Support->IntegrationID])->where(["tblIntegrationConfiguration.Status"=>1]);
			 $result = $SupportSubcategory->first();
			 if(count($result)>0)
			 {
				 return array("Settings"=>json_decode($result->Settings),"Slug"=>$result->Slug);
			 }
			 else
			 {
				return 0;
			 }
		}
		else
		{
			return 0;	
		}
	}
    public static function create_replace_array($Account,$extra_settings,$JobLoggedUser=array()){
        $RoundChargesAmount				=	 getCompanyDecimalPlaces();
        $replace_array = array();
		if(isset($Account) && !empty($Account)){
			$replace_array['FirstName'] = $Account->FirstName;
			$replace_array['LastName'] = $Account->LastName;
			$replace_array['Email'] = $Account->Email;
			$replace_array['Address1'] = $Account->Address1;
			$replace_array['Address2'] = $Account->Address2;
			$replace_array['Address3'] = $Account->Address3;
			$replace_array['City'] = $Account->City;
			$replace_array['State'] = $Account->State;
			$replace_array['PostCode'] = $Account->PostCode;
			$replace_array['Country'] = $Account->Country;
			$replace_array['OutstandingIncludeUnbilledAmount'] = AccountBalance::getBalanceAmount($Account->AccountID);
			$replace_array['OutstandingIncludeUnbilledAmount'] = number_format($replace_array['OutstandingIncludeUnbilledAmount'], $RoundChargesAmount);
			$replace_array['BalanceThreshold'] = AccountBalance::getBalanceThreshold($Account->AccountID);
			$replace_array['Currency'] = Currency::getCurrencyCode($Account->CurrencyId);
			$replace_array['CurrencySign'] = Currency::getCurrencySymbol($Account->CurrencyId);
			$replace_array['CompanyName'] = Company::getName($Account->CompanyId);
			$replace_array['CompanyVAT'] = Company::getCompanyField($Account->CompanyId,"VAT");
		    $replace_array['CompanyAddress'] = Company::getCompanyFullAddress($Account->CompanyId);
			
			$replace_array['OutstandingExcludeUnbilledAmount'] = AccountBalance::getOutstandingAmount($Account->CompanyId,$Account->AccountID);
			$replace_array['OutstandingExcludeUnbilledAmount'] = number_format($replace_array['OutstandingExcludeUnbilledAmount'], $RoundChargesAmount);
		}
        $Signature = '';
        if(!empty($JobLoggedUser)){
            $emaildata['EmailFrom'] = $JobLoggedUser->EmailAddress;
            $emaildata['EmailFromName'] = $JobLoggedUser->FirstName.' '.$JobLoggedUser->LastName;
            if(isset($JobLoggedUser->EmailFooter) && trim($JobLoggedUser->EmailFooter) != '')
            {
                $Signature = $JobLoggedUser->EmailFooter;
            }
        }
        $replace_array['Signature']= $Signature;
        $extra_var = array(
            'InvoiceNumber' => '',
            'InvoiceGrandTotal' => '',
            'InvoiceOutstanding' => '',
        );

        $request = new \Dingo\Api\Http\Request;
        $replace_array['Logo'] = '<img src="'.getCompanyLogo($request).'" />';
        $replace_array = $replace_array + array_intersect_key($extra_settings, $extra_var);

        return $replace_array;
    }
	
	public static function GetAccountAllEmails($id,$ArrayReturn=false){
	  $array			 =  array();
	  $accountemails	 = 	Account::where(array("AccountID"=>$id))->select(array('Email', 'BillingEmail'))->get();
	  $acccountcontact 	 =  DB::table('tblContact')->where(array("AccountID"=>$id))->orwhere(array("Owner"=>$id))->get(array("Email"));
	  
	  	
		if(count($accountemails)>0){
				foreach($accountemails as $AccountData){
					//if($AccountData->Email!='' && !in_array($AccountData->Email,$array))
					if($AccountData->Email!='')
					{
						if(!is_array($AccountData->Email))
						{				  
						  $email_addresses = explode(",",$AccountData->Email);				
						}
						else
						{
						  $email_addresses = $emails;
						}
						if(count($email_addresses)>0)
						{
							foreach($email_addresses as $email_addresses_data)
							{
								$txt = $email_addresses_data;
								if(!in_array($txt,$array))
								{
									$array[] =  $txt;	
								}
							}
						}
						
					}			
					
					if($AccountData->BillingEmail!='')
					{
						if(!is_array($AccountData->BillingEmail))
						{				  
						  $email_addresses = explode(",",$AccountData->BillingEmail);				
						}
						else
						{
						  $email_addresses = $emails;
						}
						if(count($email_addresses)>0)
						{
							foreach($email_addresses as $email_addresses_data)
							{
								$txt = $email_addresses_data;
								if(!in_array($txt,$array))
								{
									//$array[] =  $email_addresses_data;	
									$array[] =  $txt;	
								}
							}
						}
						
					}
				}
		}
		
		if(count($acccountcontact)>0){
				foreach($acccountcontact as $ContactData){
					$txt = $ContactData->Email;
					if($ContactData->Email!=''  && !in_array($txt,$array))
					{
						$array[] =  $txt;
						//$array[] =  $ContactData->Email;
					}
				}
		}
	if($ArrayReturn){return $array;}		
	  return implode(",",$array);
	}

    public static function accountVendorFieldID() {
        $DynamicFields = DynamicFields::where('Type', Account::DynamicFields_Type)->where('FieldSlug','vendorname')->where('Status',1);

        if($DynamicFields->count() > 0) {
            return $DynamicFields->first()->DynamicFieldsID;
        } else {
            return '';
        }
    }

    public static function accountVendorName($AccountID) {
        $DynamicFieldsID = Account::accountVendorFieldID();
//        echo "<pre>";print_r($DynamicFieldsID);exit();
        if(isset($DynamicFieldsID) && !empty($DynamicFieldsID)) {
            $VendorName = DynamicFieldsValue::where('DynamicFieldsID', $DynamicFieldsID)->where('ParentID',$AccountID);
            if($VendorName->count() > 0) {
                return $VendorName->first()->FieldValue;
            } else {
                return '';
            }

        } else {
            return '';
        }
    }
}