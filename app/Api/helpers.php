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
        $tempPath = \Api\Model\CompanyConfiguration::get("TEMP_PATH");
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


function sendMail($view,$data,$ViewType=1)
{
		
	if(empty($data['companyID']))
    {
        $companyID = \Api\Model\User::get_companyID();
    }else{
        $companyID = $data['companyID'];
    }
	
	if($ViewType){
		$body 	=  html_entity_decode(View::make($view,compact('data'))->render()); 
	}
	else{
		$body  = $view;
	}

	if(\App\SiteIntegration::CheckCategoryConfiguration(false,\App\SiteIntegration::$EmailSlug)){
		$status = 	 \App\SiteIntegration::SendMail($view,$data,$companyID,$body);		
	}
	else{
		$config = \Api\Model\Company::select('SMTPServer','SMTPUsername','CompanyName','SMTPPassword','Port','IsSSL','EmailFrom')->where("CompanyID", '=', $companyID)->first();
		$status = 	 \App\PHPMAILERIntegtration::SendMail($view,$data,$config,$companyID,$body);
	}
   /* $status = array('status' => 0, 'message' => 'Something wrong with sending mail.');
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
    if (!$mail->send()) {
        $status['status'] = 0;
        $status['message'] .= $mail->ErrorInfo;
        $status['body'] = '';
    } else {
        $status['status'] = 1;
        $status['message'] = 'Email has been sent';
        $status['body'] = $body;
    }*/
    return $status;
}
/*
function setMailConfig($CompanyID,$mandrill,$data=array()){


    $result = \Api\Model\Company::select('SMTPServer','SMTPUsername','CompanyName','SMTPPassword','Port','IsSSL','EmailFrom')->where("CompanyID", '=', $CompanyID)->first();

    $smtp = \Api\Model\CompanyConfiguration::get("EXTRA_SMTP");

    if($mandrill == 1 && !empty($smtp)) {

        $host = \Api\Model\CompanyConfiguration::getJsonKey("EXTRA_SMTP","HOST");
        $port = \Api\Model\CompanyConfiguration::getJsonKey("EXTRA_SMTP","PORT");
        $ssl = \Api\Model\CompanyConfiguration::getJsonKey("EXTRA_SMTP","SSL");
        $username = \Api\Model\CompanyConfiguration::getJsonKey("EXTRA_SMTP","USERNAME");
        $password = \Api\Model\CompanyConfiguration::getJsonKey("EXTRA_SMTP","PASSWORD");

        Config::set('mail.host', $host);
        Config::set('mail.port', $port );
        Config::set('mail.from.address', $result->EmailFrom);
        Config::set('mail.from.name', $result->CompanyName);
        Config::set('mail.encryption', ($ssl == 1 ? 'SSL' : 'TLS'));
        Config::set('mail.username', $username);
        Config::set('mail.password', $password);

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
*/
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
	
	if(!isset($data['message_id'])){
		$data['message_id'] = '';
	}

    $logData = ['EmailFrom'=>\Api\Model\User::get_user_email(),
        'EmailTo'=>$data['EmailTo'],
        'Subject'=>$data['Subject'],
        'Message'=>$data['Message'],
        'AccountID'=>$data['AccountID'],
        'CompanyID'=>\Api\Model\User::get_companyID(),
        'UserID'=>\Api\Model\User::get_userID(),
        'CreatedBy'=>\Api\Model\User::get_user_full_name(),
		"MessageID"=>$data['message_id']];
    if(\Api\Model\AccountEmailLog::Create($logData)){
        $status['status'] = 1;
    }
    return $status;
}

