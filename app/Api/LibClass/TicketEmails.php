<?php 
namespace App;
use Api\Model\Translation;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Api\Model\Company;
use Api\Model\User;
use Api\Model\Account;
use Api\Model\TicketsTable;
use Api\Model\TicketPriority;
use Api\Model\TicketGroups;
use Api\Model\AccountEmailLog;
use Api\Model\EmailTemplate;
use Api\Model\TicketGroupAgents;
use Api\Model\Contact;
use Api\Model\Note;
use Api\Model\CompanyConfiguration;
use Api\Model\Currency;
use Illuminate\Support\Facades\Session;


class TicketEmails{

	protected $TriggerTypes;
	protected $Agent;
	protected $Group;
	protected $EmailFrom;
	protected $Client;
	protected $TicketID;
	protected $TicketData;
	protected $TicketEmailData;
	protected $EmailTemplate;
	protected $slug;
	protected $Error;
	protected $CompanyID;
	protected $Comment;
	protected $NoteUser;
	protected $EmailSenderFrom;
	protected $EmailSenderTo;
	protected $arrOtherData;

	 public function __construct($data = array()){
		 foreach($data as $key => $value){
			 $this->$key = $value;
		 }		 		 
		 $this->CompanyID = User::get_companyID();
		 $this->TriggerEmail();  
	 }
	 
	 public function TriggerEmail(){
		try
		{
			$this->TicketData	  	=  		TicketsTable::find($this->TicketID);	 				
			if(is_array($this->TriggerType))
			{
				foreach($this->TriggerType as $TriggerType){
					if(method_exists($this,TicketEmails::$TriggerType())){						
						$this->$TriggerType();
					}
					
				}
			}else{
				if(method_exists($this,$this->TriggerType)){
					$method = $this->TriggerType;
					$this->$method();
				}
			}
			
		}
		catch(\Exception $ex)
		{
			Log::error("could not Trigger");
			Log::error($ex);		
			return $ex;
		}
	 }	
	 
	protected function ReplaceArray($Ticketdata){
        $replace_array = array();
		if(isset($Ticketdata) && !empty($Ticketdata)){			
			$replace_array['Subject'] 			 = 		$Ticketdata->Subject;
			$replace_array['TicketID'] 			 = 		$Ticketdata->TicketID;
			$replace_array['Requester'] 		 = 		$Ticketdata->Requester;
			
			if($Ticketdata->AccountID){
				$AccountData						 =		Account::where("AccountID",$Ticketdata->AccountID)->select(['AccountName','FirstName','LastName','Email','Address1','Address2','Address3','City','PostCode','Country'])->first();
				$replace_array['RequesterName'] 	    = 	$AccountData->AccountName;
				$replace_array['AccountName']			=	$AccountData->AccountName;
				$replace_array['FirstName']				=	$AccountData->FirstName;
				$replace_array['LastName']				=	$AccountData->LastName;
				$replace_array['Email']					=	$AccountData->Email;
				$replace_array['Address1']				=	$AccountData->Address1;
				$replace_array['Address2']				=	$AccountData->Address2;
				$replace_array['Address3']				=	$AccountData->Address3;
				$replace_array['City']					=	$AccountData->City;

				$replace_array['PostCode']				=	$AccountData->PostCode;
				$replace_array['Country']				=	$AccountData->Country;
				$array['Currency']						=	 Currency::getCurrencyCode($AccountData->CurrencyId);
				$array['CurrencySign']					=	 Currency::getCurrencySymbol($AccountData->CurrencyId);
			}
			else if($Ticketdata->ContactID){
				$contactData						 =		Contact::where("ContactID",$Ticketdata->ContactID)->select(['FirstName','LastName'])->first();
				$replace_array['RequesterName'] 	 = 	    $contactData->FirstName." ".$contactData->LastName;
			}
			else if($Ticketdata->UserID){
				$UserData						 	 =		User::where("UserID",$Ticketdata->UserID)->select(['FirstName','LastName'])->first();
				$replace_array['RequesterName'] 	 = 	    $UserData->FirstName." ".$UserData->LastName;
			}else{
				$replace_array['RequesterName'] 	 = 		$Ticketdata->RequesterName;	
			}
			
			$replace_array['Status'] 			 = 		isset($Ticketdata->Status)?TicketsTable::getTicketStatusByID($Ticketdata->Status):TicketsTable::getDefaultStatus();
			$replace_array['Priority']	 		 = 		TicketPriority::getPriorityStatusByID($Ticketdata->Priority);
			$replace_array['Description'] 	 	 = 		$Ticketdata->Description;
			$replace_array['Group'] 			 = 		isset($Ticketdata->Group)?TicketGroups::where(['GroupID'=>$Ticketdata->Group])->pluck("GroupName"):'';
			$replace_array['Type'] 				 = 		isset($Ticketdata->Type)?TicketsTable::getTicketTypeByID($Ticketdata->Type):'';
			$replace_array['Date']				 = 		$Ticketdata->created_at;
			$replace_array['helpdesk_name']		 = 		isset($Ticketdata->Group)?TicketGroups::where(['GroupID'=>$Ticketdata->Group])->pluck("GroupName"):'';
			$replace_array['Comment']			 =		$this->Comment;
			$replace_array['NoteUser']			 =		isset($this->NoteUser)?$this->NoteUser:0; 

			$request								=	 new \Dingo\Api\Http\Request;
			$replace_array['Logo']					= 	 "<img src='".getCompanyLogo($request)."' />";
		}    
		$Signature 			= 	'';
		$JobLoggedUser 		= 	User::find(User::get_userID());
		
        if(!empty($JobLoggedUser)){
          if(isset($JobLoggedUser->EmailFooter) && trim($JobLoggedUser->EmailFooter) != '')
            {
                $Signature = $JobLoggedUser->EmailFooter;
            }
        }
		
        $replace_array['Signature']= $Signature;		
        return $replace_array;
    }	
	
