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

    public function markProcessed() {
        $data = Input::all();

        // import un processed
        try {
            $CompanyID   = $data['CompanyID'];
            $GatewayID   = $data['GatewayID'];
            $export_time = $data['export_time'];
            $start_time  = $data['start_time'];
            $end_time    = $data['end_time'];

            AccountAuditExportLog::markProcessed($CompanyID,$GatewayID,$export_time,$start_time,$end_time);
            return generateResponse('Marked processed logs successfully!',false,false,[]);
        }catch (\Exception $ex){
            Log::info($ex);
            return generateResponse($ex->getMessage(),true,false,[]);
        }
    }

}
