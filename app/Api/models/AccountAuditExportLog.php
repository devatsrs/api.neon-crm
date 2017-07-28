<?php

namespace Api\Models;

use Api\Model\Account;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AccountAuditExportLog extends Model
{

    protected $fillable = [];
    protected $guarded = array('AccountAuditExportLogID');
    protected $table = 'tblAccountAuditExportLog';
    protected  $primaryKey = "AccountAuditExportLogID";


    public static function importAccountAuditExportLogs($CompanyID,$GatewayID){

        // there will be separate record for each gateway.

        // import un processed
        $audits = DB::select("call prc_getAccountAuditExportLog('".$CompanyID."' , '".$GatewayID."')");
        $data['status'] = 'success';
        $data['message'] = '1';
        $data['AccountAuditExportLogID'] = '';
        $data['data'] = array();
        $vendors = array();
        foreach ($audits as $audit) {
            $account = Account::where($audit->ParentColumnName, $audit->ParentColumnID)->select('AccountID','IsCustomer','IsVendor','AccountName');
            if($account->count() > 0) {
                $account = $account->first();

                if($account->IsVendor == 1) {
                    if(array_key_exists($account->AccountID, $vendors)) {
                        $VendorName = $vendors[$account->AccountID];
                    } else {
                        $VendorName = Account::accountVendorName($account->AccountID);
                        $vendors[$account->AccountID] = $VendorName;
                    }
                } else {
                    $VendorName = '';
                }

                $d['AccountID'] = $account->AccountID;
                $d['IsCustomer'] = $account->IsCustomer;
                $d['IsVendor'] = $account->IsVendor;
                $d['AccountName'] = $account->AccountName;
                $d['VendorName'] = $VendorName;
                $d['ColumnName'] = $audit->ColumnName;
                $d['OldValue'] = $audit->OldValue;
                $d['NewValue'] = $audit->NewValue;
                $data['data'][] = $d;
            }
        }
        return $data;
        //my $json_string = '{"status":"success","message":"", "TempAccountAuditExportLogID":"123", "data":[{"AccountID":1,"IsCustomer":1,"IsVendor":1,"AccountName":"DevTest25","VendorName":"DevTest25-Buy","ColumnName":"Email","OldValue":"","NewValue":"test@DevTest25_updated.com"},{"AccountID":1,"IsCustomer":1,"IsVendor":0,"Type":"Update","AccountName":"DevTest25","ColumnName":"Phone","OldValue":"","NewValue":"9979907571DevTest25_updated"},{"AccountID":1,"IsCustomer":1,"IsVendor":0,"Type":"Update","AccountName":"DevTest25","ColumnName":"PostCode","OldValue":"","NewValue":"360002DevTest25_updated"},{"AccountID":1,"IsCustomer":1,"IsVendor":0,"Type":"Update","AccountName":"DevTest25","ColumnName":"Address1","OldValue":"","NewValue":"DevTest25_updated15-b, Radhakrishna som, dhebar road south rajkot, gujarat, india"},{"AccountID":1,"IsCustomer":1,"IsVendor":0,"Type":"Update","AccountName":"DevTest25","ColumnName":"Fax","OldValue":"","NewValue":"423442344234DevTest25_updated"}]}';

        /*$accounts[] = ["AccountID" => 1 ,"Type" => "Insert" , "AccountName" => "DevTest" , "FieldName" => "AccountName",   "OldValue" => "" , NewValue => "DevTest" ];
        $accounts[] = ["AccountID" => 1 ,"Type" => "Update" , "AccountName" => "DevTest" , "FieldName" => "PostCode",  "OldValue" => "" , NewValue => "360002" ];
        $accounts[] = ["AccountID" => 1 ,"Type" => "Update" , "AccountName" => "DevTest" , "FieldName" => "Email",  "OldValue" => "" , NewValue => "devtest@devtest.com" ];
        $accounts[] = ["AccountID" => 1 ,"Type" => "Update" , "AccountName" => "DevTest" , "FieldName" => "Phone",  "OldValue" => "" , NewValue => "9979907571" ];
        $accounts[] = ["AccountID" => 1 ,"Type" => "Update" , "AccountName" => "DevTest" , "FieldName" => "Phone",  "OldValue" => "9979907571" , NewValue => "0919979907571" ];

        return $accounts;*/

    }

    public static function mark_processed($CompanyID,$GatewayID,$AccountImportExportLogIDs) {

        $accounts = DB::select("call prc_AccountImportExportLogMarkProcessed('".$CompanyID."' , '".$GatewayID."', '".$AccountImportExportLogIDs."')");

    }

}
