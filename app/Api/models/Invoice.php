<?php
namespace Api\Model;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Invoice extends Model{
    protected $connection = 'sqlsrv2';
    protected $fillable = [];
    protected $guarded = array('InvoiceID');
    protected $table = 'tblInvoice';
    protected  $primaryKey = "InvoiceID";
    const  INVOICE_OUT = 1;
    const  INVOICE_IN= 2;
    const DRAFT = 'draft';
    const SEND = 'send';
    const AWAITING = 'awaiting';
    const CANCEL = 'cancel';
    const RECEIVED = 'received';
    const PAID = 'paid';
    const PARTIALLY_PAID = 'partially_paid';
    const ITEM_INVOICE =1;
    //public static $invoice_status;
    public static $invoice_type = array(''=>'Select an Invoice Type' ,self::INVOICE_OUT => 'Invoice Sent',self::INVOICE_IN=>'Invoice Received','All'=>'Both');
    public static $invoice_type_customer = array(''=>'Select an Invoice Type' ,self::INVOICE_OUT => 'Invoice Received',self::INVOICE_IN=>'Invoice sent','All'=>'Both');

    public static function getInvoiceEmailTemplate($data){

        $message = '[CompanyName] has sent you an invoice of [GrandTotal] [CurrencyCode], '. PHP_EOL. 'to download copy of your invoice please click the below link.';

        $message = str_replace("[CompanyName]",$data['CompanyName'],$message);
        $message = str_replace("[GrandTotal]",$data['GrandTotal'],$message);
        $message = str_replace("[CurrencyCode]",$data['CurrencyCode'],$message);
        return $message;
    }

    public static function getLastInvoiceDate($AccountID){

        /**
         *   Get EndDate from InvoiceDetail
         *      Where ProductType is USAGE = Product::USAGE OR SUBSCRIPTION = Product::SUBSCRIPTION
         */



        if($AccountID > 0) {

            $LastInvoiceDate = Account::where("AccountID",$AccountID)->pluck("LastInvoiceDate");
            if(!empty($LastInvoiceDate)) {
                return $LastInvoiceDate;
            }

            /*$LastInvoiceDate = Invoice::join('tblInvoiceDetail', 'tblInvoice.InvoiceID', '=', 'tblInvoiceDetail.InvoiceID')
                ->where("tblInvoice.AccountID", $AccountID)
                ->whereRaw("(tblInvoiceDetail.ProductType = " . Product::USAGE . ' OR ' . "tblInvoiceDetail.ProductType = " . Product::SUBSCRIPTION . ")")// Only take Invoice which has Usage Item
                ->orderby("tblInvoiceDetail.EndDate","desc")
                ->pluck("EndDate");

            return strtotime("Y-m-d", strtotime( "+1 Day", $LastInvoiceDate));*/
        }

    }

