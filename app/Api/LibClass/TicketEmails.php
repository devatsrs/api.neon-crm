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
			$replace_array['RequesterName'] 	 = 		$Ticketdata->RequesterName;
			$replace_array['Status'] 			 = 		isset($Ticketdata->Status)?TicketsTable::getTicketStatusByID($Ticketdata->Status):TicketsTable::getDefaultStatus();
			$replace_array['Priority']	 		 = 		TicketPriority::getPriorityStatusByID($Ticketdata->Priority);
			$replace_array['Description'] 	 	 = 		$Ticketdata->Description;
			$replace_array['Group'] 			 = 		isset($Ticketdata->Group)?TicketGroups::where(['GroupID'=>$Ticketdata->Group])->pluck("GroupName"):'';
			$replace_array['Type'] 				 = 		isset($Ticketdata->Type)?TicketsTable::getTicketTypeByID($Ticketdata->Type):'';
			$replace_array['Date']				 = 		$Ticketdata->created_at;
			$replace_array['helpdesk_name']		 = 		isset($Ticketdata->Group)?TicketGroups::where(['GroupID'=>$Ticketdata->Group])->pluck("GroupName"):'';
			$replace_array['Comment']			 =		$this->Comment;
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
			'{{Notebody}}',
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
			
		];
	
		foreach($extra as $item){
			$item_name = str_replace(array('{','}'),array('',''),$item);
			if(array_key_exists($item_name,$replace_array)) {
				$EmailMessage = str_replace($item,$replace_array[$item_name],$EmailMessage);
			}
		}
		return $EmailMessage;
	} 
	
	protected function SetBasicFields($array){
		
			$array_data									=		array();
			$CompanyData								=		Company::find($this->CompanyID);
			$site_url 									= 		CompanyConfiguration::get("WEB_URL");
			$array_data['CompanyName']					=   	$CompanyData->CompanyName;
			$array_data['CompanyVAT']					=   	$CompanyData->VAT;			
			$array_data['CompanyAddress1']				=   	$CompanyData->Address1;
			$array_data['CompanyAddress2']				=   	$CompanyData->Address1;
			$array_data['CompanyAddress3']				=   	$CompanyData->Address1;
			$array_data['CompanyCity']					=   	$CompanyData->City;
			$array_data['CompanyPostCode']				=   	$CompanyData->PostCode;
			$array_data['CompanyCountry']				=   	$CompanyData->Country;
			$array_data['TicketUrl']					=   	$site_url."/tickets/".$this->TicketID."/detail";	
			$array_data['TicketCustomerUrl']			=   	$site_url."/customer/tickets/".$this->TicketID."/detail";	
			$array_data['Group']						=   	$this->Group->GroupName;
			$array_data['AgentName']					=   	$this->Agent->FirstName.' '.$this->Agent->LastName;
			$array_data['AgentEmail']					=   	$this->Agent->EmailAddress;		
			return array_merge($array_data,$array);
	}
	
	protected function AgentNewTicketCreated(){
		
			$this->slug					=		"AgentNewTicketCreated";
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
            $emailData['EmailFrom'] 	= 		$this->Group->GroupEmailAddress;
            $emailData['CompanyName'] 	= 		$this->Group->GroupName;
			$emailData['In-Reply-To'] 	= 		isset($this->Group->GroupReplyAddress)?$this->Group->GroupReplyAddress:$this->Group->GroupEmailAddress;
			$emailData['TicketID'] 		= 		$this->TicketID;
			$status 					= 		sendMail($finalBody,$emailData,0);
			
			if($status['status']){
				email_log_data_Ticket($emailData,'',$status);						
			}else{
				$this->SetError($status['message']);
			}			
	}
	
		protected function Noteaddedtoticket(){
			$this->slug					=		"Noteaddedtoticket";
			
			if(!$this->CheckBasicRequirments())
			{
				return $this->Error;
			}
			
			$NoteData					=		Note::where(['TicketID'=>$this->TicketID])->orderBy("NoteID", "desc")->first();
			if(!$NoteData){
				$this->SetError("No Note added");
				return;
			}
			
		 	$replace_array				= 		$this->ReplaceArray($this->TicketData);
		    $finalBody 					= 		$this->template_var_replace($this->EmailTemplate->TemplateBody,$replace_array);
			$finalBody 					= 		str_replace('{{Notebody}}',$NoteData->Note,$finalBody);			
			$finalSubject				= 		$this->template_var_replace($this->EmailTemplate->Subject,$replace_array);	
            $emailData['Subject']		=		$finalSubject;
            $emailData['Message'] 		= 		$finalBody;
            $emailData['CompanyID'] 	= 		User::get_companyID();
            $emailData['EmailTo'] 		= 		$this->Agent->EmailAddress;
            $emailData['EmailFrom'] 	= 		$this->Group->GroupEmailAddress;
            $emailData['CompanyName'] 	= 		$this->Group->GroupName;
			$emailData['In-Reply-To'] 	= 		isset($this->Group->GroupReplyAddress)?$this->Group->GroupReplyAddress:$this->Group->GroupEmailAddress;
			$emailData['TicketID'] 		= 		$this->TicketID;
			$status 					= 		sendMail($finalBody,$emailData,0);
			
			if($status['status']){
				email_log_data_Ticket($emailData,'',$status);						
			}else{
				$this->SetError($status['message']);
			}
	}
	
	protected function  RequesterNewTicketCreated(){
		$this->slug					=		"RequesterNewTicketCreated";
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
		$emailData['EmailFrom'] 	= 		$this->Group->GroupEmailAddress;
		$emailData['CompanyName'] 	= 		$this->Group->GroupName;
		$emailData['In-Reply-To'] 	= 		isset($this->Group->GroupReplyAddress)?$this->Group->GroupReplyAddress:$this->Group->GroupEmailAddress;
		$emailData['TicketID'] 		= 		$this->TicketID;
		$status 					= 		sendMail($finalBody,$emailData,0);
		
		if($status['status']){
			email_log_data_Ticket($emailData,'',$status);						
		}else{
			$this->SetError($status['message']);
		}		
	}
	
	protected function AgentAddsCommenttoTicket(){
		
			$this->slug					=		"AgentAddsCommenttoTicket";
			
			if(!$this->CheckBasicRequirments())
			{
				return $this->Error;
			}
			
			$NoteData					=		Note::where(['TicketID'=>$this->TicketID])->orderBy("NoteID", "desc")->first();
			if(!$NoteData){
				$this->SetError("No Note added");
				return;
			}
			
		 	$replace_array				= 		$this->ReplaceArray($this->TicketData);
		    $finalBody 					= 		$this->template_var_replace($this->EmailTemplate->TemplateBody,$replace_array);
			$finalBody 					= 		str_replace('{{Notebody}}',$NoteData->Note,$finalBody);			
			$finalSubject				= 		$this->template_var_replace($this->EmailTemplate->Subject,$replace_array);	
            $emailData['Subject']		=		$finalSubject;
            $emailData['Message'] 		= 		$finalBody;
            $emailData['CompanyID'] 	= 		User::get_companyID();
            $emailData['EmailTo'] 		= 		$this->TicketData->Requester;
            $emailData['EmailFrom'] 	= 		$this->Group->GroupEmailAddress;
            $emailData['CompanyName'] 	= 		$this->Group->GroupName;
			$emailData['In-Reply-To'] 	= 		isset($this->Group->GroupReplyAddress)?$this->Group->GroupReplyAddress:$this->Group->GroupEmailAddress;
			$emailData['TicketID'] 		= 		$this->TicketID;
			$status 					= 		sendMail($finalBody,$emailData,0);
			
			if($status['status']){
				email_log_data_Ticket($emailData,'',$status);						
			}else{
				$this->SetError($status['message']);
			}
	}
	protected function CCNewTicketCreated(){
			
		$emailto					=		array();
		$this->slug					=		"CCNewTicketCreated";
		
		if(!$this->CheckBasicRequirments())
		{
			return $this->Error;
		} 
		if(isset($this->TicketEmailData->Cc) && !empty($this->TicketEmailData->Cc)){
			$emailto = explode(",",$this->TicketEmailData->Cc);
		}else{
			return;
		}	
			
		if(count($emailto)>0){
			$replace_array				= 		$this->ReplaceArray($this->TicketData);
			$finalBody 					= 		$this->template_var_replace($this->EmailTemplate->TemplateBody,$replace_array);
			$finalSubject				= 		$this->template_var_replace($this->EmailTemplate->Subject,$replace_array);	
			$emailData['Subject']		=		$finalSubject;
			$emailData['Message'] 		= 		$finalBody;
			$emailData['CompanyID'] 	= 		User::get_companyID();
			$emailData['EmailTo'] 		= 		$emailto;
			$emailData['EmailFrom'] 	= 		$this->Group->GroupEmailAddress;
			$emailData['CompanyName'] 	= 		$this->Group->GroupName;
			$emailData['In-Reply-To'] 	= 		isset($this->Group->GroupReplyAddress)?$this->Group->GroupReplyAddress:$this->Group->GroupEmailAddress;
			$emailData['TicketID'] 		= 		$this->TicketID;
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
		
		if(!$this->CheckBasicRequirments())
		{
			return $this->Error;
		} 
		if(isset($this->TicketEmailData->Cc) && !empty($this->TicketEmailData->Cc)){
			$emailto = explode(",",$this->TicketEmailData->Cc);
		}else{
			return;
		}	
			
		if(count($emailto)>0){
			$replace_array				= 		$this->ReplaceArray($this->TicketData);
			$finalBody 					= 		$this->template_var_replace($this->EmailTemplate->TemplateBody,$replace_array);
			$finalSubject				= 		$this->template_var_replace($this->EmailTemplate->Subject,$replace_array);	
			$emailData['Subject']		=		$finalSubject;
			$emailData['Message'] 		= 		$finalBody;
			$emailData['CompanyID'] 	= 		User::get_companyID();
			$emailData['EmailTo'] 		= 		$emailto;
			$emailData['EmailFrom'] 	= 		$this->Group->GroupEmailAddress;
			$emailData['CompanyName'] 	= 		$this->Group->GroupName;
			$emailData['In-Reply-To'] 	= 		isset($this->Group->GroupReplyAddress)?$this->Group->GroupReplyAddress:$this->Group->GroupEmailAddress;
			$emailData['TicketID'] 		= 		$this->TicketID;
			$status 					= 		sendMail($finalBody,$emailData,0);

			if($status['status']){
				email_log_data_Ticket($emailData,'',$status);						
			}else{
				$this->SetError($status['message']);
			}
		}
	}
	
	
	protected function TicketAssignedtoAgent(){
		
			$this->slug					=		"TicketAssignedtoAgent";
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
            $emailData['EmailFrom'] 	= 		$this->Group->GroupEmailAddress;
            $emailData['CompanyName'] 	= 		$this->Group->GroupName;
			$emailData['In-Reply-To'] 	= 		isset($this->Group->GroupReplyAddress)?$this->Group->GroupReplyAddress:$this->Group->GroupEmailAddress;
			$status 					= 		sendMail($finalBody,$emailData,0);
			$emailData['TicketID'] 		= 		$this->TicketID;
			
			if($status['status']){
				email_log_data_Ticket($emailData,'',$status);						
			}else{
				$this->SetError($status['message']);
			}			
	}
	
	protected function AgentAssignedGroup(){
			$slug					=		"AgentAssignedGroup";
			
			if(!$this->CheckBasicRequirments())
			{
				return $this->Error;
			}			
			
			$this->EmailTemplate  		=		EmailTemplate::where(["SystemType"=>$slug])->first();									
		 	$replace_array				= 		$this->ReplaceArray($this->TicketData);
		    $finalBody 					= 		$this->template_var_replace($this->EmailTemplate->TemplateBody,$replace_array);
			$finalSubject				= 		$this->template_var_replace($this->EmailTemplate->Subject,$replace_array);				
			$Groupagents 				= 		TicketGroupAgents::get_group_agents($this->TicketData->Group,0,'EmailAddress');
			$emailData['Subject']		=		$finalSubject;
            $emailData['Message'] 		= 		$finalBody;
            $emailData['CompanyID'] 	= 		User::get_companyID();
            $emailData['EmailTo'] 		= 		$Groupagents;
            $emailData['EmailFrom'] 	= 		$this->Group->GroupEmailAddress;
            $emailData['CompanyName'] 	= 		$this->Group->GroupName;
			$emailData['In-Reply-To'] 	= 		isset($this->Group->GroupReplyAddress)?$this->Group->GroupReplyAddress:$this->Group->GroupEmailAddress;
			$status 					= 		sendMail($finalBody,$emailData,0);
			$emailData['TicketID'] 		= 		$this->TicketID;
			$emailData['UserID']		=		User::get_userID();			
			
			if($status['status']){
				email_log_data_Ticket($emailData,'',$status);						
			}else{
				$this->SetError($status['message']);
			}			
	}
	
	protected function AgentSolvestheTicket(){
		$this->slug					=		"AgentSolvestheTicket";
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
		$emailData['EmailFrom'] 	= 		$this->Group->GroupEmailAddress;
		$emailData['CompanyName'] 	= 		$this->Group->GroupName;
		$emailData['In-Reply-To'] 	= 		isset($this->Group->GroupReplyAddress)?$this->Group->GroupReplyAddress:$this->Group->GroupEmailAddress;
		$emailData['TicketID'] 		= 		$this->TicketID;
		$status 					= 		sendMail($finalBody,$emailData,0);
		$emailData['EmailParent']	=		$this->TicketData->AccountEmailLogID;
		
		if($status['status']){
			email_log_data_Ticket($emailData,'',$status);						
		}else{
			$this->SetError($status['message']);
		}	
	}
	
	protected function AgentClosestheTicket(){
		$this->slug					=		"AgentClosestheTicket";
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
		$emailData['EmailFrom'] 	= 		$this->Group->GroupEmailAddress;
		$emailData['CompanyName'] 	= 		$this->Group->GroupName;
		$emailData['In-Reply-To'] 	= 		isset($this->Group->GroupReplyAddress)?$this->Group->GroupReplyAddress:$this->Group->GroupEmailAddress;
		$emailData['TicketID'] 		= 		$this->TicketID;
		$status 					= 		sendMail($finalBody,$emailData,0);
		$emailData['EmailParent']	=		$this->TicketData->AccountEmailLogID;
		if($status['status']){
			email_log_data_Ticket($emailData,'',$status);						
		}else{
			$this->SetError($status['message']);
		}	
	}
	
	protected function SetError($error){
		$this->Error = $error;
	}
	public function GetError(){
		return $this->Error;
	}
	
	protected function CheckBasicRequirments(){
				
		if(!isset($this->TicketData->Agent)){
			$this->SetError("No Agent Found");				
		}
		else
		{
			$agent =  User::find($this->TicketData->Agent);
			if(!$agent)
			{
				$this->SetError("Invalid Agent");					
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
			$group  = 	TicketGroups::where(["GroupEmailAddress"=>$this->EmailFrom])->first();
			if(!$group)
			{
				$this->SetError("Invalid Group");				
			}
			$this->Group = $group;
		}
		
		$this->EmailTemplate  		=		EmailTemplate::where(["SystemType"=>$this->slug])->first();									
		if(!$this->EmailTemplate){
			$this->SetError("No email template found.");				
		}
		if(!$this->EmailTemplate->Status){
			$this->SetError("Email template status disabled");				
		}
		
		$this->TicketEmailData = AccountEmailLog::where(['AccountEmailLogID'=>$this->TicketData->AccountEmailLogID])->first();
		
		if($this->GetError()){
			return false;
		}		
		return true;
	}
	
	
}
?>