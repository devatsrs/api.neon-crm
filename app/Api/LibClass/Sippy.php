<?php

namespace App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use \Exception;

//use App\Lib\Xmlrpc;

/*Class for sippy api
 *@author:girish.vadher.it@gmail.com
 *Date:08-Dec-2014
 */

//require_once 'xmlrpc/xmlrpc.inc';

class Sippy{
    private static $config = array();
    private static $cli;
    private static $timeout=0; /* 60 seconds timeout */

   public function __construct($CompanyGatewayID){
       $setting = GatewayAPI::getSetting($CompanyGatewayID,'sippy');
       foreach((array)$setting as $configkey => $configval){
           if($configkey == 'password'){
               self::$config[$configkey] = Crypt::decrypt($configval);
           }else{
               self::$config[$configkey] = $configval;
           }
       }
       if(count(self::$config)>0) {
           self::$cli = new xmlrpc_client(self::$config['api_url']);
           self::$cli->return_type = 'phpvals';
           //self::$cli->debug =2;
           self::$cli->setSSLVerifyPeer(false);
           self::$cli->setSSLVerifyHost(2);
           self::$cli->setCredentials(self::$config['username'], self::$config['password'], CURLAUTH_DIGEST);
       }

    }
   public static function testConnection(){
        if(count(self::$config)>0) {
            $params = array(new xmlrpcval(array(
                "offset" => new xmlrpcval('0', "int"),
                "limit" => new xmlrpcval('1', "int"),
            ), 'struct'));
            $msg = new xmlrpcmsg('listAccounts', $params);
            $r = self::$cli->send($msg, self::$timeout);
            if ($r->faultCode()) {
                //echo $r->faultCode();echo $r->faultString();exit;
                error_log("Fault. Code: " . $r->faultCode() . ", Reason: " . $r->faultString());
                Log::error("Class Name:".__CLASS__.",Method: ". __METHOD__.", Fault. Code: " . $r->faultCode() . ", Reason: " . $r->faultString());
                throw new Exception($r->faultString());
            }
            return $r->value();
        }
   }
    public static function listAccounts($addparams=array()){
        if(count(self::$config)>0) {
            $params = array(new xmlrpcval($addparams,'struct'));
            $msg = new xmlrpcmsg('listAccounts', $params);
            $r = self::$cli->send($msg, self::$timeout);
            if ($r->faultCode()) {
                //echo $r->faultCode();echo $r->faultString();exit;
                error_log("Fault. Code: " . $r->faultCode() . ", Reason: " . $r->faultString());
                Log::error("Class Name:".__CLASS__.",Method: ". __METHOD__.", Fault. Code: " . $r->faultCode() . ", Reason: " . $r->faultString());
                throw new Exception($r->faultString());
            }
            return $r->value();
        }
    }
    public static function getAccountCDRs($addparams=array()){
        if(count(self::$config)>0) {
           if(isset($addparams['i_account'])){
                $addparams['i_account'] = new xmlrpcval($addparams["i_account"], "int");
            }
            if(isset($addparams['offset'])){
                $addparams['offset'] = new xmlrpcval($addparams["offset"], "int");
            }
            if(isset($addparams['limit'])){
                $addparams['limit'] = new xmlrpcval($addparams["limit"], "int");
            }

            if(isset($addparams['type'])){
                $addparams['type'] = new xmlrpcval($addparams["type"], "string");
            }else{
                $addparams['type'] = new xmlrpcval("non_zero", "string");
            }

            if(isset($addparams['start_date'])){
                $addparams['start_date'] = new xmlrpcval($addparams["start_date"], "string");
            }
            if(isset($addparams['end_date'])){
                $addparams['end_date'] = new xmlrpcval($addparams["end_date"], "string");
            }

            //print_r(new xmlrpcval($addparams["start_date"], "string"));
            //exit;

            $params = array(new xmlrpcval($addparams,'struct'));

            $msg = new xmlrpcmsg('getAccountCDRs', $params);

            $r = self::$cli->send($msg, self::$timeout);
            if ($r->faultCode()) {
                //echo $r->faultCode();echo $r->faultString();exit;
                error_log("Fault. Code: " . $r->faultCode() . ", Reason: " . $r->faultString());
                Log::error("Class Name:".__CLASS__.",Method: ". __METHOD__.", Fault. Code: " . $r->faultCode() . ", Reason: " . $r->faultString());
                throw new Exception($r->faultString());
            }
            return $r->value();
        }
    }


}