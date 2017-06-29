<?php 
namespace App;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Api\Model\Company;
use Api\Model\User;
use Api\Model\TicketsTable;
use Api\Model\TicketPriority;
use Api\Model\TicketGroups;
use Api\Model\AccountEmailLog;
use Api\Model\EmailTemplate;
use Api\Model\TicketGroupAgents;
use Api\Model\Note;
use Api\Model\CompanyConfiguration;
use Illuminate\Support\Facades\DB;

class Imap{

protected $email;
protected $password;
protected $status;
protected $server;

	 public function __construct($data = array()){
		 foreach($data as $key => $value){
			 $this->$key = $value;
		 }		 		 
	 }
	 
	 function ReadEmails($CompanyID){
		 
		$email 		= 	$this->email;
		$password 	= 	$this->password;		
		$server		=	$this->server;		
		$inbox  	= 	imap_open("{".$server."}", $email, $password)  or Log::info("can't connect: " . imap_last_error()); 
		$emails 	= 	imap_search($inbox,'UNSEEN');

		if($emails){
			
			/* begin output var */
			$output = ''; 
			
			/* start from bottom */
		 	$emails =  array_reverse($emails);
		
			
			/* for every email... */
			foreach($emails as $key => $email_number){
				
				$MatchType	 =   ''; $MatchID = '';
				
				/* get information specific to this email */
				$overview 		= 		imap_fetch_overview($inbox,$email_number,0);    
				//$message		=		quoted_printable_decode(imap_fetchbody($inbox, $email_number, 1));
				//$header   	=  		imap_fetchheader($inbox,$email_number);
				$structure		= 		imap_fetchstructure($inbox, $email_number); 
				$message_id   	= 		isset($overview[0]->message_id)?$overview[0]->message_id:'';
				$references   	=  		isset($overview[0]->references)?$overview[0]->references:'';
				$in_reply_to  	= 		isset($overview[0]->in_reply_to)?$overview[0]->in_reply_to:$message_id;				
				$msg_parent   	=		AccountEmailLog::where("MessageID",$in_reply_to)->first();

				if(!empty($msg_parent)){ // if email is reply of an email
					if($msg_parent->EmailParent==0){
						$parent = $msg_parent->AccountEmailLogID;                        
					}else{
						$parent = $msg_parent->EmailParent;
					}
					$parent_account =  $msg_parent->AccountID;
					$parent_UserID  =  $msg_parent->UserID;
					$AccountData    =   DB::table('tblAccount')->where(["AccountID" => $parent_account])->get(array("AccountName"));
					$Accountname    =  isset($AccountData[0]->AccountName)?' ('.($AccountData[0]->AccountName).')':'';
					$AccountTitle	=  $this->GetNametxt($overview[0]->from).$Accountname;
				}
				else
				{					
					// if email is not a reply of an email then search email in account,leads,contacts
					$parent_account 	= 	 0;
					$parent_UserID  	= 	 0;
					$parent 			= 	 0; // no parent by default		
					
					$MatchArray  		  =     $this->findEmailAddress($this->GetEmailtxt($overview[0]->from));
					$MatchType	 		  =     $MatchArray['MatchType'];
					$MatchID	 		  =	    $MatchArray['MatchID'];
					if($MatchArray['AccountID']){
						$parent_account = 	 $MatchArray['AccountID'];					
					}
					$AccountTitle		  =	    !empty($MatchArray['AccountTitle'])?$MatchArray['AccountTitle']:$this->GetNametxt($overview[0]->from);	
                }
				
				$attachmentsDB 		  =		$this->ReadAttachments($structure,$inbox,$email_number,$CompanyID); //saving attachments				
				
				if(isset($attachmentsDB) && count($attachmentsDB)>0){
					$AttachmentPaths  =		serialize($attachmentsDB);
					//$message		  =		$this->GetMessageBody(quoted_printable_decode(imap_fetchbody($inbox, $email_number, 1.2))); //if email has attachment then read 1.2 message part else read 1		
					
				}else{
					//$message		  =		quoted_printable_decode(imap_fetchbody($inbox, $email_number, 1.2));					
					$AttachmentPaths  = 	serialize([]);										
				}
			
				/*$message64		  = 	base64_decode($message);  //base 64 encoded msgs
				if((strlen($message64)>0) &&  (strpos($message64,"<html")>-1)){
						$message 	  = 	$message64;
						Log::info("msg64");
						Log::info($message64);	
				}*/
				
			 	$message = 	$this->getBody($inbox,$email_number);
				if(!empty($message)){
					$message =  $this->GetMessageBody($message);
				}
			
                $from   = $this->GetEmailtxt($overview[0]->from);
				$to 	= $this->GetEmailtxt($overview[0]->to);
				
				$logData = ['EmailFrom'=> $from,
				"EmailfromName"=>!empty($AccountTitle)?$AccountTitle:$this->GetNametxt($overview[0]->from),
				'Subject'=>$overview[0]->subject,
				'Message'=>$message,
				'CompanyID'=>$CompanyID,
				"MessageID"=>$message_id,
				"EmailParent" => $parent,
				"AccountID" => $parent_account,				
				"AttachmentPaths"=>$AttachmentPaths,
				"EmailID"=>$email_number,
				"EmailCall"=>Messages::Received,
                "UserID" => $parent_UserID,
                 "created_at"=>date('Y-m-d H:i:s'),
				 "EmailTo"=>$to
				];			
				$EmailLog   =  AccountEmailLog::insertGetId($logData);
				if($parent){ 					
          			AccountEmailLog::find($parent)->update(array("updated_at"=>date('Y-m-d H:i:s')));
				}
				
				$title = "Received from ".$AccountTitle." (".$from." )";
				
					 $data   = array(
						"CompanyID"=>$CompanyID,
						"AccountID"=> $parent_account,
						"Title"=>$title,
						"MsgLoggedUserID"=>$parent_UserID,
						"Description"=>$overview[0]->subject,
						"MatchType"=>$MatchType,
						"MatchID"=>$MatchID,
						"EmailID"=>$EmailLog
					);  
                    Messages::logMsgRecord($data);
				
				//$status = imap_setflag_full($inbox, $email_number, "\\Seen \\Flagged", ST_UID); //email staus seen
				imap_setflag_full($inbox,imap_uid($inbox,$email_number),"\\SEEN",ST_UID);
			}			
		} 		
		/* close the connection */
		imap_close($inbox);
	}
	