    public static function getNextInvoiceDate($AccountID){

        /**
         * Assumption : If Billing Cycle is 7 Days then Usage and Subscription both will be 7 Days and same for Monthly and other billing cycles..
        * */

        $Account = Account::select(["NextInvoiceDate","LastInvoiceDate","BillingStartDate"])->where("AccountID",$AccountID)->first()->toArray();

        $BillingCycle = Account::select(["BillingCycleType","BillingCycleValue"])->where("AccountID",$AccountID)->first()->toArray();
                        //"weekly"=>"Weekly", "monthly"=>"Monthly" , "daily"=>"Daily", "in_specific_days"=>"In Specific days", "monthly_anniversary"=>"Monthly anniversary");

        $NextInvoiceDate = "";
        $BillingStartDate = "";
        if(!empty($Account['LastInvoiceDate'])) {
            $BillingStartDate = strtotime($Account['LastInvoiceDate']);
        }else if(!empty($Account['BillingStartDate'])) {
            $BillingStartDate = strtotime($Account['BillingStartDate']);
        }else{
            return '';
        }

        if(isset($BillingCycle['BillingCycleType'])) {

            $BillingTimezone = CompanySetting::getKeyVal("BillingTimezone");

            if($BillingTimezone != 'Invalid Key'){
                date_default_timezone_set($BillingTimezone);
            }

            switch ($BillingCycle['BillingCycleType']) {
                case 'weekly':
                    if (!empty($BillingCycle['BillingCycleValue'])) {
                        $NextInvoiceDate = date("Y-m-d", strtotime("next " . $BillingCycle['BillingCycleValue'],$BillingStartDate));
                    }
                    break;
                case 'monthly':
                        $NextInvoiceDate = date("Y-m-d", strtotime("first day of next month ",$BillingStartDate));
                    break;
                case 'daily':
                        $NextInvoiceDate = date("Y-m-d", strtotime("+1 Days",$BillingStartDate));
                    break;
                case 'in_specific_days':
                    if (!empty($BillingCycle['BillingCycleValue'])) {
                            $NextInvoiceDate = date("Y-m-d", strtotime("+" . intval($BillingCycle['BillingCycleValue']) . " Day",$BillingStartDate));
                    }
                    break;
                case 'monthly_anniversary':

                    $day = date("d",  strtotime($BillingCycle['BillingCycleValue'])); // Date of Anivarsary
                    $month = date("m",  $BillingStartDate); // Month of Last Invoice date or Start Date
                    $year = date("Y",  $BillingStartDate); // Year of Last Invoice date or Start Date

                    $newDate = strtotime($year . '-' . $month . '-' . $day);

                    $NextInvoiceDate = date("Y-m-d", strtotime("+1 month", $newDate ));

                    break;
                case 'fortnightly':
                    $fortnightly_day = date("d", $BillingStartDate);
                    if($fortnightly_day > 15){
                        $NextInvoiceDate = date("Y-m-d", strtotime("first day of next month ",$BillingStartDate));
                    }else{
                        $NextInvoiceDate = date("Y-m-16", $BillingStartDate);
                    }
                    break;
                case 'quarterly':
                    $quarterly_month = date("m", $BillingStartDate);
                    if($quarterly_month < 4){
                        $NextInvoiceDate = date("Y-m-d", strtotime("first day of april ",$BillingStartDate));
                    }else if($quarterly_month > 3 && $quarterly_month < 7) {
                        $NextInvoiceDate = date("Y-m-d", strtotime("first day of july ",$BillingStartDate));
                    }else if($quarterly_month > 6 && $quarterly_month < 10) {
                        $NextInvoiceDate = date("Y-m-d", strtotime("first day of october ",$BillingStartDate));
                    }else if($quarterly_month > 9){
                        $NextInvoiceDate = date("Y-01-01", strtotime('+1 year ',$BillingStartDate));
                    }
                    break;
            }

            date_default_timezone_set(Config::get("app.timezone"));
        }

        return $NextInvoiceDate;

    }