	protected function template_var_replace($EmailMessage,$replace_array){
		
		$replace_array 	=	$this->SetBasicFields($replace_array);
		
		$extra = [
			'{{Subject}}',
			'{{TicketID}}',
			'{{Requester}}',
			'{{RequesterName}}',
			'{{AccountName}}',
			'{{FirstName}}',
			'{{LastName}}',
			'{{Email}}',
			'{{Address1}}',
			'{{Address2}}',
			'{{Address3}}',
			'{{City}}',
			'{{PostCode}}',
			'{{Country}}',
			'{{Currency}}',
			'{{CurrencySign}}',
			'{{Status}}',
			'{{Priority}}',
			'{{Description}}',
			'{{Group}}',
			'{{Type}}',
			'{{Date}}',
			'{{Signature}}',
			'{{AgentName}}',
			'{{AgentEmail}}',
			'{{helpdesk_name}}',
			'{{NoteUser}}',
			'{{CompanyName}}',
			"{{CompanyVAT}}",
			"{{CompanyAddress1}}",
			"{{CompanyAddress2}}",
			"{{CompanyAddress3}}",
			"{{CompanyCity}}",
			"{{CompanyPostCode}}",
			"{{CompanyCountry}}",
			"{{TicketCustomerUrl}}",
			"{{TicketUrl}}",
			'{{Comment}}',
			'{{Logo}}',
			'{{NoteUser}}'
			
		];

		foreach($extra as $item){
			$item_name = str_replace(array('{','}'),array('',''),$item);
			if(array_key_exists($item_name,$replace_array)) {
				$EmailMessage = str_replace($item,$replace_array[$item_name],$EmailMessage);
			}
		}

		$EmailMessage = preg_replace("/\{\{(\w+)}}/", "", $EmailMessage);

		return $EmailMessage;
	} 
	
	protected function SetBasicFields($array){
		
			$array_data									=		array();
			$CompanyData								=		Company::find($this->CompanyID);
			$site_url 									= 		CompanyConfiguration::get("WEB_URL");
			$array_data['CompanyName']					=   	$CompanyData->CompanyName;
			$array_data['CompanyVAT']					=   	$CompanyData->VAT;			
			$array_data['CompanyAddress1']				=   	$CompanyData->Address1;
			$array_data['CompanyAddress2']				=   	$CompanyData->Address2;
			$array_data['CompanyAddress3']				=   	$CompanyData->Address3;
			$array_data['CompanyCity']					=   	$CompanyData->City;
			$array_data['CompanyPostCode']				=   	$CompanyData->PostCode;
			$array_data['CompanyCountry']				=   	$CompanyData->Country;
			$array_data['TicketUrl']					=   	$site_url."/tickets/".$this->TicketID."/detail";	
			$array_data['TicketCustomerUrl']			=   	$site_url."/customer/tickets/".$this->TicketID."/detail";	
			$array_data['Group']						=   	isset($this->Group->GroupName)?$this->Group->GroupName:'';
			$array_data['AgentName']					=   	isset($this->Agent->FirstName)?$this->Agent->FirstName.' '.$this->Agent->LastName:"";
			$array_data['AgentEmail']					=   	isset($this->Agent->EmailAddress)?$this->Agent->EmailAddress:"";		
			return array_merge($array_data,$array);
	}
	