function email_log_data_Ticket($data,$view = '',$status){ 
	
	$EmailParent =	 0;
	if(isset($data['TicketID'])){
			//$EmailParent =	\Api\Model\TicketsTable::where(["TicketID"=>$data['TicketID']])->pluck('AccountEmailLogID');
	}
    $status_return = array('status' => 0, 'message' => 'Something wrong with Saving log.');
    if(!isset($data['EmailTo']) && empty($data['EmailTo'])){
        $status_return['message'] = 'Email To not set in Account mail log';
        return $status_return;
    }
    
    if(!isset($data['Subject']) && empty($data['Subject'])){
        $status_return['message'] = 'Subject not set in Account mail log';
        return $status_return;
    }
    if(!isset($data['Message']) && empty($data['Message'])){
        $status_return['message'] = 'Message not set in Account mail log';
        return $status_return;
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
        $data['AttachmentPaths'] = 'a:0:{}';
    }

    if($view!='')
    {
        $body = htmlspecialchars_decode(View::make($view, compact('data'))->render());
    }
    else
    {
        $body = $data['Message'];
    } 
	if(!isset($status['message_id']))
	{
		$status['message_id'] = '';
	} 
	if(!isset($data['EmailCall']))
	{
		$data['EmailCall'] = \Api\Model\Messages::Sent;
	}

	if(isset($data['EmailFrom']))
	{
		$data['EmailFrom'] = $data['EmailFrom'];
	}else{
		$data['EmailFrom'] = \Api\Model\User::get_user_email();
	}
	if(!isset($data['TicketID'])){ 
		$data['TicketID']  = 0;
	}
	
    $logData = ['EmailFrom'=>$data['EmailFrom'],
        'EmailTo'=>$data['EmailTo'],
        'Subject'=>$data['Subject'],
        'Message'=>$body,
        'CompanyID'=>\Api\Model\User::get_companyID(),
        'UserID'=>\Api\Model\User::get_userID(),
        'CreatedBy'=>\Api\Model\User::get_user_full_name(),
		"created_at"=>date("Y-m-d H:i:s"),
        'Cc'=>$data['cc'],
        'Bcc'=>$data['bcc'],
        "AttachmentPaths"=>$data['AttachmentPaths'],
		"MessageID"=>$status['message_id'],
		"EmailParent"=>isset($data['EmailParent'])?$data['EmailParent']:$EmailParent,
		"EmailCall"=>$data['EmailCall'],
		"TicketID"=>$data['TicketID'],
		"EmailType"=>\Api\Model\AccountEmailLog::TicketEmail 
    ];
	
    $data =  \Api\Model\AccountEmailLog::insertGetId($logData);
    return $data;
}

function email_log_data($data,$view = ''){
    $status = array('status' => 0, 'message' => 'Something wrong with Saving log.');
    if(!isset($data['EmailTo']) && empty($data['EmailTo'])){
        $status['message'] = 'Email To not set in Account mail log';
        return $status;
    }
   /* if(!isset($data['AccountID']) && empty($data['AccountID'])){
        $status['message'] = 'AccountID not set in Account mail log';
        return $status;
    }*/
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
	if(!isset($data['message_id']))
	{
		$data['message_id'] = '';
	}
	if(!isset($data['EmailCall']))
	{
		$data['EmailCall'] = \Api\Model\Messages::Sent;
	}

	if(isset($data['EmailFrom']))
	{
		$data['EmailFrom'] = $data['EmailFrom'];
	}else{
		$data['EmailFrom'] = \Api\Model\User::get_user_email();
	}
	
    $logData = ['EmailFrom'=>$data['EmailFrom'],
        'EmailTo'=>$data['EmailTo'],
        'Subject'=>$data['Subject'],
        'Message'=>$body,
        'AccountID'=>isset($data['AccountID'])?$data['AccountID']:0,
		'ContactID'=>isset($data['ContactID'])?$data['ContactID']:0,
		"UserType"=>isset($data['usertype'])?$data['usertype']:0,
        'CompanyID'=>\Api\Model\User::get_companyID(),
        'UserID'=>\Api\Model\User::get_userID(),
        'CreatedBy'=>\Api\Model\User::get_user_full_name(),
        'Cc'=>$data['cc'],
        'Bcc'=>$data['bcc'],
        "AttachmentPaths"=>$data['AttachmentPaths'],
		"MessageID"=>$data['message_id'],
		"EmailParent"=>isset($data['EmailParent'])?$data['EmailParent']:0,
		"EmailCall"=>$data['EmailCall'],
    ];
	
    $data =  \Api\Model\AccountEmailLog::Create($logData);
    return $data;
}

/** Store logo in cache
 * @param $request
 * @return mixed
 */
function site_configration_cache($request){

    $CACHE_EXPIRE = \Api\Model\CompanyConfiguration::get("CACHE_EXPIRE");
    $time = empty($CACHE_EXPIRE)?60:$CACHE_EXPIRE;
    $minutes = \Carbon\Carbon::now()->addMinutes($time);
    $LicenceKey = $request->only('LicenceKey')['LicenceKey'];
    $CompanyName = $request->only('CompanyName')['CompanyName'];
    $siteConfigretion = 'siteConfiguration' . $LicenceKey.$CompanyName;

    if (!Cache::has($siteConfigretion)) {

        $domain_url      =   $request->getHttpHost();
        $result       =  \Illuminate\Support\Facades\DB::table('tblCompanyThemes')->where(["DomainUrl" => $domain_url,'ThemeStatus'=>\Api\Model\Themes::ACTIVE])->first();

        if(!empty($result)){
            if(!empty($result->Logo)){

                $cache['Logo']       = (!empty($result->Logo))?$result->Logo:"";
            }
        }

        $cache['DefaultLogo']       = \Api\Model\CompanyConfiguration::get("WEB_URL").'/assets/images/logo@2x.png';


        \Illuminate\Support\Facades\Cache::add($siteConfigretion, $cache, $minutes);
    }
    $cache = Cache::get($siteConfigretion);
    return $cache;

}





