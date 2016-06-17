<?php
namespace Api\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Session;

class Company extends \Eloquent {
    protected $guarded = array();

    protected $table = 'tblCompany';

    protected  $primaryKey = "CompanyID";

    const BILLING_STARTTIME = 1;
    const BILLING_ENDTIME = 2;

    const LICENCE_ALL = 3;
    const LICENCE_BILLING = 2;
    const LICENCE_RM = 1;

    public static $billing_time = array(''=>'select a time',self::BILLING_STARTTIME=>'Start Time',self::BILLING_ENDTIME=>'End Time');
    public static $BillingCycleType =
        array(
             ""=>"Please Select an Option",
            "weekly"=>"Weekly",
            "fortnightly"=>"Fortnightly",
            "monthly"=>"Monthly" ,
            "quarterly"=>"Quarterly",
            "daily"=>"Daily",
            "in_specific_days"=>"In Specific days",
            "monthly_anniversary"=>"Monthly anniversary"
        );

    // CDR Rerate Based on Charge code or Prefix
    const CHARGECODE =1;
    const PREFIX =2;
    public static $rerate_format = array(''=>'Select a Rerate Format',self::CHARGECODE=>'Charge Code',self::PREFIX=>'Prefix');

    public static $date_format = array( 'd-m-Y'=>'dd-mm-yyyy (31-12-2015)',
                                        'm-d-Y'=>'mm-dd-yyyy (12-31-2015)',
                                        'Y-m-d'=>'yyyy-mm-dd (2015-12-31)',
                                        'd-m-y'=>'dd-mm-yy (31-12-15)',
                                        'm-d-y'=>'mm-dd-yy (12-31-15)',
                                        'y-m-d'=>'yy-mm-dd (15-12-31)',
                                        'd-M-Y'=>'dd-mmm-yyyy (31-DEC-2015)',
                                        'M-d-Y'=>'mmm-dd-yyyy (DEC-31-2015)',
                                        'Y-M-d'=>'yyyy-mmm-dd (2015-DEC-31)',
                                        'd-M-y'=>'dd-mmm-yy (31-DEC-15)',
                                        'M-d-y'=>'mmm-dd-yy (DEC-31-15)',
                                        'y-M-d'=>'yy-mmm-dd (15-DEC-31)',
                                        'd-F-Y'=>'dd-mmmm-yyyy (31-DECEMBER-2015)',
                                        'F-d-Y'=>'mmmm-dd-yyyy (DECEMBER-31-2015)',
                                        'Y-F-d'=>'yyyy-mmmm-dd (2015-DECEMBER-31)',
                                        'd-F-y'=>'dd-mmmm-yy (31-DECEMBER-15)',
                                        'F-d-y'=>'mmmm-dd-yy (DECEMBER-31-15)',
                                        'y-F-d'=>'yy-mmmm-dd (15-DECEMBER-31)',
                                      );


    public static function getName($companyID=0){
        if($companyID>0){
            return Company::find($companyID)->CompanyName;
        }else{
            return Company::find(User::get_companyID())->CompanyName;
        }

    }

    public static function ValidateLicenceKey($credentials_license){
        $LICENCE_KEY = getenv('LICENCE_KEY');
        $result = array();
        $result['LicenceHost'] = $_SERVER['HTTP_HOST'];
        $result['LicenceIP'] = $_SERVER['SERVER_ADDR'];
        $result['LicenceKey'] = $LICENCE_KEY;
        $result['Type'] = '';
        $result['LicenceProperties'] = '';
        $company_id = User::get_companyID();
        $result['CompanyName'] = company::getName($company_id);
        //$result['CompanyName'] = 'abc';
        if(!empty($LICENCE_KEY)) {

            $post = array("host" => $credentials_license['LicenceHost'], "ip" =>$credentials_license['LicenceIP'], "licence_key" => $credentials_license['LicenceKey'], "company_name" => $result['CompanyName']);
            $response = call_api($post);
            if (!empty($response)) {
                $response = json_decode($response,TRUE);
                $result['Status'] = $response['Status'] ;
                $result['Message'] = $response['Message'];
                $result['ExpiryDate'] = $response['ExpiryDate'];
                $result['Type'] = $response['Type'];
                $result['LicenceProperties'] = $response['LicenceProperties'];
            }else{
                $result['Status'] = 0 ;
                $result['Message'] = 'Unable To Validate Licence';
                $result['ExpiryDate']='';
                $result['Status'] = 1;
                $result['Message'] = "Your Licence is Valid";
                $result['Type'] = '3';
            }

        }else{

            $result['Message'] = "Licence key not found";
            $result['Status'] = "Licence key not found";
            $result['ExpiryDate'] = "";
        }

        return $result;

    }

    public static function getCompanyField($companyID,$field = "") {
        if(!empty($field)) {

            if ($companyID > 0) {
                return Company::where("CompanyID",$companyID)->pluck($field);
            } else {
                return Company::find(User::get_companyID())->CompanyName;
            }
        }
    }
    public static function getEmail($CompanyID){
        if($CompanyID > 0){
            $Email = Company::where("CompanyID",$CompanyID)->pluck("Email");
            return explode(',',$Email);
        }else{
            return  getenv("TEST_EMAIL");
        }
    }

    public static function getLicenceType(){

        $LicenceApiResponse = Session::get('LicenceApiResponse','');
        if(!empty($LicenceApiResponse) && isset($LicenceApiResponse['Type'])) {
            if ($LicenceApiResponse['Type'] == Company::LICENCE_ALL) {
                return Company::LICENCE_ALL;
            }
            if ($LicenceApiResponse['Type'] == Company::LICENCE_BILLING) {
                return Company::LICENCE_BILLING;
            }
            if ($LicenceApiResponse['Type'] == Company::LICENCE_RM) {
                return Company::LICENCE_RM;
            }
        }
        return '';

    }

    public static function isBillingLicence($billing=0){

        $LicenceApiResponse = Session::get('LicenceApiResponse','');
        if(!empty($LicenceApiResponse) && isset($LicenceApiResponse['Type'])) {
            if ($billing == 0 && $LicenceApiResponse['Type'] == Company::LICENCE_ALL || $LicenceApiResponse['Type'] == Company::LICENCE_BILLING) {
                return true;
            }else if ($billing == 1 && $LicenceApiResponse['Type'] == Company::LICENCE_BILLING) {
                return true;
            }
        }
        return false;

    }

    public static function isRMLicence(){

        $LicenceApiResponse = Session::get('LicenceApiResponse','');
        if(!empty($LicenceApiResponse) && isset($LicenceApiResponse['Type'])) {
            if ($LicenceApiResponse['Type'] == Company::LICENCE_ALL || $LicenceApiResponse['Type'] == Company::LICENCE_RM) {
                return true;
            }
        }
        return false;

    }

    public static function getLicenceResponse($credentials_license){
        $LicenceApiResponse = Session::get('LicenceApiResponse','');

        if(empty($LicenceApiResponse)) { // if first time login ...
            $valresponse = Company::ValidateLicenceKey($credentials_license);
            Session::set('LicenceApiResponse', $valresponse);
            $LicenceApiResponse = $valresponse;
        }
        return $LicenceApiResponse;
    }
}