	protected function AgentNewTicketCreated(){
		
			$this->slug					=		"AgentNewTicketCreated";
			Log::info('AgentNewTicketCreated');
			if(!$this->CheckBasicRequirments())
			{
				return $this->Error;
			}
			
		 	$replace_array				= 		$this->ReplaceArray($this->TicketData);
		    $finalBody 					= 		$this->template_var_replace($this->EmailTemplate->TemplateBody,$replace_array);
			$finalSubject				= 		$this->template_var_replace($this->EmailTemplate->Subject,$replace_array);	
            $emailData['Subject']		=		$finalSubject;
            $emailData['Message'] 		= 		$finalBody;
            $emailData['CompanyID'] 	= 		User::get_companyID();
            $emailData['EmailTo'] 		= 		$this->Agent->EmailAddress;
            $emailData['EmailFrom'] 	= 		$this->Group->GroupReplyAddress;
            $emailData['CompanyName'] 	= 		$this->Group->GroupName;
			$emailData['In-Reply-To'] 	= 		AccountEmailLog::getLastMessageIDByTicketID($this->TicketID);
			$emailData['TicketID'] 		= 		$this->TicketID;
			$emailData['Auto-Submitted']= 		"auto-generated";
			$emailData['Message-ID']	= 		$this->TicketID;
			$status 					= 		sendMail($finalBody,$emailData,0);
			
			if($status['status']){
				//email_log_data_Ticket($emailData,'',$status);						
			}else{
				$this->SetError($status['message']);
			}			
	}
	
		protected function Noteaddedtoticket(){
			$this->slug					=		"Noteaddedtoticket";
			Log::info('Noteaddedtoticket');
			if(!$this->CheckBasicRequirments())
			{
				return $this->Error;
			}
			
			$NoteData					=		Note::where(['TicketID'=>$this->TicketID])->orderBy("NoteID", "desc")->first();
			if(!$NoteData){
				$this->SetError("No Note added");
				return;
			}
			if(isset($this->Agent))
			{
				if($this->Agent->UserID==User::get_userID()){
					$this->SetError("Agent added the note");
					return;
				}
			}
			else{
				$this->SetError("No agent in note");
			}
			
		 	$replace_array				= 		$this->ReplaceArray($this->TicketData);
		    $finalBody 					= 		$this->template_var_replace($this->EmailTemplate->TemplateBody,$replace_array);
			$finalBody 					= 		str_replace('{{Notebody}}',$NoteData->Note,$finalBody);			
			$finalSubject				= 		$this->template_var_replace($this->EmailTemplate->Subject,$replace_array);	
            $emailData['Subject']		=		$finalSubject;
            $emailData['Message'] 		= 		$finalBody;
            $emailData['CompanyID'] 	= 		User::get_companyID();
            $emailData['EmailTo'] 		= 		$this->Agent->EmailAddress;
            $emailData['EmailFrom'] 	= 		$this->Group->GroupReplyAddress;
            $emailData['CompanyName'] 	= 		$this->Group->GroupName;
			$emailData['In-Reply-To'] 	= 		AccountEmailLog::getLastMessageIDByTicketID($this->TicketID);
			$emailData['TicketID'] 		= 		$this->TicketID;
			$emailData['Auto-Submitted']= 		"auto-generated";
			$emailData['Message-ID']	= 		$this->TicketID;
			$status 					= 		sendMail($finalBody,$emailData,0);
			
			if($status['status']){
			//	email_log_data_Ticket($emailData,'',$status);						
			}else{
				$this->SetError($status['message']);
			}
	}
	
