<?php
namespace App;

use Collective\Remote\RemoteFacade;
use \Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
class VOS{
    private static $config = array();

    /**
        VOS cSV columns


    0	=	callere164
    1	=	calleraccesse164
    2	=	calleee164
    3	=	calleeaccesse164
    4	=	callerip
    5	=	callercodec
    6	=	callergatewayid
    7	=	callerproductid
    8	=	callertogatewaye164
    9	=	callertype
    10	=	calleeip
    11	=	calleecodec
    12	=	calleegatewayid
    13	=	calleeproductid
    14	=	calleetogatewaye164
    15	=	calleetype
    16	=	billingmode
    17	=	calllevel
    18	=	agentfeetime
    19	=	starttime
    20	=	stoptime
    21	=	callerpdd
    22	=	calleepdd
    23	=	holdtime
    24	=	callerareacode
    25	=	feetime
    26	=	fee
    27	=	tax
    28	=	suitefee
    29	=	suitefeetime
    30	=	incomefee
    31	=	incometax
    32	=	customeraccount
    33	=	customername
    34	=	calleeareacode
    35	=	agentfee
    36	=	agenttax
    37	=	agentsuitefee
    38	=	agentsuitefeetime
    39	=	agentaccount
    40	=	agentname
    41	=	flowno
    42	=	softswitchname
    43	=	softswitchcallid
    44	=	callercallid
    45	=	calleecallid
    46	=	rtpforward
    47	=	enddirection
    48	=	endreason
    49	=	billingtype
    50	=	cdrlevel
    51	=	agentcdr_id

     */

    public function __construct($CompanyGatewayID){
       $setting = GatewayAPI::getSetting($CompanyGatewayID,'VOS');
       foreach((array)$setting as $configkey => $configval){
           if($configkey == 'password'){
               self::$config[$configkey] = Crypt::decrypt($configval);
           }else{
               self::$config[$configkey] = $configval;
           }
       }
       if(count(self::$config) && isset(self::$config['host']) && isset(self::$config['username']) && isset(self::$config['password'])){
           Config::set('remote.connections.production',self::$config);
       }
    }
    public static function getCDRs($addparams=array()){
        $response = array();
        if(count(self::$config) && isset(self::$config['host']) && isset(self::$config['username']) && isset(self::$config['password'])){
            $filename = array();
            $files =  RemoteFacade::nlist(self::$config['cdr_folder']);
            foreach((array)$files as $file){
                if(strpos($file,'cdr_') !== false){
                    $filename[] =$file;
                }
            }
            asort($filename);
            $filename = array_values($filename);
            $lastele = array_pop($filename);
            $response = $filename;
        }
        return $response;
   }
   public static function deleteCDR($addparams=array()){
        $status = false;
        if(count(self::$config) && isset(self::$config['host']) && isset(self::$config['username']) && isset(self::$config['password'])){
            $status =  RemoteFacade::delete(self::$config['cdr_folder'].'/'.$addparams['filename']);
            if($status == true){

            }
        }
        return $status;
   }
   public static function downloadCDR($addparams=array()){
        $status = false;
        if(count(self::$config) && isset(self::$config['host']) && isset(self::$config['username']) && isset(self::$config['password'])){
            $status = RemoteFacade::get(self::$config['cdr_folder'] .'/'. $addparams['filename'], $addparams['download_path'] .'pending_'. $addparams['filename']);
            if(isset($addparams['download_temppath'])){
                RemoteFacade::get(self::$config['cdr_folder'] .'/'. $addparams['filename'], $addparams['download_temppath'] .'pending_'. $addparams['filename']);
            }
        }
        return $status;
   }

    public static function changeCDRFilesStatus($status,$delete_files,$CompanyGatewayID,$isSingle=false){

        if(empty($CompanyGatewayID) && !is_numeric($CompanyGatewayID)){
            throw new Exception("Invalid CompanyGatewayID");
        }

        if($status== "progress-to-pending" ) {
            if( is_array($delete_files) && count($delete_files)>0) {
                foreach ($delete_files as $filename) {
                    $inproress_name = Config::get('app.vos_location') . $CompanyGatewayID . '/' . basename($filename);
                    $complete_name = str_replace('progress', 'pending', Config::get('app.vos_location') . $CompanyGatewayID . '/' . basename($filename));
                    rename($inproress_name, $complete_name);
                    Log::info('progress-to-pending ' . $complete_name);

                }
            }
        }
        if($status== "pending-to-progress" ) {
            if($isSingle == true && is_string($delete_files) ){
                $filename = $delete_files;
                $inproress_name = Config::get('app.vos_location') . $CompanyGatewayID . '/' . basename($filename);
                $complete_file_name = str_replace('pending', 'progress', basename($filename));
                $complete_name = Config::get('app.vos_location') . $CompanyGatewayID . '/' . $complete_file_name;
                rename($inproress_name, $complete_name);
                Log::info('pending-to-progress ' . $complete_name);
                return array("new_filename"=>$complete_file_name,"new_file_fullpath"=>$complete_name);
            }
            if( is_array($delete_files) && count($delete_files)>0) {
                foreach ($delete_files as $filename) {

                    $inproress_name = Config::get('app.vos_location') . $CompanyGatewayID . '/' . basename($filename);
                    $complete_file_name = str_replace('pending', 'progress', basename($filename));
                    $complete_name = Config::get('app.vos_location') . $CompanyGatewayID . '/' . $complete_file_name;
                    rename($inproress_name, $complete_name);
                    Log::info('pending-to-progress ' . $complete_name);
                }
            }
        }
        if($status== "progress-to-complete" ) {
            if( is_array($delete_files) && count($delete_files)>0) {
                foreach ($delete_files as $filename) {
                    $inproress_name = Config::get('app.vos_location') . $CompanyGatewayID . '/' . basename($filename);
                    $complete_name = str_replace('progress', 'complete', Config::get('app.vos_location') . $CompanyGatewayID . '/' . basename($filename));
                    rename($inproress_name, $complete_name);
                    Log::info('progress-to-complete ' . $complete_name);

                    /*if(unlink($complete_name)){
                        Log::info("CDR delete file ".$filename." processID: ".$processID);
                    }else{
                        Log::info("CDR not delete file ".$filename." processID: ".$processID);
                    }*/
                }
            }
        }
    }

    /**
     * get date time from unix timestamp
     */
    public static function get_file_datetime($filename){
        $timestamp = substr(strrchr($filename, "."), 1);
        if($timestamp > 0 ){
            return gmdate('Y-m-d H:i:s', $timestamp);
        }
    }
}