<?php 
namespace App;
use Api\Model\User;
use Aws\S3\S3Client;
use Api\Model\Company;
use Api\Model\Invoice;
use Api\Model\EmailTemplate;
use Api\Model\Estimate;
use Api\Model\Account;
use Api\Model\Currency;
use Api\Model\AccountBalance;
use Illuminate\Support\Facades\Log;


class EmailsTemplates{

	protected $EmailSubject;
	protected $EmailTemplate;
	protected $Error;
	protected $CompanyName;
	static $fields = array(
				"{{AccountName}}",
				'{{FirstName}}',
				'{{LastName}}',
				'{{Email}}',
				'{{Address1}}',
				'{{Address2}}',
				'{{Address3}}',
				'{{City}}',
				'{{State}}',
				'{{PostCode}}',
				'{{Country}}',
				'{{Signature}}',
				'{{Currency}}',
				'{{OutstandingExcludeUnbilledAmount}}',
				'{{OutstandingIncludeUnbilledAmount}}',
				'{{BalanceThreshold}}',
				'{{CompanyName}}',
				"{{CompanyVAT}}",
				"{{CompanyAddress1}}",
				"{{CompanyAddress2}}",
				"{{CompanyAddress3}}",
				"{{CompanyCity}}",
				"{{CompanyPostCode}}",
				"{{CompanyCountry}}",
				"{{Logo}}"								
				);
	
	 public function __construct($data = array()){
		 foreach($data as $key => $value){
			 $this->$key = $value;
		 }		 		 
		 $this->CompanyName = Company::getName();
	}
	
	
	static function SendOpportunityTaskTagEmail($slug,$obj,$type="body",$data){
			$replace_array							=	 $data;		
			$replace_array							=	 EmailsTemplates::setCompanyFields($replace_array);
			$replace_array							=	 EmailsTemplates::setAccountFields($replace_array,$obj['AccountID']);
			
			$LogginedUser   						=  	 \Api\Model\User::get_userID();
    		$LogginedUserName  						= 	 \Api\Model\User::get_user_full_name();			
			$request								=	 new \Dingo\Api\Http\Request;
            $replace_array['UserProfileImage']  	= 	 \Api\Model\UserProfile::get_user_picture_url($LogginedUser);
			$replace_array['Logo']					= 	 getCompanyLogo($request);		
			$replace_array['user']					= 	 $LogginedUserName;	
			$replace_array['CompanyName']			=	 Company::getName();
			$message								=	 "";		
			$EmailTemplate 							= 	 EmailTemplate::where(["SystemType"=>$slug])->first();
			if($type=="subject"){
				$EmailMessage						=	 $EmailTemplate->Subject;
			}else{
				$EmailMessage						=	 $EmailTemplate->TemplateBody;
			}
			$extraDefault	=	EmailsTemplates::$fields;
			$extraSpecific  = [			
				'{{subject}}',				
				'{{User}}',
				'{{type}}',
				'{{Comment}}',
				'{{Logo}}'
			];
			$extra = array_merge($extraDefault,$extraSpecific);
		
		foreach($extra as $item){
			$item_name = str_replace(array('{','}'),array('',''),$item);
			if(array_key_exists($item_name,$replace_array)) {					
				$EmailMessage = str_replace($item,$replace_array[$item_name],$EmailMessage);					
			}
		} 
		return $EmailMessage; 			
	}
	
	
	static function GetEmailTemplateFrom($slug){
		return EmailTemplate::where(["SystemType"=>$slug])->pluck("EmailFrom");
	}
	
		static function setCompanyFields($array){
			$CompanyData							=	Company::find(User::get_companyID());
			$array['CompanyName']					=   Company::getName();
			$array['CompanyVAT']					=   $CompanyData->VAT;			
			$array['CompanyAddress1']				=   $CompanyData->Address1;
			$array['CompanyAddress2']				=   $CompanyData->Address1;
			$array['CompanyAddress3']				=   $CompanyData->Address1;
			$array['CompanyCity']					=   $CompanyData->City;
			$array['CompanyPostCode']				=   $CompanyData->PostCode;
			$array['CompanyCountry']				=   $CompanyData->Country;
			//$array['CompanyAddress']				=   Company::getCompanyFullAddress(User::get_companyID());
			return $array;
	}
	
	static function CheckEmailTemplateStatus($slug){
		return EmailTemplate::where(["SystemType"=>$slug])->pluck("Status");
	}
	static function setAccountFields($array,$AccountID){
			$companyID						=	 User::get_companyID();
			$AccoutData 					= 	 Account::find($AccountID);			
			$array['AccountName']			=	 $AccoutData->AccountName;
			$array['FirstName']				=	 $AccoutData->FirstName;
			$array['LastName']				=	 $AccoutData->LastName;
			$array['Email']					=	 $AccoutData->Email;
			$array['Address1']				=	 $AccoutData->Address1;
			$array['Address2']				=	 $AccoutData->Address2;
			$array['Address3']				=	 $AccoutData->Address3;		
			$array['City']					=	 $AccoutData->City;
			$array['State']					=	 $AccoutData->State;
			$array['PostCode']				=	 $AccoutData->PostCode;
			$array['Country']				=	 $AccoutData->Country;
			$array['Currency']				=	 Currency::where(["CurrencyId"=>$AccoutData->CurrencyId])->pluck("Code");
			$array['OutstandingExcludeUnbilledAmount'] = AccountBalance::getOutstandingAmount($companyID,$AccountID);
			$array['OutstandingIncludeUnbilledAmount'] = AccountBalance::getBalanceAmount($AccountID);
			$array['BalanceThreshold'] 				   = AccountBalance::getBalanceThreshold($AccountID);		
		   if(!empty(user::get_userID())){
			   $UserData = user::find(user::get_userID());
			  if(isset($UserData->EmailFooter) && trim($UserData->EmailFooter) != '')
				{
					$array['Signature']= $UserData->EmailFooter;	
				}
			}	
			return $array;
	}
	
}
?>