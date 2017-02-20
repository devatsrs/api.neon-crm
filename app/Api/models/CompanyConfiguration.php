<?php
namespace Api\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;

class CompanyConfiguration extends \Eloquent {

    //protected $connection = 'sqlsrv';
    protected $fillable = [];
    protected $guarded = array('CompanyConfigurationID');
    protected $table = 'tblCompanyConfiguration';
    public  $primaryKey = "CompanyConfigurationID";
    static protected  $enable_cache = true;
    public static $cache = ["CompanyConfiguration"];

    public static function getConfiguration($CompanyID=0){
        $data = Input::all();
        $LicenceKey = $data['LicenceKey'];
        $CompanyName = $data['CompanyName'];
        $CompanyConfiguration = 'CompanyConfiguration' . $LicenceKey.$CompanyName;

        if (self::$enable_cache && Cache::has($CompanyConfiguration)) {
            $cache = Cache::get($CompanyConfiguration);
            self::$cache['CompanyConfiguration'] = $cache['CompanyConfiguration'];
        } else {
            if($CompanyID==0){
                $CompanyID = User::get_companyID();
            }
            self::$cache['CompanyConfiguration'] = CompanyConfiguration::where(['CompanyID'=>$CompanyID])->lists('Value','Key');
            $CACHE_EXPIRE = self::$cache['CompanyConfiguration']['CACHE_EXPIRE'];
            $time = empty($CACHE_EXPIRE)?60:$CACHE_EXPIRE;
            $minutes = \Carbon\Carbon::now()->addMinutes($time);
            \Illuminate\Support\Facades\Cache::add($CompanyConfiguration, array('CompanyConfiguration' => self::$cache['CompanyConfiguration']), $minutes);
        }

        return self::$cache['CompanyConfiguration'];
    }

    public static function get($key = ""){

        $cache = CompanyConfiguration::getConfiguration();

        if(!empty($key) ){

            if(isset($cache[$key])){
                return $cache[$key];
            }
        }
        return "";

    }

    public static function getJsonKey($key = "",$index = ""){

        $cache = CompanyConfiguration::getConfiguration();

        if(!empty($key) ){

            if(isset($cache[$key])){

                $json = json_decode($cache[$key],true);
                if(isset($json[$index])){
                    return $json[$index];
                }
            }
        }
        return "";

    }
}