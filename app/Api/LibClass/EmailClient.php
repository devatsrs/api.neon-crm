<?php
/**
 * Created by PhpStorm.
 * User: vishal
 * Date: 19-05-18
 * Time: 12:50 PM
 */

namespace App;
use Illuminate\Support\Facades\Log;
use Webklex\IMAP\Client;

class EmailClient extends Client
{
    public $host;
    public $port;
    public $username;
    public $password;
    public $IsSSL=false;
    public $encryption='tls';
    public $validate_cert=false;

    public function __construct($data = array()){
        parent::__construct();
        if($this->IsSSL){
            $this->encryption='ssl';
            $this->validate_cert=true;
        }
    }
    function connectClientEmail($CompanyID)
    {
        $inboxSetting = AutoImportInboxSetting::select('host','port','IsSSL','username','password')->where('CompanyID','=',$CompanyID)->get();
        $oClient = new Client([
            'host' => $inboxSetting[0]->host,
            'port' => $inboxSetting[0]->port,
            'IsSSL' => $inboxSetting[0]->IsSSL==1?'ssl':'tls',
            'validate_cert' => $inboxSetting[0]->IsSSL==1? 'true':'false',
            'username' => $inboxSetting[0]->username,
            'password' => $inboxSetting[0]->password,
        ]);

        //Connect to the IMAP Server
        $oClient->connect();
        return  $oClient;

    }

    function getEmailFolder($oClient){
        //Get all Mailboxes

        /** @var \Webklex\IMAP\Support\FolderCollection $aFolder */
        return $aFolder = $oClient->getFolders();
    }

}