	protected function  RequesterNewTicketCreated(){
		$this->slug					=		"RequesterNewTicketCreated";
		Log::info('RequesterNewTicketCreated');
		if(!$this->CheckBasicRequirments())
		{
			Log::info(print_r($this->Error,true));
			return $this->Error;
		}
		$CompanyID = User::get_companyID();
		$Requester = explode(",",$this->TicketData->Requester);
		$Requester = self::remove_group_emails_from_array($CompanyID,$Requester);

		$replace_array				= 		$this->ReplaceArray($this->TicketData);
		$finalBody 					= 		$this->template_var_replace($this->EmailTemplate->TemplateBody,$replace_array);
		$finalSubject				= 		$this->template_var_replace($this->EmailTemplate->Subject,$replace_array);	
		$emailData['Subject']		=		$finalSubject;
		$emailData['Message'] 		= 		$finalBody;
		$emailData['CompanyID'] 	= 		$CompanyID;
		$emailData['EmailTo'] 		= 		$Requester;
		$emailData['EmailFrom'] 	= 		$this->Group->GroupReplyAddress;
		$emailData['CompanyName'] 	= 		$this->Group->GroupName;
		$emailData['In-Reply-To'] 	= 		AccountEmailLog::getLastMessageIDByTicketID($this->TicketID);
		$emailData['TicketID'] 		= 		$this->TicketID;
		$emailData['Auto-Submitted']= 		"auto-generated";
		$emailData['Message-ID']	= 		$this->TicketID;
		$status 					= 		sendMail($finalBody,$emailData,0);
		
		if($status['status']){
			email_log_data_Ticket($emailData,'',$status);
		}else{
			$this->SetError($status['message']);
		}		
	}
	
	protected function AgentAddsCommenttoTicket(){
		
			$this->slug					=		"AgentAddsCommenttoTicket";
			Log::info('AgentAddsCommenttoTicket');
			
			if(!$this->CheckBasicRequirments())
			{
				return $this->Error;
			}
			
			$NoteData					=		Note::where(['TicketID'=>$this->TicketID])->orderBy("NoteID", "desc")->first();
			if(!$NoteData){
				$this->SetError("No Note added");
				return;
			}
			$CompanyID = User::get_companyID();
			$Requester = explode(",",$this->TicketData->Requester);
			$Requester = self::remove_group_emails_from_array($CompanyID,$Requester);

		 	$replace_array				= 		$this->ReplaceArray($this->TicketData);
		    $finalBody 					= 		$this->template_var_replace($this->EmailTemplate->TemplateBody,$replace_array);
			$finalBody 					= 		str_replace('{{Notebody}}',$NoteData->Note,$finalBody);			
			$finalSubject				= 		$this->template_var_replace($this->EmailTemplate->Subject,$replace_array);	
            $emailData['Subject']		=		$finalSubject;
            $emailData['Message'] 		= 		$finalBody;
            $emailData['CompanyID'] 	= 		User::get_companyID();
            $emailData['EmailTo'] 		= 		$Requester;
            $emailData['EmailFrom'] 	= 		$this->Group->GroupReplyAddress;
            $emailData['CompanyName'] 	= 		$this->Group->GroupName;
			$emailData['In-Reply-To'] 	= 		AccountEmailLog::getLastMessageIDByTicketID($this->TicketID);
			$emailData['TicketID'] 		= 		$this->TicketID;
			$emailData['Auto-Submitted']= 		"auto-generated";
			$emailData['Message-ID']	= 		$this->TicketID;
			$status 					= 		sendMail($finalBody,$emailData,0);
			
			if($status['status']){
				//email_log_data_Ticket($emailData,'',$status);						
			}else{
				$this->SetError($status['message']);
			}
	}
	protected function CCNewTicketCreated(){
			
		$emailto					=		array();
		$this->slug					=		"CCNewTicketCreated";
		Log::info('CCNewTicketCreated');
		
		if(!$this->CheckBasicRequirments())
		{
			return $this->Error;
		} 
		if(isset($this->TicketData->RequesterCC) && !empty($this->TicketData->RequesterCC)){
			$emailto = explode(",",$this->TicketData->RequesterCC);
		}else{
			return;
		}
		$CompanyID = User::get_companyID();


		if(count($emailto)>0){

			$emailto = self::remove_group_emails_from_array($CompanyID,$emailto);

			$replace_array				= 		$this->ReplaceArray($this->TicketData);
			$finalBody 					= 		$this->template_var_replace($this->EmailTemplate->TemplateBody,$replace_array);
			$finalSubject				= 		$this->template_var_replace($this->EmailTemplate->Subject,$replace_array);	
			$emailData['Subject']		=		$finalSubject;
			$emailData['Message'] 		= 		$finalBody;
			$emailData['CompanyID'] 	= 		$CompanyID;
			$emailData['EmailTo'] 		= 		$emailto;
			$emailData['EmailFrom'] 	= 		$this->Group->GroupReplyAddress;
			$emailData['CompanyName'] 	= 		$this->Group->GroupName;
			$emailData['In-Reply-To'] 	= 		AccountEmailLog::getLastMessageIDByTicketID($this->TicketID);
			$emailData['TicketID'] 		= 		$this->TicketID;
			$emailData['Auto-Submitted']= 		"auto-generated";
			$emailData['Message-ID']	= 		$this->TicketID;
			$status 					= 		sendMail($finalBody,$emailData,0);

			if($status['status']){
				email_log_data_Ticket($emailData,'',$status);
			}else{
				$this->SetError($status['message']);
			}
		}	
	}
	
