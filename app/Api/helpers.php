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

    $mandrill =0;
    if(isset($data['mandrill']) && $data['mandrill'] ==1){
        $mandrill = 1;
    }
    $mail = setMailConfig($companyID, $mandrill, $data);

    $mail->isHTML(true);
    if (isset($data['isHTML']) && $data['isHTML'] == 'false') {
        $mail->isHTML(false);
    }

    $body = htmlspecialchars_decode(View::make($view, compact('data'))->render());

    if (getenv('APP_ENV') != 'Production') {
        $data['Subject'] = 'Test Mail ' . $data['Subject'];
    }
    $mail->Body = $body;
    $mail->Subject = $data['Subject'];


    add_email_address($mail,$data,'EmailTo');
    add_email_address($mail,$data,'cc');
    add_email_address($mail,$data,'bcc');

    if(isset($data['AttachmentPaths']) && count($data['AttachmentPaths'])>0)
    {
        foreach($data['AttachmentPaths'] as $attachment_data)
        {
            if(is_amazon() == true)
            {
                $Attachmenturl  =  \App\AmazonS3::preSignedUrl($attachment_data['filepath']);
                $path 			=   getenv('AWS_URL').'/'.$attachment_data['filepath'];
            }
            else
            {
                $Attachmenturl = Config::get('app.upload_path')."/".$attachment_data['filepath'];
                $path 			=   getenv('TEMP_PATH').'/'.$attachment_data['filepath'];
            }


            $file = getenv('TEMP_PATH').'/email_attachment/'.basename($path);
            if(!file_exists(getenv('TEMP_PATH').'/email_attachment')) {
                mkdir(getenv('TEMP_PATH').'/email_attachment',0777);
            }
            file_put_contents($file,file_get_contents($path));
            //\Illuminate\Support\Facades\Log::info($file);
            //\Illuminate\Support\Facades\Log::info($Attachmenturl);
            $mail->AddAttachment($file,$attachment_data['filename']);
        }
    }


    $mail->Body = $body;
    $mail->Subject = $data['Subject'];
    if (!$mail->send()) {
        $status['status'] = 0;
        $status['message'] .= $mail->ErrorInfo;
        $status['body'] = '';
    } else {
        $status['status'] = 1;
        $status['message'] = 'Email has been sent';
        $status['body'] = $body;
    }

    //	\Illuminate\Support\Facades\Log::info($data);
    // \Illuminate\Support\Facades\Log::info($status);
    //  \Illuminate\Support\Facades\Log::info("error start");
    //	\Illuminate\Support\Facades\Log::info($mail->ErrorInfo);
    //	\Illuminate\Support\Facades\Log::info("error end");
    return $status;
}
function setMailConfig($CompanyID,$mandrill,$data=array()){


    $result = \Api\Model\Company::select('SMTPServer','SMTPUsername','CompanyName','SMTPPassword','Port','IsSSL','EmailFrom')->where("CompanyID", '=', $CompanyID)->first();
    if($mandrill == 1) {
        Config::set('mail.host', getenv("MANDRILL_SMTP_SERVER"));
        Config::set('mail.port', getenv("MANDRILL_PORT"));
        Config::set('mail.from.address', $result->EmailFrom);
        Config::set('mail.from.name', $result->CompanyName);
        Config::set('mail.encryption', (getenv("MADRILL_SSL") == 1 ? 'SSL' : 'TLS'));
        Config::set('mail.username', getenv("MANDRILL_SMTP_USERNAME"));
        Config::set('mail.password', getenv("MANDRILL_SMTP_PASSWORD"));
    }else{
        Config::set('mail.host', $result->SMTPServer);
        Config::set('mail.port', $result->Port);
        Config::set('mail.from.address', $result->EmailFrom);
        Config::set('mail.from.name', $result->CompanyName);
        Config::set('mail.encryption', ($result->IsSSL == 1 ? 'SSL' : 'TLS'));
        Config::set('mail.username', $result->SMTPUsername);
        Config::set('mail.password', $result->SMTPPassword);
    }


    extract(Config::get('mail'));

    $mail = new \PHPMailer();
    //$mail->SMTPDebug = 3;                               // Enable verbose debug output
    $mail->isSMTP();                                      // Set mailer to use SMTP
    $mail->Host = $host;  // Specify main and backup SMTP servers
    $mail->SMTPAuth = true;                               // Enable SMTP authentication
    $mail->Username = $username;                 // SMTP username

    $mail->Password = $password;                           // SMTP password
    $mail->SMTPSecure = $encryption;                            // Enable TLS encryption, `ssl` also accepted

    $mail->Port = $port;                                    // TCP port to connect to

    if(isset($data['address'])&& $data['name'] ){
        $mail->From 	= $data['address'];
        $mail->FromName = $data['name'];
    }else{
        $mail->From 	= $from['address'];
        $mail->FromName = $from['name'];
    }
    return $mail;
}

function add_email_address($mail,$data,$type='EmailTo') //type add,bcc,cc
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


