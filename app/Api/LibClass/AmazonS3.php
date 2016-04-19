<?php
namespace App;
use Api\Model\User;
use Aws\S3\S3Client;

class AmazonS3 {

    public static $dir = array(
        'CODEDECK_UPLOAD' =>  'CodedecksUploads',
        'VENDOR_UPLOAD' =>  'VendorUploads',
        'VENDOR_DOWNLOAD' =>  'VendorDownloads',
        'CUSTOMER_DOWNLOAD' =>  'CustomerDownloads',
        'ACCOUNT_APPROVAL_CHECKLIST_FORM' =>  'AccountApprovalChecklistForms',
        'ACCOUNT_DOCUMENT' =>  'AccountDocuments',
        'INVOICE_COMPANY_LOGO' =>  'InvoiceCompanyLogos',
        'PAYMENT_PROOF'=>'PaymentProof',
        'INVOICE_PROOF_ATTACHMENT' =>  'InvoiceProofAttachment',
        'INVOICE_UPLOAD' =>  'Invoices',
        'CUSTOMER_PROFILE_IMAGE' =>  'CustomerProfileImage',
        'BULK_LEAD_MAIL_ATTACHEMENT' => 'bulkleadmailattachment',
        'TEMPLATE_FILE' => 'TemplateFile',
        'CDR_UPLOAD'=>'CDRUPload',
        'VENDOR_TEMPLATE_FILE' => 'vendortemplatefile',
        'BULK_ACCOUNT_MAIL_ATTACHEMENT' =>'bulkaccountmailattachment',
        'BULK_INVOICE_MAIL_ATTACHEMENT'=>'bulkinvoicemailattachment',
        'RATETABLE_UPLOAD'=>'RateTableUpload',
        'WYSIHTML5_FILE_UPLOAD'=>'Wysihtml5fileupload',
        'PAYMENT_UPLOAD'=>'PaymentUpload',
        'OPPORTUNITY_ATTACHMENT'=>'OpportunityAttachment'
    );

    // Instantiate an S3 client
    private static function getS3Client(){

        $AMAZONS3_KEY  = getenv("AMAZONS3_KEY");
        $AMAZONS3_SECRET = getenv("AMAZONS3_SECRET");
        $AWS_REGION = getenv("AWS_REGION");

        if(empty($AMAZONS3_KEY) || empty($AMAZONS3_SECRET) || empty($AWS_REGION) ){
            return 'NoAmazon';
        }else {

            return $s3Client = S3Client::factory(array(
                'region' => $AWS_REGION,
                'credentials' => array(
                    'key' => $AMAZONS3_KEY,
                    'secret' => $AMAZONS3_SECRET
                ),
            ));
        }
    }

    /*
     * Generate Path
     * Ex. WaveTell/18-Y/VendorUploads/2015/05
     * */
    static function generate_upload_path($dir ='',$accountId = '' ) {

        if(empty($dir))
            return false;
        $CompanyID = User::get_companyID();//   Str::slug(Company::getName());

        $path = self::generate_path($dir,$CompanyID,$accountId);

        return $path;
    }

    static function generate_path($dir ='',$companyId , $accountId = '' ) {

        $path = $companyId  ."/";

        if($accountId > 0){
            $path .= $accountId ."/";
        }

        $path .=  $dir . "/". date("Y")."/".date("m") ."/" .date("d") ."/";
        $dir = getenv('UPLOAD_PATH') . '/'. $path;
        if (!file_exists($dir)) {
            mkdir($dir, 0777, TRUE);
        }

        return $path;
    }

    static function upload($file,$dir){

        // Instantiate an S3 client
        $s3 = self::getS3Client();

        //When no amazon return true;
        if($s3 == 'NoAmazon'){
            return true;
        }

        $bucket = getenv('AWS_BUCKET');
        // Upload a publicly accessible file. The file size, file type, and MD5 hash
        // are automatically calculated by the SDK.
        try {
            $resource = fopen($file, 'r');
            $s3->upload($bucket, $dir.basename($file), $resource, 'public-read');
            @unlink($file);
            return true;
        } catch (S3Exception $e) {
            return false ; //"There was an error uploading the file.\n";
        }
    }

    static function preSignedUrl($key=''){

        $s3 = self::getS3Client();

        //When no amazon ;
        if($s3 == 'NoAmazon'){
            $Uploadpath = Config::get('app.upload_path')."/".$key;
            if ( file_exists($Uploadpath) ) {
                return $Uploadpath;
            } else {
                return "";
            }
        }


        $bucket = getenv('AWS_BUCKET');

        // Get a command object from the client and pass in any options
        // available in the GetObject command (e.g. ResponseContentDisposition)
        $command = $s3->getCommand('GetObject', array(
            'Bucket' => $bucket,
            'Key' => $key,
            'ResponseContentDisposition' => 'attachment; filename="'. basename($key) . '"'
        ));

        // Create a signed URL from the command object that will last for
        // 10 minutes from the current time
        $signedUrl = $command->createPresignedUrl('+10 minutes');
        return $signedUrl;

    }

    static function unSignedUrl($key=''){

        $s3 = self::getS3Client();

        //When no amazon ;
        if($s3 == 'NoAmazon'){
            return  self::preSignedUrl($key);
        }

        $bucket = getenv('AWS_BUCKET');
        $unsignedUrl = '';
        if(!empty($key)){
           $unsignedUrl = $s3->getObjectUrl($bucket, $key);
        }
        return $unsignedUrl;

    }

    static function unSignedImageUrl($key=''){

        $s3 = self::getS3Client();

        //When no amazon ;
        if($s3 == 'NoAmazon'){
            $file = getenv("UPLOAD_PATH") . '/' . $key;
            if ( file_exists($file) ) {
                return  get_image_data($file);
            } else {
                return get_image_data("http://placehold.it/250x100");
            }
        }
        return self::unSignedUrl($key);
    }

    static function delete($file){

        if(strlen($file)>0) {
            // Instantiate an S3 client
            $s3 = self::getS3Client();

            //When no amazon ;
            if($s3 == 'NoAmazon'){
                $Uploadpath = getenv('UPLOAD_PATH') . "/"."".$file;
                if ( file_exists($Uploadpath) ) {
                    @unlink($Uploadpath);
                    return true;
                } else {
                    return false;
                }
            }

            $bucket = getenv('AWS_BUCKET');
            // Upload a publicly accessible file. The file size, file type, and MD5 hash
            // are automatically calculated by the SDK.
            try {
                $result = $s3->deleteObject(array('Bucket' => $bucket, 'Key' => $file));
                return true;
            } catch (S3Exception $e) {
                return false; //"There was an error uploading the file.\n";
            }
        }else{
            return false;
        }
    }
}