    public static  function generate_pdf($InvoiceID){
        if($InvoiceID>0) {
            $Invoice = Invoice::find($InvoiceID);
            $InvoiceDetail = InvoiceDetail::where(["InvoiceID" => $InvoiceID])->get();
            $Account = Account::find($Invoice->AccountID);
            $Currency = Currency::find($Account->CurrencyId);
            $CurrencyCode = !empty($Currency)?$Currency->Code:'';
            $CurrencySymbol =  Currency::getCurrencySymbol($Account->CurrencyId);
            $InvoiceTemplate = InvoiceTemplate::find($Account->InvoiceTemplateID);
            if (empty($InvoiceTemplate->CompanyLogoUrl) || AmazonS3::unSignedUrl($InvoiceTemplate->CompanyLogoAS3Key) == '') {
                $as3url =  public_path("/assets/images/250x100.png");
            } else {
                $as3url = (AmazonS3::unSignedUrl($InvoiceTemplate->CompanyLogoAS3Key));
            }
            $upload_path = CompanyConfiguration::get("UPLOAD_PATH");
            chmod($upload_path,0777);
            $logo = $upload_path . '/' . basename($as3url);
            file_put_contents($logo, file_get_contents($as3url));
            chmod($logo,0777);

            $arrSignature=array();
            $arrSignature["UseDigitalSignature"] = CompanySetting::getKeyVal('UseDigitalSignature', $Account->CompanyId);
            $arrSignature["DigitalSignature"] = CompanySetting::getKeyVal('DigitalSignature', $Account->CompanyId);
            $arrSignature["signaturePath"]= CompanyConfiguration::get('UPLOAD_PATH')."/".AmazonS3::generate_upload_path(AmazonS3::$dir['DIGITAL_SIGNATURE_KEY'], '', $Account->CompanyId, true);
            if($arrSignature["DigitalSignature"]!="Invalid Key"){
                $arrSignature["DigitalSignature"]=json_decode($arrSignature["DigitalSignature"]);
            }else{
                $arrSignature["UseDigitalSignature"]=false;
            }

            $InvoiceTemplate->DateFormat = invoice_date_fomat($InvoiceTemplate->DateFormat);
            $file_name = 'Invoice--' .$Account->AccountName.'-' .date($InvoiceTemplate->DateFormat) . '.pdf';
            $htmlfile_name = 'Invoice--' .$Account->AccountName.'-' .date($InvoiceTemplate->DateFormat) . '.html';


            $body = View::make('invoices.pdf', compact('Invoice', 'InvoiceDetail', 'Account', 'InvoiceTemplate', 'CurrencyCode', 'logo','CurrencySymbol', 'arrSignature'))->render();

            $body = htmlspecialchars_decode($body);
            $footer = View::make('invoices.pdffooter', compact('Invoice'))->render();
            $footer = htmlspecialchars_decode($footer);

            $amazonPath = AmazonS3::generate_path(AmazonS3::$dir['INVOICE_UPLOAD'],$Account->CompanyId,$Invoice->AccountID) ;
            $upload_path = CompanyConfiguration::get("UPLOAD_PATH");
             $destination_dir = $upload_path . '/'. $amazonPath;
			
            if (!file_exists($destination_dir)) {
                mkdir($destination_dir, 0777, true);
            }
            $file_name = \Nathanmac\GUID\Facades\GUID::generate() .'-'. $file_name;
            $htmlfile_name = \Nathanmac\GUID\Facades\GUID::generate() .'-'. $htmlfile_name;
            $local_file = $destination_dir .  $file_name;

            $local_htmlfile = $destination_dir .  $htmlfile_name;
            file_put_contents($local_htmlfile,$body);
            chmod($local_htmlfile,0777);
            $footer_name = 'footer-'. \Nathanmac\GUID\Facades\GUID::generate() .'.html';
            $footer_html = $destination_dir.$footer_name;
            file_put_contents($footer_html,$footer);
            chmod($footer_html,0777);
            $output= "";
            if(getenv('APP_OS') == 'Linux'){
                exec (base_path(). '/wkhtmltox/bin/wkhtmltopdf --footer-html "'.$footer_html.'" "'.$local_htmlfile.'" "'.$local_file.'"',$output);
                Log::info(base_path(). '/wkhtmltox/bin/wkhtmltopdf --footer-html "'.$footer_html.'" "'.$local_htmlfile.'" "'.$local_file.'"',$output);

                if($arrSignature["UseDigitalSignature"]==true){
                    $newlocal_file = $destination_dir . str_replace(".pdf","-signature.pdf",$file_name);
                    $mypdfsignerOutput=RemoteSSH::run('PortableSigner  -n     -t '.$local_file.'      -o '.$newlocal_file.'     -s '.$arrSignature["signaturePath"].'digitalsignature.pfx -c "Signed after 4 alterations" -r "Approved for publication" -l "Department of Dermatology" -p Welcome100');
                    Log::info($mypdfsignerOutput);
                    if(file_exists($newlocal_file)){
                        RemoteSSH::run('rm '.$local_file);
                        RemoteSSH::run('mv '.$newlocal_file.' '.$local_file);
                    }
                }

            }else{
                exec (base_path().'/wkhtmltopdf/bin/wkhtmltopdf.exe --footer-html "'.$footer_html.'" "'.$local_htmlfile.'" "'.$local_file.'"',$output);
                Log::info (base_path().'/wkhtmltopdf/bin/wkhtmltopdf.exe --footer-html "'.$footer_html.'" "'.$local_htmlfile.'" "'.$local_file.'"',$output);
            }
            @chmod($local_file,0777);
            Log::info($output);
            @unlink($local_htmlfile);
            @unlink($footer_html);
            if (file_exists($local_file)) {
                $fullPath = $amazonPath . basename($local_file); //$destinationPath . $file_name;
                if (AmazonS3::upload($local_file, $amazonPath)) {
                    return $fullPath;
                }
            }
            return '';
        }
    }

    public static function get_invoice_status(){
        $Company = Company::find(User::get_companyID());
        $invoiceStatus = explode(',',$Company->InvoiceStatus);
       $invoicearray = array(''=>'Select Invoice Status',self::DRAFT=>'Draft',self::SEND=>'Sent',self::AWAITING=>'Awaiting Approval',self::CANCEL=>'Cancel',self::PAID=>'Paid',self::PARTIALLY_PAID=>'Partially Paid');
        foreach($invoiceStatus as $status){
            $invoicearray[$status] = $status;
        }
        return $invoicearray;
    }
    public static function getFullInvoiceNumber($Invoice,$Account){
        $InvoiceNumberPrefix = '';
        if(!empty($Account->InvoiceTemplateID)) {
            $InvoiceNumberPrefix = InvoiceTemplate::find($Account->InvoiceTemplateID)->InvoiceNumberPrefix;
        }
        return $InvoiceNumberPrefix.$Invoice->InvoiceNumber;
    }

}