	function ReadAttachments($structure,$inbox,$email_number,$CompanyID)
	{
		$attachments   = array();
		$attachmentsDB = array();
        $UPLOADPATH = CompanyConfiguration::get($CompanyID,'UPLOAD_PATH');
		
		/* if any attachments found code start... */
		if(isset($structure->parts) && count($structure->parts)) 
		{
			for($i = 0; $i < count($structure->parts); $i++) 
			{
				$attachments[$i] = array(
					'is_attachment' => false,
					'filename' => '',
					'name' => '',
					'attachment' => ''
				);
		
				if($structure->parts[$i]->ifdparameters) 
				{
					foreach($structure->parts[$i]->dparameters as $object) 
					{
						if(strtolower($object->attribute) == 'filename') 
						{
							$attachments[$i]['is_attachment'] = true;
							$attachments[$i]['filename'] = $object->value;
						}
					}
				}
		
				if($structure->parts[$i]->ifparameters) 
				{
					foreach($structure->parts[$i]->parameters as $object) 
					{
						if(strtolower($object->attribute) == 'name') 
						{
							$attachments[$i]['is_attachment'] = true;
							$attachments[$i]['name'] = $object->value;
						}
					}
				}
		
				if($attachments[$i]['is_attachment']) 
				{
					$attachments[$i]['attachment'] = imap_fetchbody($inbox, $email_number, $i+1);
		
					/* 3 = BASE64 encoding */
					if($structure->parts[$i]->encoding == 3) 
					{ 
						$attachments[$i]['attachment'] = base64_decode($attachments[$i]['attachment']);
					}
					/* 4 = QUOTED-PRINTABLE encoding */
					elseif($structure->parts[$i]->encoding == 4) 
					{ 
						$attachments[$i]['attachment'] = quoted_printable_decode($attachments[$i]['attachment']);
					}
				}
			}
		}
		
		/* iterate through each attachment and save it */
		foreach($attachments as $attachment)
		{
			if($attachment['is_attachment'] == 1)
			{
				$filename = $attachment['name'];
				if(empty($filename)) $filename = $attachment['filename'];		
				if(empty($filename)) $filename = time() . ".dat";
				
				$file_name 		=  \Webpatser\Uuid\Uuid::generate()."_".basename($filename);
				$amazonPath 	= 	AmazonS3::generate_upload_path(AmazonS3::$dir['EMAIL_ATTACHMENT'],'',$CompanyID);
				
				if(!is_dir($UPLOADPATH.'/'.$amazonPath)){
					 mkdir($UPLOADPATH.'/'.$amazonPath, 0777, true);
				}
				
				$filepath   =  $UPLOADPATH.'/'.$amazonPath . $email_number . "-" . $file_name;
				$filepath2  =  $amazonPath . $email_number . "-" . $file_name; 
				$fp = fopen($filepath, "w+");
				fwrite($fp, $attachment['attachment']);
				fclose($fp);
				
				if(is_amazon($CompanyID)){
					if (!AmazonS3::upload($filepath, $amazonPath,$CompanyID)) {
						throw new \Exception('Error in Amazon upload');	
					}					
				}
				$attachmentsDB[] = array("filename"=>$filename,"filepath"=>$filepath2); 
			}
		}
		/* attachments code end... */
		return $attachmentsDB;			
	}
	
