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
		if(isset($Account) && !empty($contact)){
			$replace_array['FirstName'] 			= 	$contact->FirstName;
			$replace_array['LastName'] 				= 	$contact->LastName;
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
     
        return $replace_array;
    }
}