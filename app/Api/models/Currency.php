<?php
namespace Api\Model;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Currency extends Model {

    protected $fillable = [];
    protected $table = "tblCurrency";
    protected $primaryKey = "CurrencyId";
    static protected  $enable_cache = true;
    public static $cache = array(
        "currency_dropdown1_cache",   // currency => currencyID
        "currency_dropdown2_cache",
    );

    public static function getCurrencyCode($CurrencyId){
        if($CurrencyId>0){
            return Currency::where("CurrencyId",$CurrencyId)->pluck('Code');
        }
    }

    public static function getCurrencySymbol($CurrencyID){
        if($CurrencyID>0){
            return Currency::where("CurrencyId",$CurrencyID)->pluck('Symbol');
        }
    }

    public static function getCurrencyId($CurrencyCode){
        $CurrencyId='';
        if(isset($CurrencyCode)){
            $CurrencyId = Currency::where("Code",$CurrencyCode)->pluck('CurrencyId');
            if(!empty($CurrencyId) && $CurrencyId>0){
                return $CurrencyId;
            }
        }
        return $CurrencyId;
    }

    public static function getCurrencyIDList(){

        if (self::$enable_cache && Cache::has('currency_dropdown1_cache')) {
            $admin_defaults = Cache::get('currency_dropdown1_cache');
            self::$cache['currency_dropdown1_cache'] = $admin_defaults['currency_dropdown1_cache'];
        } else {
            $CompanyId = User::get_companyID();
            self::$cache['currency_dropdown1_cache'] = Currency::where("CompanyId",$CompanyId)->lists('Code','CurrencyID')->all();
            Cache::forever('currency_dropdown1_cache', array('currency_dropdown1_cache' => self::$cache['currency_dropdown1_cache']));
        }

        return self::$cache['currency_dropdown1_cache'];
    }

}