	protected function CCNoteaddedtoticket()
	{	
		$emailto					=		array();
		$this->slug					=		"CCNoteaddedtoticket";
		Log::info('CCNoteaddedtoticket');
		
		if(!$this->CheckBasicRequirments())
		{
			return $this->Error;
		} 
		if(isset($this->TicketEmailData->Cc) && !empty($this->TicketEmailData->Cc)){
			$emailto = explode(",",$this->TicketEmailData->Cc);
		}else{
			return;
		}
		$CompanyID = User::get_companyID();


		if(count($emailto)>0){
			$emailto = self::remove_group_emails_from_array($CompanyID,$emailto);
			$replace_array				= 		$this->ReplaceArray($this->TicketData);
			$finalBody 					= 		$this->template_var_replace($this->EmailTemplate->TemplateBody,$replace_array);
			$finalSubject				= 		$this->template_var_replace($this->EmailTemplate->Subject,$replace_array);	
			$emailData['Subject']		=		$finalSubject;
			$emailData['Message'] 		= 		$finalBody;
			$emailData['CompanyID'] 	= 		$CompanyID;
			$emailData['EmailTo'] 		= 		$emailto;
			$emailData['EmailFrom'] 	= 		$this->Group->GroupReplyAddress;
			$emailData['CompanyName'] 	= 		$this->Group->GroupName;
			$emailData['In-Reply-To'] 	= 		AccountEmailLog::getLastMessageIDByTicketID($this->TicketID);
			$emailData['TicketID'] 		= 		$this->TicketID;
			$emailData['Comment'] 		= 		$this->Comment;
			$emailData['NoteUser'] 		= 		$this->NoteUser;
			$emailData['Auto-Submitted']= 		"auto-generated";
			$emailData['Message-ID']	= 		$this->TicketID;
			$status 					= 		sendMail($finalBody,$emailData,0);

			if($status['status']){
				//email_log_data_Ticket($emailData,'',$status);						
				ccEmail_log_data_Ticket($status['message_id'], $this->TicketID);
			}else{
				$this->SetError($status['message']);
			}
		}
	}
	
	
	protected function TicketAssignedtoAgent(){
		
			$this->slug					=		"TicketAssignedtoAgent";
			Log::info('TicketAssignedtoAgent');
			if(!$this->CheckBasicRequirments())
			{
				return $this->Error;
			}
			
		 	$replace_array				= 		$this->ReplaceArray($this->TicketData);
		    $finalBody 					= 		$this->template_var_replace($this->EmailTemplate->TemplateBody,$replace_array);
			$finalSubject				= 		$this->template_var_replace($this->EmailTemplate->Subject,$replace_array);	
            $emailData['Subject']		=		$finalSubject;
            $emailData['Message'] 		= 		$finalBody;
            $emailData['CompanyID'] 	= 		User::get_companyID();
            $emailData['EmailTo'] 		= 		$this->Agent->EmailAddress;
            $emailData['EmailFrom'] 	= 		$this->Group->GroupReplyAddress;
            $emailData['CompanyName'] 	= 		$this->Group->GroupName;
			$emailData['In-Reply-To'] 	= 		AccountEmailLog::getLastMessageIDByTicketID($this->TicketID);
			$emailData['Message-ID']	= 		$this->TicketID;
			$emailData['Auto-Submitted']= 		"auto-generated";
			$status 					= 		sendMail($finalBody,$emailData,0);
			$emailData['TicketID'] 		= 		$this->TicketID;
			
			if($status['status']){
				//email_log_data_Ticket($emailData,'',$status);						
			}else{
				$this->SetError($status['message']);
			}			
	}
	
