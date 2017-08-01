<?php

namespace Api\Controllers;

use Api\Models\AccountAuditExportLog;
//use App\NeonAccountAuditExportLog;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;

class AccountAuditExportLogController extends BaseController {

    public function __construct() {

    }

    /** Perameters :
     * CompanyID
     * GatewayID
     * @return list of accounts from tblAccountAuditExportLog
     */
    public function get() {

        $data = Input::all();

        $CompanyID = $data['CompanyID'];
        $GatewayID = $data['GatewayID'];

        // import un processed
        try {
            $accounts = AccountAuditExportLog::importAccountAuditExportLogs($CompanyID,$GatewayID);
//            return generateResponse('',false,false,$accounts);
            return \Dingo\Api\Facade\API::response()->array($accounts)->statusCode(200);
        }catch (\Exception $ex){
            Log::info($ex);
            return generateResponse($ex->getMessage(),true,false,[]);
         }


    }

    public function mark_processed() {

        $data = Input::all();

        $CompanyID = $data['CompanyID'];
        $GatewayID = $data['GatewayID'];
        $AccountImportExportLogIDs = $data['AccountImportExportLogIDs'];

        try {

            AccountAuditExportLog::mark_processed($CompanyID,$GatewayID,$AccountImportExportLogIDs);
            return generateResponse('success',false,false,[]);

        }catch (\Exception $ex){
            Log::info($ex);
            return generateResponse($ex->getMessage(),true,false,[]);
        }

    }

}
