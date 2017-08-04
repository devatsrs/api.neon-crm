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
            $CompanyID          = $data['CompanyID'];
            $GatewayID          = $data['GatewayID'];
            $export_time        = $data['export_time'];
            $start_time         = $data['start_time'];
            $end_time           = $data['end_time'];
            $updated_vendor     = $data['updated_vendor'];
            $updated_customer   = $data['updated_customer'];
            $inserted_vendor    = $data['inserted_vendor'];
            $inserted_customer  = $data['inserted_customer'];

            $email_data['isAPI']	        = 	1;
            $email_data['companyID']	    = 	$CompanyID;
            $email_data['EmailTo']	        = 	"vasim.seta@code-desk.com";
            $email_data['In-Reply-To']	    = 	"vasim.seta@code-desk.com";
            $email_data['EmailFrom']	   	= 	"vasim.seta@code-desk.com";
            $email_data['CompanyName']  	= 	"vasim";
            $email_data['Subject']  	    = 	"account imported to vos server";
            $email_data['Message']  	    = 	"CompanyID : ".$CompanyID."<br/>";
            $email_data['Message']  	   .= 	"GatewaiID : ".$GatewayID."<br/>";
            $email_data['Message']  	   .= 	"Export Time : ".$export_time."<br/>";
            $email_data['Message']  	   .= 	"Customers Inserted : ".$inserted_customer."<br/>";
            $email_data['Message']  	   .= 	"Customers Updated : ".$updated_customer."<br/>";
            $email_data['Message']  	   .= 	"Vendors Inserted : ".$inserted_vendor."<br/>";
            $email_data['Message']  	   .= 	"Vendors Updated : ".$updated_vendor."<br/>";

            $status = sendMail('emails.template', $email_data);

            AccountAuditExportLog::markProcessed($CompanyID,$GatewayID,$export_time,$start_time,$end_time);
            return generateResponse('Marked processed logs successfully!',false,false,[]);
        }catch (\Exception $ex){
            Log::info($ex);
            return generateResponse($ex->getMessage(),true,false,[]);
        }
    }

}