	function GetEmailtxt($email)
	{
		$pos 	= 	strpos($email, "<");	
		if($pos){			
		  $first = explode("<",$email);
		  if(count($first)>0){
			 $second = explode(">",$first[1]);
			 return $second[0];
		   }		
		}else{
				return $email;
		}	
	}

    function GetNametxt($email)
    {
        $pos 	= 	strpos($email, "<");
        if($pos){
            $first = explode("<",$email);
            if(count($first)>0){
              return  $first[0];
            }
        }else{
            return $email;
        }
    }
	
	function GetMessageBody($msg){
		$doc = new \DOMDocument();
		$mock = new \DOMDocument;
		libxml_use_internal_errors(true);
		// load the HTML into the DomDocument object (this would be your source HTML)
		$doc->loadHTML($msg);		
		$this->removeElementsByTagName('script', $doc);
		$this->removeElementsByTagName('style', $doc); 
		//removeElementsByTagName('link', $doc);
		$body = $doc->getElementsByTagName('body')->item(0);
		foreach ($body->childNodes as $child){
			$mock->appendChild($mock->importNode($child, true));
		}	
		// output cleaned html
		$msg = $mock->saveHtml();	
		return $msg;	
	}	
	
	static	function CheckConnection($server,$email,$password){
		try{
			imap_open("{".$server."}INBOX", $email, $password);
			//return true;
			return array("status"=>1,"error"=>0);
		} catch (\Exception $e) {
			Log::error("Unable to validate");
			Log::error($e);			
			return array("status"=>0,"error"=>"could not connect");
			//return false;
		}   		
	}	
	
	function findEmailAddress($email)
	{
		$AccountTitle 	= 	'';
		$MatchID		=	0;
		$MatchType		=	'';
		$AccountID		=	0;
		
		//find in account(email,billing email), Email
		$AccountSearch1  =  DB::table('tblAccount')->whereRaw("find_in_set('".$email."',Email) OR find_in_set('".$email."',BillingEmail)")->get(array("AccountID","AccountName","AccountType"));
		
		if(count($AccountSearch1)>0)													
		{
			if(count($AccountSearch1)>0)													
			{
				if($AccountSearch1[0]->AccountType==1)
				{
					$MatchType	 =   'Account';
				}else{
					$MatchType	 =   'Lead';
				}
				
				$MatchID	  =	 $AccountSearch1[0]->AccountID;
				$AccountTitle =  $AccountSearch1[0]->AccountName;
				$AccountID	  =  $AccountSearch1[0]->AccountID;							
				return array('MatchType'=>$MatchType,'MatchID'=>$MatchID,"AccountTitle"=>$AccountTitle,"AccountID"=>$AccountID);      
			}		
		}
		
		$ContactSearch 	 =  DB::table('tblContact')->whereRaw("find_in_set('".$email."',Email)")->get(array("Owner","ContactID","FirstName","LastName"));		
		
		if(count($ContactSearch)>0 || count($ContactSearch)>0)													
		{	 
				$MatchType	  =   'Contact';
				$MatchID	  =	 $ContactSearch[0]->ContactID;					
				$AccountID	  =  $ContactSearch[0]->Owner;
				//$Accountdata  =  
				$Accountdata  =   DB::table('tblAccount')->where(["AccountID" => $AccountID])->get(array("AccountName")); 
				$Accountname  =   isset($Accountdata[0]->AccountName)?' ('.($Accountdata[0]->AccountName).')':'';
				$AccountTitle =   $ContactSearch[0]->FirstName.' '.$ContactSearch[0]->LastName.$Accountname;	
				return array('MatchType'=>$MatchType,'MatchID'=>$MatchID,"AccountTitle"=>$AccountTitle,"AccountID"=>$AccountID);      						
		}				
        
		$UserSearch 	 =  DB::table('tblUser')->where(['EmailAddress'=>$email])->get(array("UserID","FirstName","LastName"));		
		
		if(count($UserSearch)>0 || count($UserSearch)>0)													
		{	 
				$MatchType	  =   'User';
				$MatchID	  =	 $UserSearch[0]->UserID;					
				$AccountTitle =  $UserSearch[0]->FirstName.' '.$UserSearch[0]->LastName;	
				return array('MatchType'=>$MatchType,'MatchID'=>$MatchID,"AccountTitle"=>$AccountTitle,"AccountID"=>0);      						
		}			
	}
	
