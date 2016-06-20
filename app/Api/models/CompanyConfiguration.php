<?php
namespace Api\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class CompanyConfiguration extends \Eloquent {

    //protected $connection = 'sqlsrv';
    protected $fillable = [];
    protected $guarded = array('CompanyConfigurationID');
    protected $table = 'tblCompanyConfiguration';
    public  $primaryKey = "CompanyConfigurationID";
    static protected  $enable_cache = true;
    public static $cache = ["CompanyConfiguration"];

    public static function getConfiguration(){
        $LicenceKey = getRequestParam('LicenceKey');
        $CompanyName = getRequestParam('CompanyName');
        $CompanyConfiguration = 'CompanyConfiguration' . $LicenceKey.$CompanyName;
        if (self::$enable_cache && Cache::has($CompanyConfiguration)) {
            $cache = Cache::get($CompanyConfiguration);
            self::$cache['CompanyConfiguration'] = $cache['CompanyConfiguration'];
        } else {
            $CompanyID = User::get_companyID();
            self::$cache['CompanyConfiguration'] = CompanyConfiguration::where(['CompanyID'=>$CompanyID])->lists('Value','Key');
            Cache::forever($CompanyConfiguration, array('CompanyConfiguration' => self::$cache['CompanyConfiguration']));
        }

        return self::$cache['CompanyConfiguration'];
    }
}