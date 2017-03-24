<?php 

namespace App;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Api\Model\Company;
use Api\Model\User;
use \App\SiteIntegration;

class PHPMAILERIntegtration{ 

	public function __construct(){
	 } 


	public static function SetEmailConfiguration($config,$companyID,$data)
	{    
		Config::set('mail.host',$config->SMTPServer);
		Config::set('mail.port',$config->Port);
		if(isset($data['EmailFrom'])){ 
			Config::set('mail.from.address',$data['EmailFrom']);
		}else{ 
			Config::set('mail.from.address',$config->EmailFrom);
		}
		
		if(isset($data['CompanyName'])){
			Config::set('mail.from.name',$data['CompanyName']);
		}else{
			Config::set('mail.from.name',$config->CompanyName);
		}
		Config::set('mail.from.name',$config->CompanyName);
		Config::set('mail.encryption',($config->IsSSL==1?'ssl':'tls'));
		Config::set('mail.username',$config->SMTPUsername);
		Config::set('mail.password',$config->SMTPPassword);
		extract(Config::get('mail'));
	
		$mail = new \PHPMailer;
		//$mail->SMTPDebug = 0;                               // Enable verbose debug output
		//$mail->SMTPDebug = 1;
		$mail->isSMTP();                                      // Set mailer to use SMTP
		$mail->Host = $host;  // Specify main and backup SMTP servers
		$mail->SMTPAuth = true;                               // Enable SMTP authentication
		$mail->Username = $username;                 // SMTP username
		$mail->CharSet = 'UTF-8';
		$mail->Password = $password;                           // SMTP password
		$mail->SMTPSecure = $encryption;                            // Enable TLS encryption, `ssl` also accepted
		$mail->Port = $port;                                    // TCP port to connect to
		if(isset($data['In-Reply-To']))
		{
			$mail->addCustomHeader('In-Reply-To', $data['In-Reply-To']); 
			//$mail->AddReplyTo($data['In-Reply-To'],  $from['name']);
		}
		if(isset($data['AddReplyTo']))
		{
			$mail->AddReplyTo($data['AddReplyTo'],  $from['name']);
		}
		
		$mail->SetFrom($from['address'], $from['name']);
		//$mail->From = $from['address'];
	//	$mail->FromName = $from['name'];
		$mail->IsHTML(true); 
		return $mail;		
	}	 
	
	public static function SendMail($view,$data,$config,$companyID='',$body)
	{ 	 
		if(empty($companyID)){
			 $companyID = User::get_companyID();
		}
		
		if(isset($data['CompanyName']) && !empty($data['CompanyName']))
		{ 
			$FromName 		= $data['CompanyName'];
		}else{ 
			$FromName		= $config->CompanyName;
		}		
		 $config->CompanyName = $FromName;
		
		 $mail 		=   self::SetEmailConfiguration($config,$companyID,$data);
		 $status 	= 	array('status' => 0, 'message' => 'Something wrong with sending mail.');
	
		if(getenv('APP_ENV') != 'Production'){
			$data['Subject'] = 'Test Mail '.$data['Subject'];
		}
		
		$mail->Body = $body;
		$mail->Subject = $data['Subject'];
		if(!is_array($data['EmailTo']) && strpos($data['EmailTo'],',') !== false){
			$data['EmailTo']  = explode(',',$data['EmailTo']);
		}
		$mail->clearAllRecipients();
		
		$mail =  self::add_email_address($mail,$data,'EmailTo'); 
		$mail =  self::add_email_address($mail,$data,'cc');
		$mail =  self::add_email_address($mail,$data,'bcc');
		
		if(SiteIntegration::CheckIntegrationConfiguration(false,SiteIntegration::$imapSlug))
		{
			$ImapData =  SiteIntegration::CheckIntegrationConfiguration(true,SiteIntegration::$imapSlug);
			
			$mail->AddReplyTo($ImapData->EmailTrackingEmail, $FromName);
		}
		
		$message_id		  =  "<".md5(time().$config->EmailFrom) . '@'.$_SERVER['SERVER_NAME'].">";
        $mail->MessageID  =  $message_id;
		 	
	if(isset($data['AttachmentPaths']) && !empty($data['AttachmentPaths'])) {
        foreach($data['AttachmentPaths'] as $attachment_data) { 
            $file = \Webpatser\Uuid\Uuid::generate()."_". basename($attachment_data['filepath']); 
            $Attachmenturl = \App\AmazonS3::unSignedUrl($attachment_data['filepath']);
            file_put_contents($file,file_get_contents($Attachmenturl));
            $mail->AddAttachment($file,$attachment_data['filename']);
        }
    } 

		$emailto = is_array($data['EmailTo'])?implode(",",$data['EmailTo']):$data['EmailTo'];
		if (!$mail->send()) {
					$status['status'] = 0;
					$status['message'] .= $mail->ErrorInfo . ' ( Email Address: ' . $emailto . ')';
		} else {
					$mail->clearAllRecipients();
					$status['status'] = 1;
					$status['message'] = 'Email has been sent';
					$status['body'] = $body;
					$status['message_id']	=	$mail->getLastMessageID(); 
		} Log::info(print_r($mail,true));
		return $status;
	}
	
	static function add_email_address($mail,$data,$type) //type add,bcc,cc
	{
		if(isset($data[$type]))
		{
			if(!is_array($data[$type])){
				$email_addresses = explode(",",$data[$type]);
			}
			else{
				$email_addresses = $data[$type];
			}
			if(count($email_addresses)>0 && !empty($email_addresses)){
				foreach($email_addresses as $email_address){
					if(!empty($email_address)){					
						if($type=='EmailTo'){ 
							$mail->addAddress(trim($email_address));
						}
						if($type=='cc'){ 
							$mail->AddCC(trim($email_address));
						}
						if($type=='bcc'){
							$mail->AddBCC(trim($email_address));
						}
					}
				}
			}
		}
		return $mail;
	}
}
?>