function email_log_data($data,$view = ''){
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

    if(!isset($data['cc']))
    {
        $data['cc'] = '';
    }

    if(!isset($data['bcc']))
    {
        $data['bcc'] = '';
    }

    if(isset($data['AttachmentPaths']) && count($data['AttachmentPaths'])>0)
    {
        $data['AttachmentPaths'] = serialize($data['AttachmentPaths']);
    }
    else
    {
        $data['AttachmentPaths'] = serialize([]);
    }

    if($view!='')
    {
        $body = htmlspecialchars_decode(View::make($view, compact('data'))->render());
    }
    else
    {
        $body = $data['Message'];
    }

    $logData = ['EmailFrom'=>\Api\Model\User::get_user_email(),
        'EmailTo'=>$data['EmailTo'],
        'Subject'=>$data['Subject'],
        'Message'=>$body,
        'AccountID'=>$data['AccountID'],
        'CompanyID'=>\Api\Model\User::get_companyID(),
        'UserID'=>\Api\Model\User::get_userID(),
        'CreatedBy'=>\Api\Model\User::get_user_full_name(),
        'Cc'=>$data['cc'],
        'Bcc'=>$data['bcc'],
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

function create_site_configration_cache($request){
    $LicenceKey = $request->only('LicenceKey')['LicenceKey'];
    $domain_url      =   $request->getHttpHost();
    $result       =  \Illuminate\Support\Facades\DB::table('tblCompanyThemes')->where(["DomainUrl" => $domain_url,'ThemeStatus'=>\Api\Model\Themes::ACTIVE])->get();

    if($result){  //url found
        $cache['FavIcon']    = empty($result[0]->Favicon)?\Illuminate\Support\Facades\URL::to('/').'/assets/images/favicon.ico':validfilepath($result[0]->Favicon);
        $cache['Logo']       = empty($result[0]->Logo)?\Illuminate\Support\Facades\URL::to('/').'/assets/images/logo@2x.png':validfilepath($result[0]->Logo);
        $cache['Title']    = $result[0]->Title;
        $cache['FooterText']  = $result[0]->FooterText;
        $cache['FooterUrl']   = $result[0]->FooterUrl;
        $cache['LoginMessage']  = $result[0]->LoginMessage;
        $cache['CustomCss']   = $result[0]->CustomCss;
    }else{
        $cache['FavIcon']    = \Illuminate\Support\Facades\URL::to('/').'/assets/images/favicon.ico';
        $cache['Logo']       = \Illuminate\Support\Facades\URL::to('/').'/assets/images/logo@2x.png';
        $cache['Title']    = 'Neon';
        $cache['FooterText']  = '&copy; '.date('Y').' Code Desk';
        $cache['FooterUrl']   = 'http://www.code-desk.com';
        $cache['LoginMessage']  = 'Dear user, log in to access RM!';
        $cache['CustomCss']   = '';
    }

    $siteConfigretion = 'siteConfiguration' . $LicenceKey;
        if (\Illuminate\Support\Facades\Cache::has($siteConfigretion)) {
            \Illuminate\Support\Facades\Cache::forget($siteConfigretion);
        }
    \Illuminate\Support\Facades\Cache::forever($siteConfigretion, $cache);
}




function validfilepath($path){
    $path = \App\AmazonS3::unSignedUrl($path);
    if (!is_numeric(strpos($path, "https://"))) {
        //$path = str_replace('/', '\\', $path);
        if (copy($path, './uploads/' . basename($path))) {

            $path = \Illuminate\Support\Facades\URL::to('/') . '/uploads/' . basename($path);
        }
    }
    return $path;
}


function getCompanyLogo($request){
    $LicenceKey = $request->only('LicenceKey')['LicenceKey'];
    $siteConfigretion = 'siteConfiguration' . $LicenceKey;
    $cache = Cache::get($siteConfigretion);
    return $cache['Logo'];
}

function call_api($post = array()){

    //$LicenceVerifierURL = 'http://localhost/RMLicenceAPI/branches/master/public/validate_licence';
    $LicenceVerifierURL = 'http://api.licence.neon-soft.com/validate_licence';// //getenv('LICENCE_URL').'validate_licence';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $LicenceVerifierURL);
    curl_setopt($ch, CURLOPT_VERBOSE, '1');
    curl_setopt($ch, CURLOPT_AUTOREFERER, 1);//TRUE to automatically set the Referer: field in requests where it follows a Location: redirect.
    curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);//TRUE to force the connection to explicitly close when it has finished processing, and not be pooled for reuse.
    curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);//TRUE to force the use of a new connection instead of a cached one.


    //turning off the server and peer verification(TrustManager Concept).
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    // curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_DEFAULT);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);

    //NVPRequest for submitting to server
    $nvpreq = "json=" . json_encode($post);

    //$nvpreq = http_build_query($post);

    ////setting the nvpreq as POST FIELD to curl
    curl_setopt($ch, CURLOPT_POSTFIELDS, $nvpreq);

    //getting response from server
    $response = curl_exec($ch);
    Illuminate\Support\Facades\Log::info($response);
    // echo $response;
    return $response;
}

