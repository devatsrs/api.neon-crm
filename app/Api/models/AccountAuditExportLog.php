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
    public $timestamps = false;


    /**
     * @param $CompanyID
     * @param $GatewayID
     * @return mixed
     */
    public static function importAccountAuditExportLogs($CompanyID, $GatewayID) {
        $audits = DB::select("call prc_getAccountAuditExportLog('".$CompanyID."' , '".$GatewayID."')");
        $data['status'] = 'success';
        $data['message'] = '1';
        $data['AccountAuditExportLogID'] = '';
        $data['export_time'] = '';
        $data['start_time'] = '';
        $data['end_time'] = '';
        $data['data'] = array();
        $vendors = array();
        $k=0;
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

                $accountIPs = AccountAuthenticate::where(["CompanyID"=>$CompanyID, "AccountID"=>$account->AccountID, "ServiceID" => 0]);

                if($accountIPs->count() > 0) {
                    $accountIPs = $accountIPs->first();

                    if($accountIPs->CustomerAuthRule == "IP") {
                        $CustomerIP = $accountIPs->CustomerAuthValue;
                    } else {
                        $CustomerIP = "";
                    }
                    if($accountIPs->VendorAuthRule == "IP") {
                        $VendorIP = $accountIPs->VendorAuthValue;
                    } else {
                        $VendorIP = "";
                    }
                } else {
                    $CustomerIP = "";
                    $VendorIP = "";
                }

                $d['AccountID'] = $account->AccountID;
                $d['IsCustomer'] = $account->IsCustomer;
                $d['IsVendor'] = $account->IsVendor;
                $d['AccountName'] = $account->AccountName;
                $d['VendorName'] = $VendorName;
                $d['ColumnName'] = $audit->ColumnName;
                $d['OldValue'] = $audit->OldValue;
                $d['NewValue'] = $audit->NewValue;
                $d['CustomerIP'] = $CustomerIP;
                $d['VendorIP'] = $VendorIP;

                if($k==0) {
                    $data['export_time'] = $audit->created_at;
                    $data['start_time'] = $audit->start_time;
                    $data['end_time'] = $audit->end_time;
                }
                $k++;
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

    public static function markProcessed($CompanyID,$GatewayID,$export_time,$start_time,$end_time) {
        AccountAuditExportLog::where(["CompanyID"=>$CompanyID,"CompanyGatewayID"=>$GatewayID,"created_at"=>$export_time,"start_time"=>$start_time,"end_time"=>$end_time,"Status"=>0])->update(['Status'=>1]);
    }

    /**
     * @param $CompanyID
     * @param $GatewayID
     * @return mixed
     */
    public static function importAccountIPAuditExportLogs($CompanyID, $GatewayID) {

        $audits = DB::select("call prc_getAccountIPAuditExportLog('".$CompanyID."' , '".$GatewayID."')");
        $data['status'] = 'success';
        $data['message'] = '1';
        $data['AccountIPAuditExportLogID'] = '';
        $data['export_time'] = '';
        $data['start_time'] = '';
        $data['end_time'] = '';
        $data['data'] = array();
        $vendors = array();
        $k=0;
        foreach ($audits as $audit) {
            $authenticate = AccountAuthenticate::find($audit->ParentColumnID);
            $account = Account::where('AccountID', $authenticate->AccountID)->select('AccountID','IsCustomer','IsVendor','AccountName');
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

                if($k==0) {
                    $data['export_time'] = $audit->created_at;
                    $data['start_time'] = $audit->start_time;
                    $data['end_time'] = $audit->end_time;
                }
                $k++;
                $data['data'][] = $d;
            }
        }
        return $data;
    }
}
