<?php
namespace App;

use Collective\Remote\RemoteFacade;
use \Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
class SippySSH{
    private static $config = array();
    private static $sippy_file_location = "";

    public static $customer_cdr_file_name = "cdrs-thrift.bin.";
    public static $vendor_cdr_file_name = "cdrs_connections-thrift.bin.";

    public function __construct($CompanyGatewayID){
        $setting = GatewayAPI::getSetting($CompanyGatewayID,'SippySFTP');
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

    /** Download Customer and Vendor CDR
     * @param array $addparams
     * @return array
     */
    public static function getCDRs($addparams=array()){
        $response = array();
        if(count(self::$config) && isset(self::$config['host']) && isset(self::$config['username']) && isset(self::$config['password'])){
            $filename = array();
            $files =  RemoteFacade::nlist(self::$config['cdr_folder']);
            foreach((array)$files as $file){
                if(strpos($file,self::$customer_cdr_file_name) !== false || strpos($file,self::$vendor_cdr_file_name) !== false){
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
            $status =  RemoteFacade::delete(rtrim(self::$config['cdr_folder'],'/').'/'.$addparams['filename']);
            if($status == true){
                //Log::info('File deleted on server ' . rtrim(self::$config['cdr_folder'],'/').'/'.$addparams['filename']);
            }else{
                Log::info('Failed to delete on server ' . rtrim(self::$config['cdr_folder'],'/').'/'.$addparams['filename']);
            }
        }
        return $status;
    }
    public static function downloadCDR($addparams=array()){
        $status = false;
        if(count(self::$config) && isset(self::$config['host']) && isset(self::$config['username']) && isset(self::$config['password'])){
            $source = rtrim(self::$config['cdr_folder'],'/') .'/'. $addparams['filename'];
            $destination = $addparams['download_path'] .'pending_'. $addparams['filename'];
            $status = RemoteFacade::get($source, $destination );

            //Encode file
            //self::encode_file($destination);

            if(isset($addparams['download_temppath'])){
                RemoteFacade::get(rtrim(self::$config['cdr_folder'],'/') .'/'. $addparams['filename'], $addparams['download_temppath'] .'pending_'. $addparams['filename']);
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
                    $inproress_name = getenv("SIPPYFILE_LOCATION") . $CompanyGatewayID . '/' . basename($filename);
                    $complete_name = str_replace('progress', 'pending', getenv("SIPPYFILE_LOCATION") . $CompanyGatewayID . '/' . basename($filename));
                    rename($inproress_name, $complete_name);
                    Log::info('progress-to-pending ' . $complete_name);

                }
            }
        }
        if($status== "pending-to-progress" ) {
            if($isSingle == true && is_string($delete_files) ){
                $filename = $delete_files;
                $inproress_name = getenv("SIPPYFILE_LOCATION") . $CompanyGatewayID . '/' . basename($filename);
                $complete_file_name = str_replace('pending', 'progress', basename($filename));
                $complete_name = getenv("SIPPYFILE_LOCATION") . $CompanyGatewayID . '/' . $complete_file_name;
                rename($inproress_name, $complete_name);
                Log::info('pending-to-progress ' . $complete_name);
                return array("new_filename"=>$complete_file_name,"new_file_fullpath"=>$complete_name);
            }
            if( is_array($delete_files) && count($delete_files)>0) {
                foreach ($delete_files as $filename) {

                    $inproress_name = getenv("SIPPYFILE_LOCATION") . $CompanyGatewayID . '/' . basename($filename);
                    $complete_file_name = str_replace('pending', 'progress', basename($filename));
                    $complete_name = getenv("SIPPYFILE_LOCATION") . $CompanyGatewayID . '/' . $complete_file_name;
                    rename($inproress_name, $complete_name);
                    Log::info('pending-to-progress ' . $complete_name);
                }
            }
        }
        if($status== "progress-to-complete" ) {
            if( is_array($delete_files) && count($delete_files)>0) {
                foreach ($delete_files as $filename) {
                    $inproress_name = getenv("SIPPYFILE_LOCATION") . $CompanyGatewayID . '/' . basename($filename);
                    $complete_name = str_replace('progress', 'complete', getenv("SIPPYFILE_LOCATION") . $CompanyGatewayID . '/' . basename($filename));
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

    //Encode file into csv
    // Not in use
    public static function encode_file($encoded_file){

        $sippy_decoder = getenv("SIPPY_CSVDECODER"); // Sippy decoder command
        $destination = $encoded_file . '.csv';

        /**
         *  Following command will generante new csv file from sippy encoded file
         *  and delete encoded file after csv created.
         */
        if(file_exists($encoded_file)) {

            exec($sippy_decoder . " " . $encoded_file . " > " . $destination);
            Log::info( "Sippy Encoded " . $encoded_file );
            Log::info( "Sippy decoded csv " . $destination );
        }else{
            Log::error( "Sippy Encoded File not found ." . $encoded_file );
        }

    }

    /** Decode Customer file content and return array response
     * @param $sippy_file
     * @return array
     */
    public static function get_customer_file_content($sippy_file) {

        $sippy_decoder = getenv("SIPPY_CSVDECODER"); // Sippy decoder command
        exec($sippy_decoder . " customer " . $sippy_file ,$output,$return_var);
        Log::info($sippy_decoder . " customer " . $sippy_file );

        $cdr_array = [];
        foreach($output as $op_row){

            $col_vals = explode(",",$op_row);

            $cdr_row = array();
            /**
             *
            Account - i_account (only the ID of account can be provided instead of the account's username value)
            connect_time - connect_time
            disconnect_time - disconnect_time
            call_duration - duration
            billed_duration - billed_duration
            cli - cli_in (only the 'Incoming CLI' number that is passed to the switch for further call authorization and routing can be retrieved here, the actual CLI number can be fetched from the calls table instead)
            cld - cld_in (only the 'Incoming CLD' number that is passed to the switch for further call authorization and routing can be retrieved here, the actual CLD number can be fetched from the calls table instead)
            cost - cost
            remote_ip - remote_ip

            ==================================================
            0	=	i_cdr	            =	994252378
            1	=	i_call	            =	994257953
            2	=	i_account	        =	266
            3	=	result	            =	-17
            4	=	cost	            =	0
            5	=	delay	            =	0
            6	=	duration	        =	0
            7	=	billed_duration	    =	0
            8	=	connect_time	    =	1455183000
            9	=	disconnect_time	    =	1455183000
            10	=	cld_in	            =	3.36692E+15
            11	=	cli_in	            =	264538165
            12	=	prefix	            =	external_translation_error
            13	=	price_1	            =	0
            14	=	price_n	            =	0
            15	=	interval_1	        =	0
            16	=	interval_n	        =	1
            17	=	post_call_surcharge	=	0
            18	=	connect_fee	        =	0
            19	=	free_seconds	    =	0
            20	=	remote_ip	        =	61.246.45.228
            21	=	grace_period    	=	0
            22	=	user_agent      	=
            23	=	pdd1xx	            =	0
            24	=	i_protocol	        =	1
            25	=	release_source  	=
            26	=	plan_duration	    =	0
            27	=	accessibility_cost	=	0
            28	=	lrn_cld	            =	None
            29	=	lrn_cld_in	        =	None
            30	=	area_name	        =	None
            31	=	p_asserted_id	    =	None
            32	=	remote_party_id	    =	None
             * ==================================================
             **/


            $cdr_row['i_cdr']               = $col_vals[0];
            $cdr_row['i_call']              = $col_vals[1];
            $cdr_row['i_account']           = $col_vals[2];     // Account Number
            $cdr_row['result']              = $col_vals[3];
            $cdr_row['cost']                = $col_vals[4];
            $cdr_row['delay']               = $col_vals[5];
            $cdr_row['duration']            = $col_vals[6];
            $cdr_row['billed_duration']     = $col_vals[7];
            $cdr_row['connect_time']        = $col_vals[8];
            $cdr_row['disconnect_time']     = $col_vals[9];
            $cdr_row['cld_in']              = $col_vals[10];
            $cdr_row['cli_in']              = $col_vals[11];
            $cdr_row['prefix']              = $col_vals[12];
            $cdr_row['price_1']             = $col_vals[13];
            $cdr_row['price_n']             = $col_vals[14];
            $cdr_row['interval_1']          = $col_vals[15];
            $cdr_row['interval_n']          = $col_vals[16];
            $cdr_row['post_call_surcharge'] = $col_vals[17];
            $cdr_row['connect_fee']         = $col_vals[18];
            $cdr_row['free_seconds']        = $col_vals[19];
            $cdr_row['remote_ip']           = $col_vals[20];    // Remote IP
            $cdr_row['grace_period']        = $col_vals[21];
            $cdr_row['user_agent']          = $col_vals[22];
            $cdr_row['pdd1xx']              = $col_vals[23];
            $cdr_row['i_protocol']          = $col_vals[24];
            $cdr_row['release_source']      = $col_vals[25];
            $cdr_row['plan_duration']       = $col_vals[26];
            $cdr_row['accessibility_cost']  = $col_vals[27];
            $cdr_row['lrn_cld']             = $col_vals[28];
            $cdr_row['lrn_cld_in']          = $col_vals[29];
            $cdr_row['area_name']           = $col_vals[30];
            $cdr_row['p_asserted_id']       = $col_vals[31];
            $cdr_row['remote_party_id']     = $col_vals[32];

            $cdr_array[] = $cdr_row;
            unset($cdr_row);

        }

        return ["return_var"=>$return_var,"output" => $cdr_array ];
    }

    /** Decode Vendor cdr file and return array
     * @param $sippy_file
     * @return array
     */
    public static function get_vendor_file_content($sippy_file) {

        $sippy_decoder = getenv("SIPPY_CSVDECODER"); // Sippy decoder command
        exec($sippy_decoder . " vendor " . $sippy_file ,$output,$return_var);
        Log::info($sippy_decoder . " vendor " . $sippy_file );

        $cdr_array = [];
        foreach($output as $op_row){

            $col_vals = explode(",",$op_row);

            $cdr_row = array();
            /**
             *
            We need following detail for vendor cdr

            Account - i_account_debug (only the ID of account can be provided instead of the account's username value)
            connect_time - connect_time
            disconnect_time - disconnect_time
            call_duration - duration
            billed_duration - billed_duration
            cli - cli_out
            cld - cld_out
            selling_cost - # is provided only in the cdrs table
            buying_cost - cost
            remote_ip - remote_ip

            ==================================================
            0	=	i_cdrs_connection           = 1559871575
            1	=	i_call                      = 1008178101
            2	=	i_connection                = 832
            3	=	result                      = 480
            4	=	cost                        = 0
            5	=	delay                       = 0
            6	=	duration                    = 0
            7	=	billed_duration             = 0
            8	=	setup_time                  = 1455775800
            9	=	connect_time                = 1455775800
            10	=	disconnect_time             = 1455775800
            11	=	cld_out                     = 101923013216748
            12	=	cli_out                     = zakirullah612
            13	=	prefix                      = 9230
            14	=	price_1                     = 0.0105
            15	=	price_n                     = 0.0105
            16	=	interval_1                  = 1
            17	=	interval_n                  = 1
            18	=	post_call_surcharge         = 0
            19	=	connect_fee                 = 0
            20	=	free_seconds                = 0
            21	=	grace_period                = 0
            22	=	user_agent                  = VOS3000 V2.1.3.2
            23	=	pdd100                      = 0.042545173
            24	=	pdd1xx                      = 0.207991183
            25	=	i_account_debug             = 43
            26	=	i_protocol                  = 1
            27	=	release_source              = callee
            28	=	call_setup_time             = 1455775799
            29	=	lrn_cld                     = None
            30	=	area_name                   = None
            31	=	i_media_relay               = NullInt64(v=8)
            32	=	remote_ip                   = NullString(s=u'167.114.11.15')
            33	=	vendor_name                 = NullString(s=u'GlobalTelecom-Buy')
             * ==================================================
             **/


            $cdr_row['i_cdrs_connection']			=	$col_vals[0];
            $cdr_row['i_call']						=	$col_vals[1];
            $cdr_row['i_connection']				=	$col_vals[2];
            $cdr_row['result']						=	$col_vals[3];
            $cdr_row['cost']						=	$col_vals[4];
            $cdr_row['delay']						=	$col_vals[5];
            $cdr_row['duration']					=	$col_vals[6];
            $cdr_row['billed_duration']				=	$col_vals[7];
            $cdr_row['setup_time']					=	$col_vals[8];
            $cdr_row['connect_time']				=	$col_vals[9];
            $cdr_row['disconnect_time']				=	$col_vals[10];
            $cdr_row['cld_out']						=	$col_vals[11];
            $cdr_row['cli_out']						=	$col_vals[12];
            $cdr_row['prefix']						=	$col_vals[13];
            $cdr_row['price_1']						=	$col_vals[14];
            $cdr_row['price_n']						=	$col_vals[15];
            $cdr_row['interval_1']					=	$col_vals[16];
            $cdr_row['interval_n']					=	$col_vals[17];
            $cdr_row['post_call_surcharge']			=	$col_vals[18];
            $cdr_row['connect_fee']					=	$col_vals[19];
            $cdr_row['free_seconds']				=	$col_vals[20];
            $cdr_row['grace_period']				=	$col_vals[21];
            $cdr_row['user_agent']					=	$col_vals[22];
            $cdr_row['pdd100']						=	$col_vals[23];
            $cdr_row['pdd1xx']						=	$col_vals[24];
            $cdr_row['i_account_debug']				=	$col_vals[25];
            $cdr_row['i_protocol']					=	$col_vals[26];
            $cdr_row['release_source']				=	$col_vals[27];
            $cdr_row['call_setup_time']				=	$col_vals[28];
            $cdr_row['lrn_cld']						=	$col_vals[29];
            $cdr_row['area_name']					=	$col_vals[30];
            $cdr_row['i_media_relay']				=	$col_vals[31];
            $cdr_row['remote_ip']					=	$col_vals[32];
            $cdr_row['vendor_name']					=	$col_vals[33];


            $cdr_array[] = $cdr_row;
            unset($cdr_row);

        }

        return ["return_var"=>$return_var,"output" => $cdr_array ];
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