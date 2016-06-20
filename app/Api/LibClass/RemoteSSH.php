<?php
namespace App;

use Api\Model\CompanyConfiguration;
use Collective\Remote\RemoteFacade;
use \Exception;
use Faker\Provider\Uuid;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
class RemoteSSH{
    private static $config = array();
    public static $uploadPath = '';

    public static function setConfig(){
        $Configuration = CompanyConfiguration::getConfiguration();
        if(!empty($Configuration)){
            self::$config = json_decode($Configuration['SSH'],true);
            self::$uploadPath = $Configuration['UPLOADPATH'];
        }
        if(count(self::$config) && isset(self::$config['host']) && isset(self::$config['username']) && isset(self::$config['password'])){
            Config::set('remote.connections.production',self::$config);
        }
    }

    public static function downloadFile($key){
        $status = ['status'=>0,'message'=>'SSH is not configured','filePath'=>''];
        self::setConfig();
        if(count(self::$config) && isset(self::$config['host']) && isset(self::$config['username']) && isset(self::$config['password'])){
            if(isset($addparams['filename']) && !empty($addparams['filename'])) {
                $source = $key;
                if (!empty(self::$uploadPath)) {
                    $source = rtrim(self::$uploadPath, '/') . '/' . $addparams['filename'];
                }
                try {
                    $tempPath = getenv('TEMP_PATH');
                    $destination = rtrim($tempPath, '/') . '/' . Uuid::uuid() . basename($key);
                    RemoteFacade::get($source, $destination);
                    $status['status'] = 1;
                    $status['message'] = 'File downloaded to '.$destination;
                    $status['filePath'] = $destination;
                }catch (Exception $ex){
                    Log::info($ex);
                    $status['message'] = $ex->getMessage();
                }
            }else{
                $status['message'] = 'File path is empty';
                Log::info($status['message']);
            }
        }
        return $status;
    }
}