	protected function AgentAssignedGroup(){
			$slug					=		"AgentAssignedGroup";
			Log::info('AgentAssignedGroup');
			
			if(!$this->CheckBasicRequirments())
			{
				return $this->Error;
			}			
			$account=Account::find($this->TicketData->AccountID);
			$LanguageID=$this->getLanguageID($account);

			$this->EmailTemplate  		=		EmailTemplate::getSystemEmailTemplate(User::get_companyID(), $slug, $LanguageID);
		 	$replace_array				= 		$this->ReplaceArray($this->TicketData);
		    $finalBody 					= 		$this->template_var_replace($this->EmailTemplate->TemplateBody,$replace_array);
			$finalSubject				= 		$this->template_var_replace($this->EmailTemplate->Subject,$replace_array);				
			$Groupagents 				= 		TicketGroupAgents::get_group_agents($this->TicketData->Group,0,'EmailAddress');
			$emailData['Subject']		=		$finalSubject;
            $emailData['Message'] 		= 		$finalBody;
            $emailData['CompanyID'] 	= 		User::get_companyID();
            $emailData['EmailTo'] 		= 		$Groupagents;
            $emailData['EmailFrom'] 	= 		$this->Group->GroupReplyAddress;
            $emailData['CompanyName'] 	= 		$this->Group->GroupName;
			$emailData['In-Reply-To'] 	= 		AccountEmailLog::getLastMessageIDByTicketID($this->TicketID);
			$emailData['Message-ID']	= 		$this->TicketID;
			$emailData['Auto-Submitted']= 		"auto-generated";
			$status 					= 		sendMail($finalBody,$emailData,0);
			$emailData['TicketID'] 		= 		$this->TicketID;
			$emailData['UserID']		=		User::get_userID();			
			
			if($status['status']){
				//email_log_data_Ticket($emailData,'',$status);						
			}else{
				$this->SetError($status['message']);
			}			
	}
	
	protected function AgentSolvestheTicket(){
		$this->slug					=		"AgentSolvestheTicket";
		Log::info('AgentSolvestheTicket');
		if(!$this->CheckBasicRequirments())
		{
			return $this->Error;
		}
		
		$replace_array				= 		$this->ReplaceArray($this->TicketData);
		$finalBody 					= 		$this->template_var_replace($this->EmailTemplate->TemplateBody,$replace_array);
		$finalSubject				= 		$this->template_var_replace($this->EmailTemplate->Subject,$replace_array);	
		$emailData['Subject']		=		$finalSubject;
		$emailData['Message'] 		= 		$finalBody;
		$emailData['CompanyID'] 	= 		User::get_companyID();
		$emailData['EmailTo'] 		= 		$this->TicketData->Requester;
		$emailData['EmailFrom'] 	= 		$this->Group->GroupReplyAddress;
		$emailData['CompanyName'] 	= 		$this->Group->GroupName;
		$emailData['In-Reply-To'] 	= 		AccountEmailLog::getLastMessageIDByTicketID($this->TicketID);
		$emailData['TicketID'] 		= 		$this->TicketID;
		$emailData['Message-ID']	= 		$this->TicketID;
		$emailData['Auto-Submitted']= 		"auto-generated";
		$emailData['AttachmentPaths']	= 	unserialize($this->TicketData->AttachmentPaths);
		$status 					= 		sendMail($finalBody,$emailData,0);
		$emailData['EmailParent']	=		0;
		
		if($status['status']){
			email_log_data_Ticket($emailData,'',$status);						
		}else{
			$this->SetError($status['message']);
		}	
	}
	
