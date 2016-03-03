<?php
namespace App;

use Curl\Curl;
use \Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
class Porta{
    private static $config = array();
    private static $cli;
    private static $timeout=0; /* 60 seconds timeout */

   public function __construct($CompanyGatewayID){
       $setting = GatewayAPI::getSetting($CompanyGatewayID,'Porta');
       foreach((array)$setting as $configkey => $configval){
           if($configkey == 'password'){
               self::$config[$configkey] = Crypt::decrypt($configval);
           }else{
               self::$config[$configkey] = $configval;
           }
       }
       if(count(self::$config) && isset(self::$config['api_url']) && isset(self::$config['password'])){
           self::$cli =  new Curl();
       }
    }
   public static function testConnection(){
       $response = array();
       if(count(self::$config) && isset(self::$config['api_url']) && isset(self::$config['password'])){
           $api_url = self::$config['api_url'].'/GetCustomersShortInfo/'.self::$config['password'].'/?format=json';
           self::$cli->get($api_url);
           if(isset(self::$cli->response) && self::$cli->response != '') {
                   $ResponseArray = json_decode(self::$cli->response, true);
                   if(!empty($ResponseArray) && isset($ResponseArray['CustomerRes'])) {
                       $response['result'] = 'OK';
                   }
           }else if(isset(self::$cli->error_message) && isset(self::$cli->error_code)){
               $response['faultString'] =  self::$cli->error_message;
               $response['faultCode'] =  self::$cli->error_code;
           }
       }
       return $response;
   }
    public static function listAccounts($addparams=array()){
        $response = array();
        if(count(self::$config) && isset(self::$config['api_url']) && isset(self::$config['password'])){
            $api_url = self::$config['api_url'].'/getcustomersshortinfowithnolimit/'.self::$config['password'].'/true?format=json';
            self::$cli->get($api_url);
            if(isset(self::$cli->response) && self::$cli->response != '') {
                $ResponseArray = json_decode(self::$cli->response, true);
                if(!empty($ResponseArray) && isset($ResponseArray['CustomerRes'])) {
                    $response = $ResponseArray;
                    $response['result'] = 'OK';
                }
            }else if(isset(self::$cli->error_message) && isset(self::$cli->error_code)){
                $response['faultString'] =  self::$cli->error_message;
                $response['faultCode'] =  self::$cli->error_code;
                Log::error("Class Name:".__CLASS__.",Method: ". __METHOD__.", Fault. Code: " . self::$cli->error_code. ", Reason: " . self::$cli->error_message);
                throw new Exception(self::$cli->error_message);
            }
        }
        return $response;
    }
    public static function listCustomer($addparams=array()){
        $response = array();
        if(count(self::$config) && isset(self::$config['api_url']) && isset(self::$config['password']) && isset($addparams['ICustomer'])){
            echo $api_url = self::$config['api_url'].'/GetAccountByICustomer/'.self::$config['password'].'/'.$addparams['ICustomer'].'/?format=json';
            self::$cli->get($api_url);
            if(isset(self::$cli->response) && self::$cli->response != '') {
                $ResponseArray = json_decode(self::$cli->response, true);
                if(!empty($ResponseArray) && isset($ResponseArray['Accounts'])) {
                    $response = $ResponseArray;
                    $response['result'] = 'OK';
                }
            }else if(isset(self::$cli->error_message) && isset(self::$cli->error_code)){
                $response['faultString'] =  self::$cli->error_message;
                $response['faultCode'] =  self::$cli->error_code;
                Log::error("Class Name:".__CLASS__.",Method: ". __METHOD__.", Fault. Code: " . self::$cli->error_code. ", Reason: " . self::$cli->error_message);
                throw new Exception(self::$cli->error_message);
            }
        }
        return $response;

    }
    public static function getAccountCDRs($addparams=array()){
        $response = array();
        if(count(self::$config) && isset(self::$config['api_url']) && isset(self::$config['password']) && isset($addparams['ICustomer'])){
            $addparams['start_date_ymd'] = str_replace(" ","%20",$addparams['start_date_ymd']);
            $addparams['end_date_ymd'] = str_replace(" ","%20",$addparams['end_date_ymd']);
            $api_url = self::$config['api_url'].'/getCustomerAccountCDRS/'.self::$config['password'].'/'.$addparams['ICustomer'].'/'.$addparams['start_date_ymd'].'/'.$addparams['end_date_ymd'].'/true/?format=json';
            self::$cli->get($api_url);
            //print_r(self::$cli->response);
            if(isset(self::$cli->response) && self::$cli->response != '') {
                $ResponseArray = json_decode(self::$cli->response, true);
                if(!empty($ResponseArray) && isset($ResponseArray['DictPortaCustomerAccountCDRS']['Voice Calls'])) {
                    $response = $ResponseArray;
                    $response['result'] = 'OK';
                }
            }else if(isset(self::$cli->error_message) && isset(self::$cli->error_code)){
                $response['faultString'] =  self::$cli->error_message;
                $response['faultCode'] =  self::$cli->error_code;
                Log::error("Class Name:".__CLASS__.",Method: ". __METHOD__.", Fault. Code: " . self::$cli->error_code. ", Reason: " . self::$cli->error_message);
                throw new Exception(self::$cli->error_message);
            }
        }
        return $response;

    }
    public static function listVendors($addparams=array()){

    }

}