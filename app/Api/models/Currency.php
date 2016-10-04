<?php
namespace Api\Model;
use Illuminate\Database\Eloquent\Model;
class Currency extends Model {

    protected $fillable = [];
    protected $table = "tblCurrency";
    protected $primaryKey = "CurrencyId";
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
}