	protected function AgentClosestheTicket(){
		$this->slug					=		"AgentClosestheTicket";
		Log::info('AgentClosestheTicket');
		if(!$this->CheckBasicRequirments())
		{
			return $this->Error;
		}
		
		$replace_array				= 		$this->ReplaceArray($this->TicketData);
		$finalBody 					= 		$this->template_var_replace($this->EmailTemplate->TemplateBody,$replace_array);
		$finalSubject				= 		$this->template_var_replace($this->EmailTemplate->Subject,$replace_array);	
		$emailData['Subject']		=		$finalSubject;
		$emailData['Message'] 		= 		$finalBody;
		$emailData['CompanyID'] 	= 		User::get_companyID();
		$emailData['EmailTo'] 		= 		$this->TicketData->Requester;
		$emailData['EmailFrom'] 	= 		$this->Group->GroupReplyAddress;
		$emailData['CompanyName'] 	= 		$this->Group->GroupName;
		$emailData['In-Reply-To'] 	= 		AccountEmailLog::getLastMessageIDByTicketID($this->TicketID);
		$emailData['TicketID'] 		= 		$this->TicketID;
		$emailData['Message-ID']	= 		$this->TicketID;
		$emailData['Auto-Submitted']= 		"auto-generated";
		$status 					= 		sendMail($finalBody,$emailData,0);
		$emailData['EmailParent']	=		0;
		if($status['status']){
			email_log_data_Ticket($emailData,'',$status);						
		}else{
			$this->SetError($status['message']);
		}	
	}
	
	protected function SetError($error){
		$this->Error = $error;
		Log::info("Ticket Email error: ".$error);
	}
	public function GetError(){
		return $this->Error;
	}
	
	protected function CheckBasicRequirments(){
				
		if(!isset($this->TicketData->Agent)){
			//$this->SetError("No Agent Found");				
		}
		else
		{
			$agent =  User::find($this->TicketData->Agent);
			if(!$agent)
			{
			//	$this->SetError("Invalid Agent");					
			}
			$this->Agent = $agent;				
		}
		
		if(!isset($this->EmailFrom) || empty($this->EmailFrom))
		{
			if(!isset($this->TicketData->Group))
			{
				$this->SetError("No group Found");		
				
			}
			else
			{
				$group =  TicketGroups::find($this->TicketData->Group);
				if(!$group)
				{
					$this->SetError("Invalid Group");						
				}
				$this->Group = $group;
			}
		}
		else
		{
			$group  = 	TicketGroups::where(["GroupReplyAddress"=>$this->EmailFrom])->first();
			if(!$group)
			{
				$this->SetError("Invalid Group");				
			}
			$this->Group = $group;
		}

		$account=Account::find($this->TicketData->AccountID);
		$LanguageID=$this->getLanguageID($account);
		$this->EmailTemplate  		=		EmailTemplate::getSystemEmailTemplate(User::get_companyID(), $this->slug, $LanguageID);
		if(!$this->EmailTemplate){
			$this->SetError("No email template found.");				
		}
		if($this->EmailTemplate->Status<1){
			$this->SetError("Email template status disabled");				
		}
		
		$this->TicketEmailData = AccountEmailLog::where(['AccountEmailLogID'=>$this->TicketData->AccountEmailLogID])->first();
		
		if($this->GetError()){
			return false;
		}		
		return true;
	}

	/* Ticket New Email */
	protected function CCEmailTicketCreated(){

		$emailto					=		array();
		$this->slug					=		"CCEmailTicketCreated";
		Log::info('CCEmailTicketCreated');
		/*
		if(!$this->CheckBasicRequirments())
		{
			return $this->Error;
		}
		*/
		//log::info(print_r($this->TicketData,true));
		if(isset($this->EmailSenderTo) && !empty($this->EmailSenderTo)){
			$emailto = explode(",",$this->EmailSenderTo);
		}else{
			return;
		}
		log::info("--email to--");
		//log::info(print_r($emailto,true));
		$CompanyID = User::get_companyID();
		$CompanyName = Company::getName($CompanyID);



		if(count($emailto)>0){

			$emailto = self::remove_group_emails_from_array($CompanyID,$emailto);

			$replace_array				= 		$this->ReplaceArray($this->TicketData);
			$finalBody 					= 		$this->template_var_replace($this->TicketData->Description,$replace_array);
			$finalSubject				= 		$this->template_var_replace($this->TicketData->Subject,$replace_array);
			$emailData['Subject']		=		$finalSubject;
			$emailData['Message'] 		= 		$finalBody;
			$emailData['CompanyID'] 	= 		User::get_companyID();
			$emailData['EmailTo'] 		= 		$emailto;
			if(isset($this->TicketData->RequesterCC) && !empty($this->TicketData->RequesterCC)){
				$emailcc = explode(",",$this->TicketData->RequesterCC);

				$emailcc = self::remove_group_emails_from_array($CompanyID,$emailcc);


				$emailData['cc'] 		= 		$emailcc;
			}
			$emailData['EmailFrom'] 	= 		$this->EmailSenderFrom;
			$emailData['CompanyName'] 	= 		isset($this->Group->GroupName)? $this->Group->GroupName:$CompanyName ;
			$emailData['In-Reply-To'] 	= 		AccountEmailLog::getLastMessageIDByTicketID($this->TicketID);
			$emailData['TicketID'] 		= 		$this->TicketID;
			$emailData['Auto-Submitted']= 		"auto-generated";
			$emailData['Message-ID']	= 		$this->TicketID;
			$emailData['AttachmentPaths']	= 	unserialize($this->TicketData->AttachmentPaths);
			$status 					= 		sendMail($finalBody,$emailData,0);
			if($status['status']){
				email_log_data_Ticket($emailData,'',$status);
			}else{
				$this->SetError($status['message']);
			}
		}
	}

