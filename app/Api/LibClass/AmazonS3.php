<?php
namespace App;
use Api\Model\CompanyConfiguration;
use Api\Model\User;
use Aws\S3\S3Client;
use Illuminate\Support\Facades\Log;

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
        'OPPORTUNITY_ATTACHMENT'=>'OpportunityAttachment',
        'TASK_ATTACHMENT'=>'TaskAttachment',
        'EMAIL_ATTACHMENT'=>'EmailAttachment',
    );

    /** Get Amazon Settings from Company Config table
     * @return array|mixed
     */
    private static function getAmazonSettings(){

      /*  $cache = CompanyConfiguration::getConfiguration();
        $amazon = array();
        if(isset($cache['Amazon'])) {

            $amazoneJson = $cache['Amazon'];

            if (!empty($amazoneJson)) {
                $amazon = json_decode($amazoneJson, true);
             }
        }*/
		$amazon 		= 	array();
		$AmazonData		=	\App\SiteIntegration::is_amazon_configured(true);
		
		if(!empty($AmazonData)){
			$amazon 	=	 array("AWS_BUCKET"=>$AmazonData->AmazonAwsBucket,"AMAZONS3_KEY"=>$AmazonData->AmazonKey,"AMAZONS3_SECRET"=>$AmazonData->AmazonSecret,"AWS_REGION"=>$AmazonData->AmazonAwsRegion);	
		}
		
        return $amazon;
    }

    private static function getBucket(){

        $amazon = self::getAmazonSettings();
        if(isset($amazon['AWS_BUCKET'])){

            return $amazon['AWS_BUCKET'];
        }else {
            return "";
        }

    }

    // Instantiate an S3 client
    private static function getS3Client(){

        $AMAZONS3_KEY  = '';
        $AMAZONS3_SECRET = '';
        $AWS_REGION = '';

        $amazon = self::getAmazonSettings();
        if(isset($amazon['AMAZONS3_KEY']) && isset($amazon['AMAZONS3_SECRET']) && $amazon['AWS_REGION'] && $amazon['AWS_REGION']){

            $AMAZONS3_KEY = $amazon['AMAZONS3_KEY'];
            $AMAZONS3_SECRET = $amazon['AMAZONS3_SECRET'];
            $AWS_REGION = $amazon['AWS_REGION'];
        }

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

    static function preSignedUrl($key=''){

        $s3 = self::getS3Client();

        //When no amazon ;
        if($s3 == 'NoAmazon'){
            $status = RemoteSSH::downloadFile($key);
            return $status['filePath'];
        }
        $bucket = self::getBucket();
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
Log::info("amazon unsignedUrl1");
        $s3 = self::getS3Client();

        //When no amazon ;
        if($s3 == 'NoAmazon'){
            return  self::preSignedUrl($key);
        }
        $bucket = self::getBucket();
        $unsignedUrl = '';
        if(!empty($key)){

            $unsignedUrl = $s3->getObjectUrl($bucket, $key);
        } 
        return $unsignedUrl;

    }

    //@TODO: need to update when needed
    static function unSignedImageUrl($key=''){

        $s3 = self::getS3Client();

        //When no amazon ;
        if($s3 == 'NoAmazon'){

            $site_url = \Api\Model\CompanyConfiguration::get("SITE_URL");

            return combile_url_path($site_url,$key);

        }
        return self::unSignedUrl($key);
    }

    /** Delete file from amazon or ssh.
     * @param $file
     * @return bool
     */
    static function delete($file){

        if(strlen($file)>0) {
            // Instantiate an S3 client
            $s3 = self::getS3Client();

            //When no amazon ;
            if($s3 == 'NoAmazon'){

                $upload_path = CompanyConfiguration::get("UPLOADPATH");
                $file_path = rtrim($upload_path,'/').'/'. $file;
                return RemoteSSH::deleteFile($file_path);

            }

            $bucket = self::getBucket();
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
