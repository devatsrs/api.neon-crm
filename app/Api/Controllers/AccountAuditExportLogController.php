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
            $updated_vendor     = isset($data['updated_vendor']) ? $data['updated_vendor'] : 0;
            $updated_customer   = isset($data['updated_customer']) ? $data['updated_customer'] : 0;
            $inserted_vendor    = isset($data['inserted_vendor']) ? $data['inserted_vendor'] : 0;
            $inserted_customer  = isset($data['inserted_customer']) ? $data['inserted_customer'] : 0;

            $email_data['isAPI']	    = 1;
            $email_data['companyID']	= $CompanyID;
            $email_data['EmailTo']	    = "vasim.seta@code-desk.com";
            $email_data['In-Reply-To']	= "vasim.seta@code-desk.com";
            $email_data['EmailFrom']	= "vasim.seta@code-desk.com";
            $email_data['CompanyName']  = "vasim";
            $email_data['Subject']  	= "account imported to vos server";
            $email_data['Message']  	= "CompanyID : ".$CompanyID."<br/>";
            $email_data['Message']     .= "GatewaiID : ".$GatewayID."<br/>";
            $email_data['Message']     .= "Export Time : ".$export_time."<br/>";
            $email_data['Message']     .= "Customers Inserted : " . $inserted_customer . "<br/>";
            $email_data['Message']     .= "Customers Updated : " . $updated_customer . "<br/>";
            $email_data['Message']     .= "Vendors Inserted : " . $inserted_vendor . "<br/>";
            $email_data['Message']     .= "Vendors Updated : " . $updated_vendor . "<br/>";

            $status = sendMail('emails.template', $email_data);

            AccountAuditExportLog::markProcessed($CompanyID,$GatewayID,$export_time,$start_time,$end_time);
            return generateResponse('Marked processed logs successfully!',false,false,[]);
        }catch (\Exception $ex){
            Log::info($ex);
            return generateResponse($ex->getMessage(),true,false,[]);
        }
    }

    public function markProcessedIP() {
        $data = Input::all();

        // import un processed
        try {
            $CompanyID              = $data['CompanyID'];
            $GatewayID              = $data['GatewayID'];
            $export_time            = $data['export_time'];
            $start_time             = $data['start_time'];
            $end_time               = $data['end_time'];
            $updated_vendorip       = isset($data['updated_vendorip']) ? $data['updated_vendorip'] : 0;
            $updated_customerip     = isset($data['updated_customerip']) ? $data['updated_customerip'] : 0;
            $inserted_vendorip      = isset($data['inserted_vendorip']) ? $data['inserted_vendorip'] : 0;
            $inserted_customerip    = isset($data['inserted_customerip']) ? $data['inserted_customerip'] : 0;

            $email_data['isAPI']	    = 1;
            $email_data['companyID']	= $CompanyID;
            $email_data['EmailTo']	    = "vasim.seta@code-desk.com";
            $email_data['In-Reply-To']	= "vasim.seta@code-desk.com";
            $email_data['EmailFrom']	= "vasim.seta@code-desk.com";
            $email_data['CompanyName']  = "vasim";
            $email_data['Subject']  	= "accountip imported to vos server";
            $email_data['Message']  	= "CompanyID : ".$CompanyID."<br/>";
            $email_data['Message']     .= "GatewaiID : ".$GatewayID."<br/>";
            $email_data['Message']     .= "Export Time : ".$export_time."<br/>";
            $email_data['Message']     .= "Customers IP Inserted : " . $inserted_customerip . "<br/>";
            $email_data['Message']     .= "Customers IP Updated : " . $updated_customerip . "<br/>";
            $email_data['Message']     .= "Vendors IP Inserted : " . $inserted_vendorip . "<br/>";
            $email_data['Message']     .= "Vendors IP Updated : " . $updated_vendorip . "<br/>";

            $status = sendMail('emails.template', $email_data);

            AccountAuditExportLog::markProcessed($CompanyID,$GatewayID,$export_time,$start_time,$end_time);
            return generateResponse('Marked processed logs successfully!',false,false,[]);
        }catch (\Exception $ex){
            Log::info($ex);
            return generateResponse($ex->getMessage(),true,false,[]);
        }
    }

    /**
     * @return mixed
     */
    public function getAccountIPAuditLogs() {
        $data = Input::all();
        $CompanyID = $data['CompanyID'];
        $GatewayID = $data['GatewayID'];

        // import un processed
        try {
            $accountips = AccountAuditExportLog::importAccountIPAuditExportLogs($CompanyID,$GatewayID);
            return \Dingo\Api\Facade\API::response()->array($accountips)->statusCode(200);
        }catch (\Exception $ex){
            Log::info($ex);
            return generateResponse($ex->getMessage(),true,false,[]);
        }
    }

}
