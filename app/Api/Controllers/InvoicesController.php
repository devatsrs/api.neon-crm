<?php

namespace Api\Controllers;

use Api\Model\Account;
use Api\Model\Currency;
use Api\Model\DataTableSql;
use Dingo\Api\Http\Request;
use Api\Model\Lead;
use Api\Model\User;
use Api\Model\Tags;
use App\Http\Requests;
use Dingo\Api\Facade\API;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class InvoicesController extends BaseController
{

    public function __construct(Request $request)
    {
        $this->middleware('jwt.auth');
        Parent::__Construct($request);
    }

    public function getCustomerInvoices(){
        $data = Input::all();

        $rules['iDisplayStart'] = 'required|Min:1';
        $rules['iDisplayLength'] = 'required';

        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            return generateResponse($validator->errors(),true);
        }
        $data['iDisplayStart'] += 1;
        $data['iSortCol_0'] = !empty($data['iSortCol_0']) ? $data['iSortCol_0'] : 0;
        $data['sSortDir_0'] = !empty($data['sSortDir_0']) ? $data['sSortDir_0'] : 'desc';

        $Currencies = Currency::getCurrencyIDList();
        $Accounts   = Account::getAccountIDList();

        $valid_invoice_status = ['draft','send','awaiting','cancel','paid','partially_paid','post'];

        if(isset($data['InvoiceStatus'])) {
            $InvoiceStatus = explode(',',$data['InvoiceStatus']);
            $data['InvoiceStatus'] = implode(',',array_intersect($valid_invoice_status,$InvoiceStatus));
        }

        $columns = ['InvoiceID','AccountName','InvoiceNumber','IssueDate','InvoicePeriod','GrandTotal','PendingAmount','InvoiceStatus','DueDate','DueDays','Currency','Description','Attachment','AccountID','OutstandingAmount','ItemInvoice','BillingEmail','GrandTotal','TotalMinutes'];
        $companyID                  = User::get_companyID();
        $data['zerovalueinvoice']   = !empty($data['zerovalueinvoice']) && $data['zerovalueinvoice']== 'true'?1:0;
        $data['IssueDateStart']     = empty($data['IssueDateStart'])?'0000-00-00 00:00:00':$data['IssueDateStart'];
        $data['IssueDateEnd']       = empty($data['IssueDateEnd'])?'0000-00-00 00:00:00':$data['IssueDateEnd'];
        $data['CurrencyID']         = !empty($data['Currency']) && array_search($data['Currency'],$Currencies) ? array_search($data['Currency'],$Currencies) : 0;
        $data['AccountID']          = !empty($data['Account']) && array_search($data['Account'],$Accounts) ? array_search($data['Account'],$Accounts) : 0;
        $data['Overdue']            = !empty($data['Overdue']) && $data['Overdue']== 'true'?1:0;
        $data['InvoiceNumber']      = !empty($data['InvoiceNumber']) ? $data['InvoiceNumber'] : "";
        $data['InvoiceStatus']      = !empty($data['InvoiceStatus']) ? $data['InvoiceStatus'] : "";
        $data['InvoiceType']        = 1;
        $sort_column                = $columns[$data['iSortCol_0']];
        $userID                     = 0;
        /*if(User::is('AccountManager')) { // Account Manager
            $userID = User::get_userID();
        }*/

        $query = "call prc_getInvoice (".$companyID.",".intval($data['AccountID']).",'".$data['InvoiceNumber']."','".$data['IssueDateStart']."','".$data['IssueDateEnd']."',".intval($data['InvoiceType']).",'".$data['InvoiceStatus']."',".$data['Overdue'].",".( ceil($data['iDisplayStart']/$data['iDisplayLength']) )." ,".$data['iDisplayLength'].",'".$sort_column."','".$data['sSortDir_0']."',".intval($data['CurrencyID'])."";
        if(isset($data['zerovalueinvoice']) && $data['zerovalueinvoice'] == 1){
            $query = $query.',3,0,1,"",'.$userID.')'; //3=api
        }else{
            $query .=',3,0,0,"",'.$userID.')'; //3=api
        }

        try{
            Log::info($query);
            $invoices = DataTableSql::of($query,'billing_db')->make();
            /*$invoices = DB::connection('billing_db')->select($query);
            $cnt = count($invoices);*/
            $message = "";
        }catch (\Exception $ex){
            Log::info($ex);
            $message = $ex->getMessage();
            return generateResponse($message,true,false,[]);
        }
        return generateResponse($message,false,false,$invoices);
    }

    public function getVendorInvoices(){
        $data = Input::all();

        $rules['iDisplayStart'] = 'required|Min:1';
        $rules['iDisplayLength'] = 'required';

        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            return generateResponse($validator->errors(),true);
        }
        $data['iDisplayStart'] += 1;
        $data['iSortCol_0'] = !empty($data['iSortCol_0']) ? $data['iSortCol_0'] : 0;
        $data['sSortDir_0'] = !empty($data['sSortDir_0']) ? $data['sSortDir_0'] : 'desc';

        $Currencies = Currency::getCurrencyIDList();
        $Accounts   = Account::getAccountIDList();

        $valid_invoice_status = ['draft','send','awaiting','cancel','paid','partially_paid','post'];

        if(isset($data['InvoiceStatus'])) {
            $InvoiceStatus = explode(',',$data['InvoiceStatus']);
            $data['InvoiceStatus'] = implode(',',array_intersect($valid_invoice_status,$InvoiceStatus));
        }

        $columns = ['InvoiceID','AccountName','InvoiceNumber','IssueDate','InvoicePeriod','GrandTotal','PendingAmount','InvoiceStatus','DueDate','DueDays','Currency','Description','Attachment','AccountID','OutstandingAmount','ItemInvoice','BillingEmail','GrandTotal','TotalMinutes'];
        $companyID                  = User::get_companyID();
        $data['zerovalueinvoice']   = !empty($data['zerovalueinvoice']) && $data['zerovalueinvoice']== 'true'?1:0;
        $data['IssueDateStart']     = empty($data['IssueDateStart'])?'0000-00-00 00:00:00':$data['IssueDateStart'];
        $data['IssueDateEnd']       = empty($data['IssueDateEnd'])?'0000-00-00 00:00:00':$data['IssueDateEnd'];
        $data['CurrencyID']         = !empty($data['Currency']) && array_search($data['Currency'],$Currencies) ? array_search($data['Currency'],$Currencies) : 0;
        $data['AccountID']          = !empty($data['Account']) && array_search($data['Account'],$Accounts) ? array_search($data['Account'],$Accounts) : 0;
        $data['Overdue']            = !empty($data['Overdue']) && $data['Overdue']== 'true'?1:0;
        $data['InvoiceNumber']      = !empty($data['InvoiceNumber']) ? $data['InvoiceNumber'] : "";
        $data['InvoiceStatus']      = !empty($data['InvoiceStatus']) ? $data['InvoiceStatus'] : "";
        $data['InvoiceType']        = 2;
        $userID                     = 0;
        $sort_column                = $columns[$data['iSortCol_0']];
        /*if(User::is('AccountManager')) { // Account Manager
            $userID = User::get_userID();
        }*/

        $query = "call prc_getInvoice (".$companyID.",".intval($data['AccountID']).",'".$data['InvoiceNumber']."','".$data['IssueDateStart']."','".$data['IssueDateEnd']."',".intval($data['InvoiceType']).",'".$data['InvoiceStatus']."',".$data['Overdue'].",".( ceil($data['iDisplayStart']/$data['iDisplayLength']) )." ,".$data['iDisplayLength'].",'".$sort_column."','".$data['sSortDir_0']."',".intval($data['CurrencyID'])."";
        if(isset($data['zerovalueinvoice']) && $data['zerovalueinvoice'] == 1){
            $query = $query.',3,0,1,"",'.$userID.')';
        }else{
            $query .=',3,0,0,"",'.$userID.')';
        }

        try{
            Log::info($query);
            $invoices = DataTableSql::of($query,'billing_db')->make();
            /*$invoices = DB::connection('billing_db')->select($query);
            $cnt = count($invoices);*/
            $message = "";
        }catch (\Exception $ex){
            Log::info($ex);
            $message = $ex->getMessage();
            return generateResponse($message,true,false,[]);
        }
        return generateResponse($message,false,false,$invoices);
    }

}