	public static function remove_group_emails_from_array($CompanyID,$email_array = array()) {

		$GroupEmailAddress 	=	TicketGroups::where(array("CompanyID"=>$CompanyID,"GroupEmailStatus"=>1))->get(["GroupReplyAddress","GroupEmailAddress"])->toArray();

		$group_emails = [];
		foreach($GroupEmailAddress as $GEmailAddress){
			$group_emails[]  = $GEmailAddress["GroupEmailAddress"];
			$group_emails[]  = $GEmailAddress["GroupReplyAddress"];
		}

		//$GroupEmailAddress 	=	TicketGroups::get(["GroupEmailAddress"])->lists('GroupEmailAddress');

		return array_diff((array) $email_array, $group_emails);

	}

	public function getLanguageID($arrAccourntData){
		$LanguageID = Translation::$default_lang_id;

		if(!empty($arrAccourntData) && !empty($arrAccourntData->LanguageID) ) {
			$LanguageID = $arrAccourntData->LanguageID;
		}else if( !empty( $this->TicketData->Group )){
			$data = TicketGroups::find($this->TicketData->Group);
			if(!empty($data)){
				$LanguageID = $data->LanguageID;
			}
		}

		return $LanguageID;
	}

	protected function AgentReplay()
	{
		Log::info('AgentReplay');

		$replace_array				= 		$this->ReplaceArray($this->TicketData);
		$finalBody 					= 		$this->template_var_replace($this->arrOtherData['Message'],$replace_array);
		$finalSubject				= 		$this->template_var_replace($this->arrOtherData['Subject'],$replace_array);
		$email_from_data   			=  		TicketGroups::where(["GroupReplyAddress"=>$this->arrOtherData['EmailFrom']])->select( 'GroupReplyAddress','GroupName')->get();

		$emailData					=		json_decode(json_encode($this->arrOtherData), true);
		$emailData['Subject']		=		$finalSubject;
		$emailData['Message'] 		= 		$finalBody;
		$emailData['CompanyID'] 	= 		$this->CompanyID;
		$emailData['CompanyName'] 	=		isset($email_from_data[0])?$email_from_data[0]->GroupName:Company::getName($this->CompanyID);
		$emailData['In-Reply-To'] 	= 		AccountEmailLog::getLastMessageIDByTicketID($this->TicketID);
		$emailData['TicketID'] 		= 		$this->TicketID;
		$emailData['Message-ID']	= 		$this->TicketID;
		$emailData['Auto-Submitted']= 		"auto-generated";

		$AttachmentPaths			=		array();
		if(array_key_exists('AttachmentPaths', $this->arrOtherData)){
			$AttachmentPaths 	= 		$this->arrOtherData['AttachmentPaths'];
		}

		$emailData['AttachmentPaths']	= 	$AttachmentPaths;
		$status 					= 		sendMail($finalBody,$emailData,0);

		if($status['status']){
			$logid = email_log_data_Ticket($emailData,'',$status);
			AccountEmailLog::find($logid)->update(["EmailParent"=>$logid]);
		}else{
			$this->SetError($status['message']);
		}
	}
}
?>