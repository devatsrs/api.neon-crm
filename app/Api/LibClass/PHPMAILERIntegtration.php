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
		Config::set('mail.from.address',$config->EmailFrom);
		Config::set('mail.from.name',$config->CompanyName);
		Config::set('mail.encryption',($config->IsSSL==1?'SSL':'TLS'));
		Config::set('mail.username',$config->SMTPUsername);
		Config::set('mail.password',$config->SMTPPassword);
		extract(Config::get('mail'));
	
		$mail = new \PHPMailer;
		//$mail->SMTPDebug = 3;                               // Enable verbose debug output
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
		}
	
		$mail->From = $from['address'];
		$mail->FromName = $from['name'];
		$mail->IsHTML(true);		
		return $mail;		
	}	 
	
	public static function SendMail($view,$data,$config,$companyID='',$body)
	{
		if(empty($companyID)){
			 $companyID = User::get_companyID();
		}
		
		 $mail 		=   self::SetEmailConfiguration($config,$companyID,$data);
		 $status 	= 	array('status' => 0, 'message' => 'Something wrong with sending mail.');
	
		if(getenv('APP_ENV') != 'Production'){
			$data['Subject'] = 'Test Mail '.$data['Subject'];
		}
		$mail =  self::add_email_address($mail,$data,'EmailTo');
		$mail =  self::add_email_address($mail,$data,'cc');
		$mail =  self::add_email_address($mail,$data,'bcc');
		
		if(SiteIntegration::CheckIntegrationConfiguration(false,SiteIntegration::$imapSlug))
		{
			$ImapData =  SiteIntegration::CheckIntegrationConfiguration(true,SiteIntegration::$imapSlug);
			
			$mail->AddReplyTo($ImapData->EmailTrackingEmail, $ImapData->EmailTrackingName);
		}
		
		$message_id		  =  "<".md5(time().$config->EmailFrom) . '@'.$_SERVER['SERVER_NAME'].">";
        $mail->MessageID  =  $message_id;
		
		 
		if(isset($data['AttachmentPaths']) && count($data['AttachmentPaths'])>0) {
        foreach($data['AttachmentPaths'] as $attachment_data) { 
            $file = \Webpatser\Uuid\Uuid::generate()."_". basename($attachment_data['filepath']); 
            $Attachmenturl = \App\AmazonS3::unSignedUrl($attachment_data['filepath']);
            file_put_contents($file,file_get_contents($Attachmenturl));
            $mail->AddAttachment($file,$attachment_data['filename']);
        }
    } 
		$mail->Body = $body;
		$mail->Subject = $data['Subject'];
		if(!is_array($data['EmailTo']) && strpos($data['EmailTo'],',') !== false){
			$data['EmailTo']  = explode(',',$data['EmailTo']);
		}
	
		if(isset($data['cc'])) {
			if (is_array($data['cc'])) {
				foreach ($data['cc'] as $cc_address) {
					$user_data = User::where(["EmailAddress" => $cc_address])->get();
					$mail->AddCC($cc_address, $user_data[0]['FirstName'] . ' ' . $user_data[0]['LastName']);
				}
			}
		}
	
		if(isset($data['cc'])) {
			if (is_array($data['bcc'])) {
				foreach ($data['bcc'] as $bcc_address) {
					$user_data = User::where(["EmailAddress" => $bcc_address])->get();
	
					$mail->AddBCC($bcc_address, $user_data[0]['FirstName'] . ' ' . $user_data[0]['LastName']);
				}
			}
		}
		if(is_array($data['EmailTo'])){
			foreach((array)$data['EmailTo'] as $email_address){
				if(!empty($email_address)) {
					$email_address = trim($email_address);
					//$mail->clearAllRecipients();
					$mail->addAddress($email_address); //trim Added by Abubakar					
				}
			}
			if (!$mail->send()) {
					$status['status'] = 0;
					$status['message'] .= $mail->ErrorInfo . ' ( Email Addresses: ' . implode(",",$data['EmailTo']) . ')';
			} else {
					$status['status'] = 1;
					$status['message'] = 'Email has been sent';
					$status['body'] = $body;
					$status['message_id']	=	$mail->getLastMessageID(); 
			}
		}else{
			if(!empty($data['EmailTo'])) {
				$email_address = trim($data['EmailTo']);
				$mail->clearAllRecipients();
				$mail->addAddress($email_address); //trim Added by Abubakar
				if (!$mail->send()) {
					$status['status'] = 0;
					$status['message'] .= $mail->ErrorInfo . ' ( Email Address: ' . $data['EmailTo'] . ')';
				} else {
					$status['status'] = 1;
					$status['message'] = 'Email has been sent';
					$status['body'] = $body;
					$status['message_id']	=	$mail->getLastMessageID(); 
				}
			}
		} 
		return $status;
	}
	
	static function add_email_address($mail,$data,$type='EmailTo') //type add,bcc,cc
	{
		if(isset($data[$type]))
		{
			if(!is_array($data[$type])){
				$email_addresses = explode(",",$data[$type]);
			}
			else{
				$email_addresses = $data[$type];
			}
	
			if(count($email_addresses)>0){
				foreach($email_addresses as $email_address){
					if($type='EmailTo'){
						$mail->addAddress(trim($email_address));
					}
					if($type='cc'){
						$mail->AddCC(trim($email_address));
					}
					if($type='bcc'){
						$mail->AddBCC(trim($email_address));
					}
				}
			}
		}
		return $mail;
	}
}
?>