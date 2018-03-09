<?php
namespace Api\Model;
use Illuminate\Support\Facades\Log;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Input;

class EmailTemplate extends \Eloquent 
{

    protected $guarded = array("TemplateID");
    protected $table = 'tblEmailTemplate';
    protected  $primaryKey = "TemplateID";
    const ACCOUNT_TEMPLATE =1;
    const INVOICE_TEMPLATE =2;
    const RATESHEET_TEMPLATE = 3;
	const TICKET_TEMPLATE = 3;
	const PRIVACY_ON = 1;
    const PRIVACY_OFF = 0;
	
	const DYNAMICTEMPLATE = 0;
	const STATICTEMPLATE  = 1;
	
	
    public static $privacy = [0=>'All User',1=>'Only Me'];
    public static $Type = [0=>'Select Template Type',self::ACCOUNT_TEMPLATE=>'Account',self::INVOICE_TEMPLATE=>'Billing',self::RATESHEET_TEMPLATE=>'Rate sheet',self::TICKET_TEMPLATE=>'Tickets'];

    public static function checkForeignKeyById($id){
        $companyID = User::get_companyID();
        $JobTypeID = JobType::where(["Code" => 'BLE'])->pluck('JobTypeID');
        $hasInCronLog = Job::where("TemplateID",$id)->where("CompanyID",$companyID)->where('JobTypeID',$JobTypeID)->count();
        if( intval($hasInCronLog) > 0 ){
            return true;
        }else{
            return false;
        }
    }
    public static function getTemplateArray($data=array()){
        $select =  isset($data['select'])?$data['select']:1;
        unset($data['select']);
        $data['CompanyID']=User::get_companyID();
        $EmailTemplate = EmailTemplate::where($data);
        if(!isset($data['UserID'])){
            $EmailTemplate->whereNull('UserID');
        }
        $row = $EmailTemplate->select(array('TemplateID', 'TemplateName'))->orderBy('TemplateName')->lists('TemplateName','TemplateID');

        if(!empty($row) && $select==1){
            $row = array(""=> "Select")+$row;
        }
        return $row;
    }

    public static function getDefaultSystemTemplate($SystemType){
       return  EmailTemplate::where(array('SystemType'=>$SystemType,"CompanyID"=>User::get_companyID()))->pluck('TemplateID');
    }
	
	public static function GetUserDefinedTemplates($select = 1){
		$select =  isset($select)?$select:1;
       $row =  EmailTemplate::where(array('StaticType'=>EmailTemplate::DYNAMICTEMPLATE,"CompanyID"=>User::get_companyID()))->whereNull('UserID')->select(["TemplateID","TemplateName"])->lists('TemplateName','TemplateID');
	    if(!empty($row) && $select==1){
            $row = array(""=> "Select")+$row;
        }
        return $row;
    }

    public static function getSystemEmailTemplate($companyID, $slug,$languageID=""){
        if(empty($languageID)){
            $languageID=Translation::$default_lang_id;
        }

        $emailtemplate=EmailTemplate::where(["SystemType"=>$slug, "LanguageID"=>$languageID, "CompanyID"=>$companyID, 'Status'=>1])->first();
        if(empty($emailtemplate)){
            $emailtemplate=EmailTemplate::where(["SystemType"=>$slug, "LanguageID"=>Translation::$default_lang_id, "CompanyID"=>User::get_companyID(), 'Status'=>1])->first();
        }

        return $emailtemplate;
    }
}