/** Send logo url
 * @param $request
 * @return string
 */
function getCompanyLogo($request){

    $cache = site_configration_cache($request);

    if(isset($cache['Logo']) && !empty($cache['Logo'])){

        $logo_url = \App\AmazonS3::unSignedImageUrl($cache["Logo"]);

    }else {

        // if no logo and amazon then use from site url even if amazon is set or not.
        $DefaultLogo = $cache['DefaultLogo'];
        $site_url = \Api\Model\CompanyConfiguration::get("WEB_URL");

        $logo_url = combile_url_path($site_url,$DefaultLogo);

    }

    return $logo_url;
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
    \Illuminate\Support\Facades\Log::info("Licencing request... ");
    \Illuminate\Support\Facades\Log::info($nvpreq);
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

function cleanarray($data = [],$unset=[]){
    $unset[]= 'LicenceKey';
    $unset[]= 'CompanyName';
	$unset[]= 'LoginType';
    foreach($unset as $item){
        unset($data[$item]);
    }
    return $data;
}

function SendTicketEmail($Type='store',$id,$data = array()){
	
	$LogginedUser   	 = 		\Api\Model\User::get_userID();
    $LogginedUserName    =  	\Api\Model\User::get_user_full_name();
	$LogginedUserEmail	 =		\Api\Model\User::get_user_email();
    //$AssignedUser    	 = 		$data['UsersIDs'];
	
	if($Type=='store')
	{
		if(isset($data['Requester']) && $data['Requester']!=$LogginedUserEmail)
		{
			$EmailData['EmailTo']     	  =   $data['Requester'];
			$EmailData['Subject']   	  =   'Ticket Created. "'.$data['Subject'].'"';
			$EmailData['TicketSubject']   =   "(Neon) ".$data['Subject'];
			$EmailData['AttachmentPaths'] =   unserialize($data['AttachmentPaths']);
			$EmailData['TitleHeading']    =   $LogginedUserName." <strong>created</strong> a ticket for you";
			
			if(isset($data['email_from']) && !empty($data['email_from'])){
				$EmailData['EmailFrom']       =   $data['email_from'];
				$EmailData['CompanyName']  	  =   $data['email_from_name'];
			}
			
			
			$EmailData['TicketID']	 	  =   $id;
			$EmailData['Message']	 	  =   $data['Description'];
			$EmailData['Description']	  =   $data['Description'];			
			$EmailData['Status']	 	  =   \Api\Model\TicketsTable::getTicketStatusByID($data['Status']);
			$EmailData['Priority']	 	  =   \Api\Model\TicketPriority::where(["PriorityID"=>$data['Priority']])->pluck('PriorityValue');			
			$status       				  =   sendMail('emails.tickets.TicketCreated', $EmailData);		
				
			if($status['status']==1){
			return email_log_data_Ticket($EmailData,'emails.tickets.TicketCreated',$status);
			}		
			else{
				Log:info(print_r($status,true));
			}				
		}		
		return false;		
	}
	
	/*if($Type=='update')
	{
		if(isset($data['Requester']) && $data['Requester']!=$LogginedUserEmail)
		{
			$EmailData['EmailTo']     	  =   $data['Requester'];
			$EmailData['Subject']   	  =   'Ticket Updated. "'.$data['Subject'].'"';
			$EmailData['TicketSubject']   =   "(Neon) ".$data['Subject'];
			$EmailData['AttachmentPaths'] =   unserialize($data['AttachmentPaths']);
			$EmailData['TitleHeading']    =   $LogginedUserName." <strong>updated</strong> the ticket";
			$EmailData['Description']	  =   $data['Description'];
			
			if(isset($data['email_from']) && !empty($data['email_from'])){
				$EmailData['EmailFrom']       =   $data['email_from'];
				$EmailData['CompanyName']  	  =   $data['email_from_name'];
			}
			$EmailData['Message']	 	  =   $data['Description'];
			$EmailData['Status']	 	  =   \Api\Model\TicketsTable::getTicketStatusByID($data['Status']);
			$EmailData['Priority']	 	  =   \Api\Model\TicketPriority::where(["PriorityID"=>$data['Priority']])->pluck('PriorityValue');			
			$status       				  =   sendMail('emails.tickets.TicketCreated', $EmailData);	
			
			if($status['status']==1){
				$result  =  email_log_data_Ticket($EmailData,'emails.tickets.TicketCreated'); Log::info("email_log_data result",$status); Log::info(print_r($result,true));
				return $result;
			}									
		}		
		return false;		
	} */
}

	function SendComposeTicketEmail($data){
		
		$EmailData['EmailTo']     	  =   $data['Requester'];
		$EmailData['Subject']   	  =   $data['Subject'];
		$EmailData['EmailFrom']  	  =   $data['email_from'];
		$EmailData['CompanyName']  	  =   $data['email_from_name'];
		$EmailData['AddReplyTo']  	  =   $data['AddReplyTo'];
		$EmailData['cc']  		 	  =   isset($data['cc'])?$data['cc']:'';
		$EmailData['bcc']  		 	  =   isset($data['bcc'])?$data['bcc']:'';
		$EmailData['AttachmentPaths'] =   !empty($data['files'])?unserialize($data['files']):'';
		$EmailData['Description']	  =   $data['Description'];			
		$EmailData['Message']	 	  =   $data['Description'];
		$EmailData['TicketID']	 	  =   $data['TicketID'];
		$status       				  =   sendMail('emails.template', $EmailData);	
		 		
		if($status['status']==0){
				 return generateResponse($status['message'],true,true);
		}		
		if($status['status']==1){
		//	return email_log_data_Ticket($EmailData,'emails.template',$status);
		}
		return false;
	}
	
function SendTaskMail($data){
    $LogginedUser   = \Api\Model\User::get_userID();
    $LogginedUserName   =  \Api\Model\User::get_user_full_name();
    $AssignedUser    = $data['UsersIDs'];
    if($LogginedUser != $AssignedUser){ //if assigned user and logined user are not same then send email

        $AssignedUserData     =  \Api\Model\User::find($AssignedUser);
        $data['EmailTo']     =   $AssignedUserData->EmailAddress;
        //$data['cc']      =   "umer.ahmed@code-desk.com";
        $data['Subject_task']   =   $data['Subject'];
        $data['Subject']      =   "(Neon) ".$data['Subject'];
        $data['TitleHeading']   =   $LogginedUserName." <strong>Assigned</strong> you a Task";
		$data['UserProfileImage']  =  \Api\Model\UserProfile::get_user_picture_url($LogginedUser);
        $status       =   sendMail('emails.task.TaskEmailSend', $data);
    }
}

function SendTaskMailUpdate($NewData,$OldData,$type='Task'){
    $LogginedUser   =  \Api\Model\User::get_userID();
    $LogginedUserName  =  \Api\Model\User::get_user_full_name();

    //Tagged Users Email
    if($NewData['TaggedUsers']!=''){
        $TaggedUsersNew   =  explode(",",$NewData['TaggedUsers']);
        $TaggedUsersOld   =  explode(",",$OldData['TaggedUsers']);
        $TaggedUsersDiff  =  array_diff($TaggedUsersNew, $TaggedUsersOld);
        $TaggedUsersDiffEmail  =  array();
        if(count($TaggedUsersDiff)>0){
            foreach($TaggedUsersDiff as $TaggedUsersDiffData){
                if($LogginedUser!=$TaggedUsersDiffData){
                    $TaggedUserData       =  \Api\Model\User::find($TaggedUsersDiffData);
                    $TaggedUsersDiffEmail[]   =  $TaggedUserData->EmailAddress;
                }
            }
            $NewData['EmailTo']     =   $TaggedUsersDiffEmail;
            //$NewData['cc']        =   "umer.ahmed@code-desk.com";
            if($type=='Opportunity'){
                $NewData['Subject_task']   =   $NewData['OpportunityName'];
                $NewData['Subject']      =   "(Neon) ".$NewData['OpportunityName'];
                $NewData['Description']    =   "";
            }else if($type=='Task'){
                $NewData['Subject_task']   =   $NewData['Subject'];
                $NewData['Subject']      =   "(Neon) ".$NewData['Subject'];
            }
            $NewData['CreatedBy']      =   $OldData['CreatedBy'];
            $NewData['TitleHeading']  =   $LogginedUserName." <strong>Tagged</strong> you in a ".$type;
            $NewData['UserProfileImage']  =  \Api\Model\UserProfile::get_user_picture_url($LogginedUser);

            $status        =   sendMail('emails.task.TaskEmailSend', $NewData);
        }
    }

    if($type=='Task'){
        //Assign Users Email
        if($OldData['UsersIDs']!=$NewData['UsersIDs']){ // new and old assigned user are not same
            if($LogginedUser!=$NewData['UsersIDs']){ //new user and logined user are not same

                $AssignedUserData       =  \Api\Model\User::find($NewData['UsersIDs']);
                $NewData['EmailTo']     =   $AssignedUserData->EmailAddress;
                //$NewData['cc']        =   "umer.ahmed@code-desk.com";
                $NewData['Subject_task']   =   $NewData['Subject'];
                $NewData['Subject']      =   "(Neon) ".$NewData['Subject'];
                $NewData['CreatedBy']      =   $OldData['CreatedBy'];
                $NewData['TitleHeading']  =   $LogginedUserName." <strong>Assigned</strong> you a ".$type;
                $NewData['UserProfileImage']  =  \Api\Model\UserProfile::get_user_picture_url($LogginedUser);
                $status        =   sendMail('emails.task.TaskEmailSend', $NewData);
            }
        }
    }
}


function combile_url_path($url, $path){

    return add_trailing_slash($url). $path;
}

/** Add slash at the end
 * @param string $str
 * @return string
 */
function add_trailing_slash($str = ""){

    if(!empty($str)){

        return rtrim($str, '/') . '/';

    }
}

/** Remove slash at the start
 * @param string $str
 * @return string
 */
function remove_front_slash($str = ""){

    if(!empty($str)){

        return ltrim($str, '/')  ;

    }
}
function get_currenttime(){
    return date('Y-m-d H:i:s');
}
function template_var_replace($EmailMessage,$replace_array){
    $extra = [
        '{{FirstName}}',
        '{{LastName}}',
        '{{Email}}',
        '{{Address1}}',
        '{{Address2}}',
        '{{Address3}}',
        '{{City}}',
        '{{State}}',
        '{{PostCode}}',
        '{{Country}}',
        '{{InvoiceNumber}}',
        '{{InvoiceGrandTotal}}',
        '{{InvoiceOutstanding}}',
        '{{OutstandingExcludeUnbilledAmount}}',
        '{{Signature}}',
        '{{OutstandingIncludeUnbilledAmount}}',
        '{{BalanceThreshold}}',
        '{{Currency}}',
        '{{CompanyName}}'
    ];

    foreach($extra as $item){
        $item_name = str_replace(array('{','}'),array('',''),$item);
        if(array_key_exists($item_name,$replace_array)) {
            $EmailMessage = str_replace($item,$replace_array[$item_name],$EmailMessage);
        }
    }
    return $EmailMessage;
}
function next_run_time($data){

    $Interval = $data['Interval'];
    if(isset($data['StartTime'])) {
        $StartTime = $data['StartTime'];
    }
    if(isset($data['LastRunTime'])){
        $LastRunTime = $data['LastRunTime'];
    }else{
        $LastRunTime = date('Y-m-d H:i:00');
    }
    switch($data['Time']) {
        case 'HOUR':
            if($LastRunTime == ''){
                $strtotime = strtotime('+'.$Interval.' hour');
            }else{
                $strtotime = strtotime($LastRunTime)+$Interval*60*60;
            }
            return date('Y-m-d H:i:00',$strtotime);
        case 'MINUTE':
            if($LastRunTime == ''){
                $strtotime = strtotime('+'.$Interval.' minute');
            }else{
                $strtotime = strtotime($LastRunTime)+$Interval*60;
            }
            return date('Y-m-d H:i:00',$strtotime);
        case 'DAILY':
            if($LastRunTime == ''){
                $strtotime = strtotime('+'.$Interval.' day');
            }else{
                $strtotime = strtotime($LastRunTime)+$Interval*60*60*24;
            }
            if(isset($StartTime)){
                return date('Y-m-d',$strtotime).' '.date("H:i:00", strtotime("$StartTime"));
            }
            return date('Y-m-d H:i:00',$strtotime);
        case 'MONTHLY':
            if($LastRunTime == ''){
                $strtotime = strtotime('+'.$Interval.' month');
            }else{
                $strtotime = strtotime("+$Interval month", strtotime($LastRunTime));
            }
            if(isset($StartTime)){
                return date('Y-m-d',$strtotime).' '.date("H:i:00", strtotime("$StartTime"));
            }
            return date('Y-m-d H:i:00',$strtotime);
        default:
            return '';

    }
}