	function getBody($imap,$uid) {
	$uid  =  imap_uid ($imap,$uid); //getting mail uid
    $body = $this->get_part($imap, $uid, "TEXT/HTML");
    // if HTML body is empty, try getting text body
    if ($body == "") {
        $body = $this->get_part($imap, $uid, "TEXT/PLAIN");
    }
        return $body;
    }

    function get_part($imap, $uid, $mimetype, $structure = false, $partNumber = false){
    if (!$structure) {
           $structure = imap_fetchstructure($imap, $uid, FT_UID);
    }
    if ($structure) {
        if ($mimetype == $this->get_mime_type($structure)) {
            if (!$partNumber) {
                $partNumber = 1;
            }
            $text = imap_fetchbody($imap, $uid, $partNumber, FT_UID);
            switch ($structure->encoding) {
                case 3: return imap_base64($text);
                case 4: return imap_qprint($text);
                default: return $text;
           }
       }

        // multipart
        if ($structure->type == 1) {
            foreach ($structure->parts as $index => $subStruct) {
                $prefix = "";
                if ($partNumber) {
                    $prefix = $partNumber . ".";
                }
                $data = $this->get_part($imap, $uid, $mimetype, $subStruct, $prefix.($index + 1));
                if ($data) {
                    return $data;
                }
            }
        }
    }
    return false;
    }

