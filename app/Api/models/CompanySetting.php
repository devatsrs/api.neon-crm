<?php
namespace Api\Model;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CompanySetting extends Model{
	protected $fillable = [];
    protected $table = "tblCompanySetting";
    public $timestamps = false; // no created_at and updated_at

    public static function getKeyVal($key){
        $CompanySetting = CompanySetting::where(["CompanyID"=> User::get_companyID(),'key'=>$key])->first();
        if(count($CompanySetting)>0 && isset($CompanySetting->Value)){
            return $CompanySetting->Value;
        }else{
            return 'Invalid Key';
        }
    }

    public static function  setKeyVal($key,$val){
        $CompanySetting = CompanySetting::where(["CompanyID"=> User::get_companyID(),'key'=>$key])->first();
        if(count($CompanySetting)>0){
            CompanySetting::where(["CompanyID"=> User::get_companyID(),'key'=>$key])->update(array('Value'=>$val));
        }else{
            CompanySetting::insert(array('CompanyID' => User::get_companyID(), 'key' => $key,'Value'=>$val));
        }
    }
}