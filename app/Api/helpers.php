<?php

function check_date_format_db($date){
    $datefomated = date('Y-m-d H:i:s',strtotime($date));
    if(date('Y', strtotime($datefomated)) == '1970'){
        throw new Exception('Invalid Date Format!!');
    }
    return $datefomated;
}
function uploaded_File_Handler($fileArray){
    $files_array = [];
    foreach($fileArray as $Index=>$file){  
        $decoded_file = base64_decode($file['file']);
        $fileName = $file['fileName'];
        $tempPath = getenv('TEMP_PATH');
        $stamp = date_timestamp_get(date_create());
        $path = $tempPath.'/'.$stamp.$fileName;
        file_put_contents($path, $decoded_file);
        $files_array[] = ['fileName'=>$file['fileName'],
                            'file'=>$path,
                            'Extension'=>$file['fileExtension']];
    }
    return $files_array;
}
function rename_win($oldfile,$newfile) {
    //if (!rename($oldfile,$newfile)) {
        if (copy ($oldfile,$newfile)) {
            unlink($oldfile);
            return TRUE;
        }
        //return FALSE;
    //}
    return TRUE;
}

function sendMail($view,$data){
    $status = array('status' => 0, 'message' => 'Something wrong with sending mail.');
    if(empty($data['companyID']))
    {
        $companyID = \Api\Model\User::get_companyID();
    }else{
        $companyID = $data['companyID'];
    }
    $mail = setMailConfig($companyID);
    $body = View::make($view,compact('data'))->render();

    if(getenv('APP_ENV') != 'Production'){
        $data['Subject'] = 'Test Mail '.$data['Subject'];
    }
    $mail->Body = $body;
    $mail->Subject = $data['Subject'];
    if(!is_array($data['EmailTo']) && strpos($data['EmailTo'],',') !== false){
        $data['EmailTo']  = explode(',',$data['EmailTo']);
    }
	
	if(isset($data['AttachmentPaths']) && count($data['AttachmentPaths'])>0)
	{
		foreach($data['AttachmentPaths'] as $attachment_data)
		{
			 if(is_amazon() == true)
			{
				$Attachmenturl =  AmazonS3::preSignedUrl($attachment_data['filepath']);
			}
			else
			{
				$Attachmenturl = Config::get('app.upload_path')."/".$attachment_data['filepath'];
			}			
			$mail->AddAttachment($Attachmenturl,$attachment_data['filename']);
		}
	}

    if(is_array($data['EmailTo'])){
        foreach((array)$data['EmailTo'] as $email_address){
            if(!empty($email_address)) {
                $email_address = trim($email_address);
                $mail->clearAllRecipients();
                $mail->addAddress($email_address); //trim Added by Abubakar
                if (!$mail->send()) {
                    $status['status'] = 0;
                    $status['message'] .= $mail->ErrorInfo . ' ( Email Address: ' . $email_address . ')';
                } else {
                    $status['status'] = 1;
                    $status['message'] = 'Email has been sent';
                    $status['body'] = $body;
                }
            }
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
            }
        }
    }
    return $status;
}
function setMailConfig($CompanyID){
    $result = \Api\Model\Company::select('SMTPServer','SMTPUsername','CompanyName','SMTPPassword','Port','IsSSL','EmailFrom')->where("CompanyID", '=', $CompanyID)->first();
    Config::set('mail.host',$result->SMTPServer);
    Config::set('mail.port',$result->Port);
    Config::set('mail.from.address',$result->EmailFrom);
    Config::set('mail.from.name',$result->CompanyName);
    Config::set('mail.encryption',($result->IsSSL==1?'SSL':'TLS'));
    Config::set('mail.username',$result->SMTPUsername);
    Config::set('mail.password',$result->SMTPPassword);
    extract(Config::get('mail'));

    $mail = new PHPMailer;
    //$mail->SMTPDebug = 3;                               // Enable verbose debug output
    $mail->isSMTP();                                      // Set mailer to use SMTP
    $mail->Host = $host;  // Specify main and backup SMTP servers
    $mail->SMTPAuth = true;                               // Enable SMTP authentication
    $mail->Username = $username;                 // SMTP username

    $mail->Password = $password;                           // SMTP password
    $mail->SMTPSecure = $encryption;                            // Enable TLS encryption, `ssl` also accepted

    $mail->Port = $port;                                    // TCP port to connect to

    $mail->From = $from['address'];
    $mail->FromName = $from['name'];
    $mail->isHTML(true);

    return $mail;

}

