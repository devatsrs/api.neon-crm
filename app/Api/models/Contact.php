<?php
namespace Api\Model;

use Illuminate\Database\Eloquent\Model;
class Contact extends \Eloquent {

    protected $guarded = array();

    protected $table = 'tblContact';

    protected  $primaryKey = "ContactID";
    public static $rules = array(
       // 'AccountID' =>      'required',
        'CompanyID' =>  'required',
        'FirstName' => 'required',
        'LastName' => 'required',
    );
	
	 public static function create_replace_array_contact($contact,$extra_settings,$JobLoggedUser=array()){
        $replace_array = array();
		if(isset($contact) && !empty($contact)){
			$replace_array['FirstName'] 			= 	$contact->FirstName;
			$replace_array['LastName'] 				= 	$contact->LastName;
			$replace_array['AccountName'] 			= 	$contact->AccountName;
			$replace_array['Email'] 				= 	$contact->Email;
			$replace_array['Address1'] 				= 	$contact->Address1;
			$replace_array['Address2'] 				= 	$contact->Address2;
			$replace_array['Address3']				= 	$contact->Address3;
			$replace_array['City'] 					= 	$contact->City;
			$replace_array['State'] 				= 	$contact->State;
			$replace_array['PostCode'] 				= 	$contact->PostCode;
			$replace_array['Country'] 				= 	$contact->Country;		
			$replace_array['CompanyName'] 			= 	Company::getName($contact->CompanyId);
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

		$request = new \Dingo\Api\Http\Request;
		 $replace_array['Logo'] = '<img src="'.getCompanyLogo($request).'" />';
        return $replace_array;
    }
	
	public static function CheckEmailContact($emails,$accountID = 0){
		
		$emails_array 	= 	array();
		$AllEmails    	= 	Messages::GetAllSystemEmails(); 
		$CompanyID 	  	= 	User::get_companyID();
		
		if(is_array($emails))
		{
			$emails_array = $emails;			
		}
		else
		{
			if(strlen($emails)<1){return;}
			$emails_array = explode(",",$emails);
		}
		
		foreach($emails_array as $emails_array_data)
		{
			if(strlen($emails_array_data)>0)
			{
				if(!in_array($emails_array_data,$AllEmails))
				{
						$FromName = 	explode("@",$emails_array_data); 
						if($accountID)
						{
							$ContactData = array("FirstName"=>$FromName[0],"Email"=>$emails_array_data,"CompanyId"=>$CompanyID,"AccountID"=>$accountID,"Owner"=>$accountID);
						}
						else
						{
							$ContactData = array("FirstName"=>$FromName[0],"Email"=>$emails_array_data,"CompanyId"=>$CompanyID);
						}
						Contact::create($ContactData);
				}
			}
		}
	}
}