function generateResponse($message,$isError=false,$isCustomError=false,$data=[]){
    $status = 'success';
    if($isError){
        if($isCustomError) {
            $message = ["error" => [$message]];
        }
        $status='failed';
    }
    $reponse_data = ['status' => $status,'message'=>$message];
    if(count($data)>0){
        $reponse_data['data'] = $data;
    }
    return \Dingo\Api\Facade\API::response()->array($reponse_data)->statusCode(200);
}

function getRequestParam($key){
    $request = new \Dingo\Api\Http\Request;
    $param = $request->only($key)[$key];
    return $param;
}

function cleanarray($data = [],$unset=array()){
        $unset = array_merge($unset,['LicenceKey','CompanyName']);
		\Illuminate\Support\Facades\Log::info($unset);
    foreach($unset as $item){
        unset($data[$item]);
    }
    return $data;
}

function SendTaskMail($data){
		$LogginedUser		 = \Api\Model\User::get_userID();
		$LogginedUserName 	 = 	\Api\Model\User::get_user_full_name();
		$AssignedUser 		 = $data['UsersIDs'];
		if($LogginedUser != $AssignedUser){ //if assigned user and logined user are not same then send email		
		
			$AssignedUserData 	 	 =		\Api\Model\User::find($AssignedUser); 			
			$data['EmailTo'] 	 	 = 		$AssignedUserData->EmailAddress;
			$data['cc'] 		 	 = 		"umer.ahmed@code-desk.com";		
			$data['Subject_task'] 	 = 		$data['Subject'];					
			$data['Subject']  	 	 = 		"(Neon) ".$data['Subject'];			
			$data['TitleHeading'] 	 = 		$LogginedUserName." <strong>Assigned</strong> you a Task";
			$status 			 	 = 		sendMail('emails.task.TaskEmailSend', $data);								
		}
	}
	
function SendTaskMailUpdate($NewData,$OldData,$type='Task'){
		$LogginedUser 		= 	\Api\Model\User::get_userID();		
		$LogginedUserName 	= 	\Api\Model\User::get_user_full_name();
		
		//Tagged Users Email
		if($NewData['TaggedUsers']!=''){
			$TaggedUsersNew 		= 	explode(",",$NewData['TaggedUsers']);
			$TaggedUsersOld 		= 	explode(",",$OldData['TaggedUsers']);
			$TaggedUsersDiff		= 	array_diff($TaggedUsersNew, $TaggedUsersOld);		
			$TaggedUsersDiffEmail 	= 	array();
			if(count($TaggedUsersDiff)>0){
				foreach($TaggedUsersDiff as $TaggedUsersDiffData){
					if($LogginedUser!=$TaggedUsersDiffData){
						$TaggedUserData 	 	 	 =		\Api\Model\User::find($TaggedUsersDiffData);
						$TaggedUsersDiffEmail[]		 =		$TaggedUserData->EmailAddress;
					}
				}			 			
				$NewData['EmailTo'] 	 	 = 		$TaggedUsersDiffEmail;
				$NewData['cc'] 		 	 	 = 		"umer.ahmed@code-desk.com";	
				if($type=='Opportunity'){	
					$NewData['Subject_task'] 	 = 		$NewData['OpportunityName'];		
					$NewData['Subject']  	 	 = 		"(Neon) ".$NewData['OpportunityName'];
					$NewData['Description']  	 = 		"";				
				}else if($type=='Task'){
					$NewData['Subject_task'] 	 = 		$NewData['Subject'];		
					$NewData['Subject']  	 	 = 		"(Neon) ".$NewData['Subject'];
				}
				$NewData['CreatedBy']  	 	 = 		$OldData['CreatedBy'];		
				if($type=='Opportunity'){
					$NewData['TitleHeading']	 = 		$LogginedUserName." <strong>Tagged</strong> you in an ".$type;
				}else{
					$NewData['TitleHeading']	 = 		$LogginedUserName." <strong>Tagged</strong> you in a ".$type;
				}
				$status 			 		 = 		sendMail('emails.task.TaskEmailSend', $NewData);								
			}
		}
		
		if($type=='Task'){
			//Assign Users Email
			if($OldData['UsersIDs']!=$NewData['UsersIDs']){ // new and old assigned user are not same				
				if($LogginedUser!=$NewData['UsersIDs']){ //new user and logined user are not same			
					
					$AssignedUserData 	 	 	 =		\Api\Model\User::find($NewData['UsersIDs']); 			
					$NewData['EmailTo'] 	 	 = 		$AssignedUserData->EmailAddress;
					$NewData['cc'] 		 	 	 = 		"umer.ahmed@code-desk.com";		
					$NewData['Subject_task'] 	 = 		$NewData['Subject'];		
					$NewData['Subject']  	 	 = 		"(Neon) ".$NewData['Subject'];
					$NewData['CreatedBy']  	 	 = 		$OldData['CreatedBy'];		
					$NewData['TitleHeading']	 = 		$LogginedUserName." <strong>Assigned</strong> you a ".$type;
					$status 			 		 = 		sendMail('emails.task.TaskEmailSend', $NewData);							
				}
			}
		}
	}