function email_log($data){
    $status = array('status' => 0, 'message' => 'Something wrong with Saving log.');
    if(!isset($data['EmailTo']) && empty($data['EmailTo'])){
        $status['message'] = 'Email To not set in Account mail log';
        return $status;
    }
    if(!isset($data['AccountID']) && empty($data['AccountID'])){
        $status['message'] = 'AccountID not set in Account mail log';
        return $status;
    }
    if(!isset($data['Subject']) && empty($data['Subject'])){
        $status['message'] = 'Subject not set in Account mail log';
        return $status;
    }
    if(!isset($data['Message']) && empty($data['Message'])){
        $status['message'] = 'Message not set in Account mail log';
        return $status;
    }

    if(is_array($data['EmailTo'])){
        $data['EmailTo'] = implode(',',$data['EmailTo']);
    }

    $logData = ['EmailFrom'=>\Api\Model\User::get_user_email(),
        'EmailTo'=>$data['EmailTo'],
        'Subject'=>$data['Subject'],
        'Message'=>$data['Message'],
        'AccountID'=>$data['AccountID'],
        'CompanyID'=>\Api\Model\User::get_companyID(),
        'UserID'=>\Api\Model\User::get_userID(),
        'CreatedBy'=>\Api\Model\User::get_user_full_name()];
    if(\Api\Model\AccountEmailLog::Create($logData)){
        $status['status'] = 1;
    }
    return $status;
}


function email_log_data($data){
    $status = array('status' => 0, 'message' => 'Something wrong with Saving log.');
    if(!isset($data['EmailTo']) && empty($data['EmailTo'])){
        $status['message'] = 'Email To not set in Account mail log';
        return $status;
    }
    if(!isset($data['AccountID']) && empty($data['AccountID'])){
        $status['message'] = 'AccountID not set in Account mail log';
        return $status;
    }
    if(!isset($data['Subject']) && empty($data['Subject'])){
        $status['message'] = 'Subject not set in Account mail log';
        return $status;
    }
    if(!isset($data['Message']) && empty($data['Message'])){
        $status['message'] = 'Message not set in Account mail log';
        return $status;
    }

    if(is_array($data['EmailTo'])){
        $data['EmailTo'] = implode(',',$data['EmailTo']);
    }
	
	if(!isset($data['cc']) || !is_array($data['cc']))
	{
		$data['cc'] = array();
	}
	
	if(!isset($data['bcc']) || !is_array($data['bcc']))
	{
		$data['bcc'] = array();
	}
	
	if(isset($data['AttachmentPaths']) && count($data['AttachmentPaths'])>0)
	{
			$data['AttachmentPaths'] = serialize($data['AttachmentPaths']);
	}
	else
	{
		$data['AttachmentPaths'] = serialize([]);
	}

    $logData = ['EmailFrom'=>\Api\Model\User::get_user_email(),
        'EmailTo'=>$data['EmailTo'],
        'Subject'=>$data['Subject'],
        'Message'=>$data['Message'],
        'AccountID'=>$data['AccountID'],
        'CompanyID'=>\Api\Model\User::get_companyID(),
        'UserID'=>\Api\Model\User::get_userID(),
        'CreatedBy'=>\Api\Model\User::get_user_full_name(),
		'Cc'=>implode(",",$data['cc']),
		'Bcc'=>implode(",",$data['bcc']),
		"AttachmentPaths"=>$data['AttachmentPaths']
		];
     $data =  \Api\Model\AccountEmailLog::Create($logData);
    return $data;
}

function is_amazon(){
    $AMAZONS3_KEY  = getenv("AMAZONS3_KEY");
    $AMAZONS3_SECRET = getenv("AMAZONS3_SECRET");
    $AWS_REGION = getenv("AWS_REGION");

    if(empty($AMAZONS3_KEY) || empty($AMAZONS3_SECRET) || empty($AWS_REGION) ){
        return false;
    }
    return true;
}

