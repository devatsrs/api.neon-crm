<?php

namespace Api\Controllers;

use App\AccountImportExportLog;
use App\NeonAccountImportExportLog;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;

class AccountImportExportLogController extends BaseController {

    public function __construct() {

    }

    /** Perameters :
     * CompanyID
     * GatewayID
     * @return list of accounts from tblAccountImportExportLog
     */
    public function get() {

        $data = Input::all();

        $CompanyID = $data['CompanyID'];
        $GatewayID = $data['GatewayID'];

        // import un processed
        try {
            $accounts = AccountImportExportLog::importAccountImportExportLogs($CompanyID,$GatewayID);
            return generateResponse('',false,false,$accounts);
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

            AccountImportExportLog::mark_processed($CompanyID,$GatewayID,$AccountImportExportLogIDs);
            return generateResponse('success',false,false,[]);

        }catch (\Exception $ex){
            Log::info($ex);
            return generateResponse($ex->getMessage(),true,false,[]);
        }

    }

}
