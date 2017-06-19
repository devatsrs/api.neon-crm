<?php namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AccountImportExportLog extends Model {

    protected $fillable = [];
    protected $guarded = array('AccountImportExportLogID');
    protected $table = 'tblAccountImportExportLog';
    protected  $primaryKey = "AccountImportExportLogID";


    public static function importAccountImportExportLogs($CompanyID,$GatewayID){

        // there will be separate record for each gateway.

        // import un processed
        $accounts = DB::select("call prc_getAccountImportExportLog('".$CompanyID."' , '".$GatewayID."')");

        return $accounts;

    }

    public static function mark_processed($CompanyID,$GatewayID,$AccountImportExportLogIDs) {

        $accounts = DB::select("call prc_AccountImportExportLogMarkProcessed('".$CompanyID."' , '".$GatewayID."', '".$AccountImportExportLogIDs."')");

    }

}