    function get_mime_type($structure) {
        $primaryMimetype = array("TEXT", "MULTIPART", "MESSAGE", "APPLICATION",    "AUDIO", "IMAGE", "VIDEO", "OTHER");

        if ($structure->subtype) {
           return $primaryMimetype[(int)$structure->type] . "/" . $structure->subtype;
        }
        return "TEXT/PLAIN";
    }  
	function removeElementsByTagName($tagName, $document) {
	  $nodeList = $document->getElementsByTagName($tagName);
	  for ($nodeIdx = $nodeList->length; --$nodeIdx >= 0; ) {
		$node = $nodeList->item($nodeIdx);
		$node->parentNode->removeChild($node);
	  }
	}
	
	
	function ReadTicketEmails($CompanyID,$server,$email,$password,$GroupID){
		$AllEmails  =   Messages::GetAllSystemEmails();
		$email 		= 	$email;
		$password 	= 	$password;		
		$server		=	$server;		
		$inbox  	= 	imap_open("{".$server."}", $email, $password)  or Log::info("can't connect: " . imap_last_error()); 
		$emails 	= 	imap_search($inbox,'UNSEEN');
		Log::info("connectiong:".$email);
		if($emails){
			
			/* begin output var */
			$output = ''; 
			
			/* start from bottom */
		 	$emails =  array_reverse($emails);
		
			try {
			/* for every email... */
			foreach($emails as $key => $email_number){
				Log::info("reading emails");
				/* get information specific to this email */
				$MatchType	 				= 		  ''; 
				$MatchID 					= 		  '';
				$priority					=		  1;				
				$headers 					=		  array();
				$overview 					= 		  imap_fetch_overview($inbox,$email_number,0);    
				$structure					= 		  imap_fetchstructure($inbox, $email_number); 
				$header 					= 		  imap_fetchheader($inbox, $email_number);
				$message_id   				= 		  isset($overview[0]->message_id)?$overview[0]->message_id:'';
				$references   				=  		  isset($overview[0]->references)?$overview[0]->references:'';
				$in_reply_to  				= 		  isset($overview[0]->in_reply_to)?$overview[0]->in_reply_to:$message_id;				
				$msg_parent   				=		  AccountEmailLog::where("MessageID",$in_reply_to)->first();
				$headerdata					=		  imap_headerinfo($inbox, $email_number);
				
				//$msg_parentconversation   	=		  TicketsConversation::where("MessageID",$in_reply_to)->first();
				// Split on \n  for priority 
				$h_array					=		  explode("\n",$header);
		
				foreach ( $h_array as $h ) {				
					// Check if row start with a char
						if ( preg_match("/^[A-Z]/i", $h )) {				
						$tmp 					= 	explode(":",$h);
						$header_name 		    = 	$tmp[0];
						$header_value 			= 	$tmp[1];								
						$headers[$header_name] 	= 	$header_value;						
					} else {
						// Append row to previous field
						$headers[$header_name]  = 	$header_value . $h;
					}				
				}
				if(isset($headers['X-Priority']) && $headers['X-Priority']!=''){
					$prioritytxt  =  explode("X-Priority ",$headers['X-Priority']);
					$prioritytxt2 =  explode(" (",$prioritytxt[0]);						
					$priority	  =	isset(Messages::$EmailPriority[trim($prioritytxt2[0])])?Messages::$EmailPriority[trim($prioritytxt2[0])]:1;
				}
				
				//if(!empty($msg_parent) || !empty($msg_parentconversation)){  // if email is reply of an ticket or conversation					
				if(!empty($msg_parent)){ 
					if(!empty($msg_parent)){
						if($msg_parent->EmailParent==0){
							$parent = $msg_parent->AccountEmailLogID;                        
						}else{
							$parent = $msg_parent->EmailParent;
						}
						$parent_UserID  =  $msg_parent->UserID;
					}/*else if(!empty($msg_parentconversation)){
						$parent = $msg_parent->TicketID;
					}*/
				}else{							    //new ticket						
					$parent 			  = 	 0; // no parent by default		
					$parent_UserID  	  =      0;
                }
				
				$attachmentsDB 		  =		$this->ReadAttachments($structure,$inbox,$email_number,$CompanyID); //saving attachments	
				if(isset($attachmentsDB) && count($attachmentsDB)>0){
					$AttachmentPaths  =		serialize($attachmentsDB);				
				}else{
					$AttachmentPaths  = 	serialize([]);										
				}
				
			 	$message = 	$this->getBody($inbox,$email_number);
				if(!empty($message)){
					$message =  $this->GetMessageBody($message);
				}
			
                $from   	= 	$this->GetEmailtxt($overview[0]->from);
				$to 		= 	$this->GetEmailtxt($overview[0]->to);
				$FromName	=	$this->GetNametxt($overview[0]->from);
				$cc			=	isset($headerdata->ccaddress)?$this->GetEmailtxt($headerdata->ccaddress):'';
				$bcc		=	isset($headerdata->bccaddress)?$this->GetEmailtxt($headerdata->bccaddress):'';
				$update_id  =	''; $insert_id  =	'';
						
				/*if($parent){ 	
					$logData = [
					    'TicketID'=>$parent,
						'Requester'=> $from,
						"RequesterName"=>$FromName,
						'Subject'=>$overview[0]->subject,
						'TicketMessage'=>$message,
						"MessageID"=>$message_id,
						"AttachmentPaths"=>$AttachmentPaths,
						"EmailID"=>$email_number,
						"EmailCall"=>Messages::Received,
						"created_at"=>date('Y-m-d H:i:s'),
					];	
	          		 TicketsConversation::insertGetId($logData);
				}else{
					
					$logData = [
						'Requester'=> $from,
						"RequesterName"=>$FromName,
						'Subject'=>$overview[0]->subject,
						'Description'=>$message,
						'CompanyID'=>$CompanyID,
						"MessageID"=>$message_id,
						"AttachmentPaths"=>$AttachmentPaths,
						"EmailID"=>$email_number,
						"EmailCall"=>TicketsTable::Received,
						"Group"=>$GroupID,
						"created_at"=>date('Y-m-d H:i:s'),
						"Priority"=>$priority,
						"Status"=>TicketsTable::getOpenTicketStatus()
					];	
				  	 TicketsTable::insertGetId($logData);
				}*/
				
				if(!$parent){
				$logData = [
						'Requester'=> $from,
						"RequesterName"=>$FromName,
						'Subject'=>$overview[0]->subject,
						'Description'=>$message,
						'CompanyID'=>$CompanyID,
						"AttachmentPaths"=>$AttachmentPaths,
						"Group"=>$GroupID,
						"created_at"=>date('Y-m-d H:i:s'),
						"Priority"=>$priority,
						"Status"=>TicketsTable::getOpenTicketStatus(),
						"created_by"=> 'RMScheduler'
					];
						
					$ticketID 		=  TicketsTable::insertGetId($logData);
					if($GroupID){
						$TicketEmails 	=  new TicketEmails(array("TicketID"=>$ticketID,"TriggerType"=>array("AgentAssignedGroup")));
					}					
					$TicketEmails 	=  new TicketEmails(array("TicketID"=>$ticketID,"TriggerType"=>array("RequesterNewTicketCreated")));
				}
				else //reopen ticket if ticket status closed 
				{
					$old_status = TicketsTable::where(["AccountEmailLogID"=>$parent])->pluck("Status");
					if($old_status==TicketsTable::getClosedTicketStatus() || $old_status==TicketsTable::getResolvedTicketStatus()){
						TicketsTable::where(["AccountEmailLogID"=>$parent])->update(["Status"=>TicketsTable::getOpenTicketStatus()]);		
					}	
				
					$TicketData_parent = TicketsTable::where(["AccountEmailLogID"=>$parent])->first();
					if($from==$TicketData_parent->Requester){		
						$TicketEmails 	=  new TicketEmails(array("TicketID"=>$TicketData_parent->TicketID,"TriggerType"=>"RequesterRepliestoTicket","CompanyID"=>$CompanyID,"Comment"=>$message));
						Log::info("error:".$TicketEmails->GetError());
					}
			
				}
				$logData = ['EmailFrom'=> $from,
					"EmailfromName"=>$FromName,
					'Subject'=>$overview[0]->subject,
					'Message'=>$message,
					'CompanyID'=>$CompanyID,
					"MessageID"=>$message_id,
					"EmailParent" => $parent,
					"AttachmentPaths"=>$AttachmentPaths,
					"EmailID"=>$email_number,
					"EmailCall"=>Messages::Received,
					"UserID" => $parent_UserID,
					"created_at"=>date('Y-m-d H:i:s'),
					"EmailTo"=>$to,
					"CreatedBy"=> 'RMScheduler',
					"Cc"=>$cc,
					"Bcc"=>$bcc,
				];	
						
				$EmailLog   =  AccountEmailLog::insertGetId($logData);
				if(!$parent)
				{
					 TicketsTable::find($ticketID)->update(array("AccountEmailLogID"=>$EmailLog));
					 $TicketEmails 		=  new TicketEmails(array("TicketID"=>$ticketID,"TriggerType"=>"CCNewTicketCreated"));
					 
					if(!in_array($from,$AllEmails)){
						$ContactData = array("FirstName"=>$FromName,"Email"=>$from,"CompanyId"=>$CompanyID);
						$contactID =  Contact::insertGetId($ContactData);
						$EmailLogObj = AccountEmailLog::find($EmailLog);
						$EmailLogObj->update(array("UserType"=>Messages::UserTypeContact,"ContactID"=>$contactID));		
						$AllEmails[] = $from;
					}else{
						$accountIDSave  =	0;
						$accountID   	=  DB::table('tblAccount')->where(array("Email"=>$from))->pluck("AccountID");
						$accountID2  	=  DB::table('tblAccount')->where(array("BillingEmail"=>$from))->pluck("AccountID");
						if($accountID){
							$accountIDSave = $accountID;
						}
						if($accountID2){
							$accountIDSave = $accountID2;
						}
						$EmailLogObj = AccountEmailLog::find($EmailLog);
						if($accountIDSave){
							$EmailLogObj->update(array("AccountID"=>$accountIDSave));		
						}else{
							 $ContactID 	 =  DB::table('tblContact')->where(array("Email"=>$from))->pluck("ContactID");	
							 if($ContactID){
								$EmailLogObj->update(array("UserType"=>Messages::UserTypeContact,"ContactID"=>$ContactID));					
							  }
						}
					}
				}
				
				
				//$status = imap_setflag_full($inbox, $email_number, "\\Seen \\Flagged", ST_UID); //email staus seen
				imap_setflag_full($inbox,imap_uid($inbox,$email_number),"\\SEEN",ST_UID); 
			}
			} catch (Exception $e) {
				Log::error("Tracking email imap failed");
				Log::error($e);								
			}   			
		} 		
		/* close the connection */
		imap_close($inbox);
		Log::info("reading emails completed");
	}  
}
?>