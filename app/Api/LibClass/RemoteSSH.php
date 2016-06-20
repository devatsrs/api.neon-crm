<?php
namespace App;

use Api\Model\CompanyConfiguration;
use Collective\Remote\RemoteFacade;
use \Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
class RemoteSSH{
    private static $config = array();
    public static $uploadPath = '';

    public function __construct(){
        $Configuration = CompanyConfiguration::getConfiguration();
        if(!empty($Configuration)){
            self::$config = json_decode($Configuration['SSH'],true);
            self::$uploadPath = $Configuration['UPLOADPATH'];
        }
        if(count(self::$config) && isset(self::$config['host']) && isset(self::$config['username']) && isset(self::$config['password'])){
            Config::set('remote.connections.production',self::$config);
        }
    }

    public static function downloadFile($addparams=array()){
        $status = ['status'=>0,'message'=>'SSH is not configured','filePath'=>''];
        if(count(self::$config) && isset(self::$config['host']) && isset(self::$config['username']) && isset(self::$config['password'])){
            if(isset($addparams['filename']) && !empty($addparams['filename'])) {
                $source = $addparams['filename'];
                if (!empty(self::$uploadPath)) {
                    $source = rtrim(self::$uploadPath, '/') . '/' . $addparams['filename'];
                }
                try {
                    if (isset($addparams['downloadPath']) && !empty($addparams['downloadPath'])) {
                        $destination = rtrim($addparams['downloadPath'], '/') . '/' . $addparams['filename'];
                        RemoteFacade::get($source, $destination);
                        $status['status'] = 1;
                        $status['message'] = 'File downloaded to '.$destination;
                        $status['filePath'] = $destination;
                    }

                    if (isset($addparams['downloadTempPath']) && !empty($addparams['downloadTempPath'])) {
                        $destination = rtrim($addparams['downloadTempPath'], '/') . '/' . $addparams['filename'];
                        RemoteFacade::get($source, $destination);
                        $status['status'] = 1;
                        $status['message'] = 'File downloaded to '.$destination;
                        $status['filePath'] = $destination;
                    }
                }catch (Exception $ex){
                    Log::info($ex);
                    $status['message'] = $ex->getMessage();
                }
            }else{
                $status['message'] = 'File path is empty';
            }
        }
        return $status;
    }
}