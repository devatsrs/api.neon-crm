<?php
namespace Api\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Input;
use \App\Imap;

class TicketsTable extends \Eloquent 
{
    protected $guarded = array("TicketID");

    protected $table = 'tblTickets';

    protected $primaryKey = "TicketID";
	
    static  $FreshdeskTicket  		= 	2;
    static  $SystemTicket 			= 	1;
	const   TICKET					=	0;
	const   EMAIL					=	1;
	const   TICKETGLOBALACCESS		=	1;
	const   TICKETGROUPACCESS		=	2;
	const   TICKETRESTRICTEDACCESS	=	3;
	static  $defaultSortField 		= 	'created_at';
	static  $defaultSortType 		= 	'desc';
	static  $Sortcolumns			=	array("created_at"=>"Date Created","subject"=>"Subject","status"=>"Status","group"=>"Group","updated_at"=>"Last Modified");
	
	static function GetAgentSubmitRules($page='all'){
		 $rules 	 =  array();
		 $messages	 =  array();
		 $fields 	 = 	Ticketfields::where(['AgentReqSubmit'=>1])->get();
		 
		foreach($fields as $fieldsdata)	 
		{	
			if($page=='DetailPage' && ($fieldsdata->FieldType=='default_requester' || $fieldsdata->FieldType=='default_subject' || $fieldsdata->FieldType=='default_description')){continue;}
			
			$rules[$fieldsdata->FieldType] = 'required';
			$messages[$fieldsdata->FieldType.".required"] = "The ".$fieldsdata->AgentLabel." field is required";
		}
		
		return array("rules"=>$rules,"messages"=>$messages);
	}
	
	static function GetCustomerSubmitRules($page='all'){
		 $rules 	 =  array();
		 $messages	 =  array();
		 $fields 	 = 	Ticketfields::where(['CustomerReqSubmit'=>1])->get();
		 
		foreach($fields as $fieldsdata)	 
		{	
			if($page=='DetailPage' && ($fieldsdata->FieldType=='default_requester' || $fieldsdata->FieldType=='default_subject' || $fieldsdata->FieldType=='default_description' || $fieldsdata->FieldType=='default_group')){continue;}
			
			if(($fieldsdata->FieldType=='default_requester'  || $fieldsdata->FieldType=='default_group' || $fieldsdata->FieldType=='default_agent' )){continue;}
			
			$rules[$fieldsdata->FieldType] = 'required';
			$messages[$fieldsdata->FieldType.".required"] = "The ".$fieldsdata->AgentLabel." field is required";
		}
		
		return array("rules"=>$rules,"messages"=>$messages);
	}
	
	static function GetAgentSubmitComposeRules(){
		
		 $rules 	 =  array();
		 $messages	 =  array();
		 $fields 	 = 	Ticketfields::where(['AgentReqSubmit'=>1])->get();
		
		 
		foreach($fields as $fieldsdata)	 
		{
			if(($fieldsdata->FieldType=='default_requester'  || $fieldsdata->FieldType=='default_group' || $fieldsdata->FieldType=='default_agent' || $fieldsdata->FieldType=='default_subject' || $fieldsdata->FieldType=='default_description' )){continue;}
		
			$rules[$fieldsdata->FieldType] = 'required';
			$messages[$fieldsdata->FieldType.".required"] = "The ".$fieldsdata->AgentLabel." field is required";
		}
		
		return array("rules"=>$rules,"messages"=>$messages);
	
	}
	
	static function getClosedTicketStatus(){
		//TicketfieldsValues::WHERE
		 $ValuesID =  TicketfieldsValues::join('tblTicketfields','tblTicketfields.TicketFieldsID','=','tblTicketfieldsValues.FieldsID')
            ->where(['tblTicketfields.FieldType'=>Ticketfields::TICKET_SYSTEM_STATUS_FLD])->where(['tblTicketfieldsValues.FieldValueAgent'=>TicketfieldsValues::$Status_Closed])->pluck('ValuesID');			
			return $ValuesID;
	}
	
	static function getResolvedTicketStatus(){
		//TicketfieldsValues::WHERE
		 $ValuesID =  TicketfieldsValues::join('tblTicketfields','tblTicketfields.TicketFieldsID','=','tblTicketfieldsValues.FieldsID')
            ->where(['tblTicketfields.FieldType'=>Ticketfields::TICKET_SYSTEM_STATUS_FLD])->where(['tblTicketfieldsValues.FieldValueAgent'=>TicketfieldsValues::$Status_Resolved])->pluck('ValuesID');			
			return $ValuesID;
	}
	
	static function getTicketStatusByID($id,$fld='FieldValueAgent'){
		//TicketfieldsValues::WHERE
		 $ValuesID =  TicketfieldsValues::join('tblTicketfields','tblTicketfields.TicketFieldsID','=','tblTicketfieldsValues.FieldsID')
            ->where(['tblTicketfields.FieldType'=>Ticketfields::TICKET_SYSTEM_STATUS_FLD])->where(['tblTicketfieldsValues.ValuesID'=>$id])->pluck($fld);			
			return $ValuesID;
	}
	
	
	static function getTicketStatus(){
		//TicketfieldsValues::WHERE
		 $row =  TicketfieldsValues::join('tblTicketfields','tblTicketfields.TicketFieldsID','=','tblTicketfieldsValues.FieldsID')->select(array('FieldValueAgent', 'ValuesID'))->where(['tblTicketfields.FieldType'=>Ticketfields::TICKET_SYSTEM_STATUS_FLD])->lists('FieldValueAgent','ValuesID');		
			 if(!empty($row)){
				$row =  array("0"=> "Select")+json_decode(json_encode($row),true);
			}	
			return $row;
	}
	
	
	static function getTicketType(){
		//TicketfieldsValues::WHERE
		 $row =  TicketfieldsValues::join('tblTicketfields','tblTicketfields.TicketFieldsID','=','tblTicketfieldsValues.FieldsID')
            ->where(['tblTicketfields.FieldType'=>Ticketfields::TICKET_SYSTEM_TYPE_FLD])->lists('FieldValueAgent','ValuesID');
			$row = array("0"=> "Select")+$row;
			return $row;
	}
	
	static function SetUpdateValues($TicketData,$ticketdetaildata,$Ticketfields){
			//$TicketData  = '';
			$data = array();
			
			foreach($Ticketfields as $TicketfieldsData)
			{	 
				if(in_array($TicketfieldsData->FieldType,Ticketfields::$staticfields))
				{		
					if($TicketfieldsData->FieldType=='default_requester')
					{ 			
						$data[$TicketfieldsData->FieldType] = $TicketData->RequesterName." <".$TicketData->Requester.">";
					}
					
					if($TicketfieldsData->FieldType=='default_subject')
					{
						$data[$TicketfieldsData->FieldType] = $TicketData->Subject;
					}
					
					if($TicketfieldsData->FieldType=='default_ticket_type')
					{
						$data[$TicketfieldsData->FieldType] = $TicketData->Type;
					}
					
					if($TicketfieldsData->FieldType=='default_status')
					{
						$data[$TicketfieldsData->FieldType] = $TicketData->Status;
					}	
					
					if($TicketfieldsData->FieldType=='default_status')
					{
						$data[$TicketfieldsData->FieldType] = $TicketData->Status;
					}
					
					if($TicketfieldsData->FieldType=='default_priority')
					{
						$data[$TicketfieldsData->FieldType] = $TicketData->Priority;
					}
					
					if($TicketfieldsData->FieldType=='default_group')
					{
						$data[$TicketfieldsData->FieldType] = $TicketData->Group;
					}
					
					if($TicketfieldsData->FieldType=='default_agent')
					{
						$data[$TicketfieldsData->FieldType] = $TicketData->Agent;
					}
					
					if($TicketfieldsData->FieldType=='default_description')
					{
						$data[$TicketfieldsData->FieldType] = $TicketData->Description;
					}
				}else{
					$found = 0;
					foreach($ticketdetaildata as $ticketdetail){						
						if($TicketfieldsData->TicketFieldsID == $ticketdetail->FieldID){
							$data[$TicketfieldsData->FieldType] = $ticketdetail->FieldValue;
							$found=1;
							break;
						}
					}
					if($found==0){					
						if(($TicketfieldsData->FieldHtmlType == Ticketfields::FIELD_HTML_TEXT) || ($TicketfieldsData->FieldHtmlType == Ticketfields::FIELD_HTML_TEXTAREA) || ($TicketfieldsData->FieldHtmlType == Ticketfields::FIELD_HTML_DATE)){
							$data[$TicketfieldsData->FieldType] =  '';
						}else{
							$data[$TicketfieldsData->FieldType] =  0;
						}
					}
						
				}
				
			}
			
			$data['AttachmentPaths']  = 	 $TicketData->AttachmentPaths;	
			//Log::info(print_r($data,true));	
			return $data;
	}
	
	 
	static function CheckTicketLicense(){
		return true;
		//return false;
	}
	
		static function getDefaultStatus(){			
		 $ValuesID =  TicketfieldsValues::join('tblTicketfields','tblTicketfields.TicketFieldsID','=','tblTicketfieldsValues.FieldsID')
            ->where(['tblTicketfields.FieldType'=>Ticketfields::TICKET_SYSTEM_STATUS_FLD])->where(['tblTicketfieldsValues.FieldValueAgent'=>Ticketfields::TICKET_SYSTEM_STATUS_DEFAULT,'tblTicketfieldsValues.FieldType'=>Ticketfields::FIELD_TYPE_STATIC])->pluck('ValuesID');			
			return $ValuesID;	
	}
	
	static function CheckTicketAccount($id){
		$data 		 	= 	Input::all(); 
		$TicketsData 	=	TicketsTable::find($id);	
		$accountID 		= 	0;
		
		if($TicketsData)
		{
			$accountID =  Account::where(array("Email"=>$TicketsData->Requester))->orWhere(array("BillingEmail"=>$TicketsData->Requester))->pluck('AccountID');
			if(!$accountID)
			{
				if(isset($data['LoginType']) && $data['LoginType']=='customer')
				{
					$accountID = User::get_userID();		
				}
			}
			if(!$accountID)
			{
				$accountID =0;
			}
		}
		return $accountID;

	}
	
	static function getTicketTypeByID($id,$fld='FieldValueAgent'){
		//TicketfieldsValues::WHERE
			$ValuesID =  TicketfieldsValues::join('tblTicketfields','tblTicketfields.TicketFieldsID','=','tblTicketfieldsValues.FieldsID')
            ->where(['tblTicketfields.FieldType'=>Ticketfields::TICKET_SYSTEM_TYPE_FLD])->where(['tblTicketfieldsValues.ValuesID'=>$id])->pluck($fld);			
			return $ValuesID;
	}
	static function	SetEmailType($email)
	{
		$final	=	 array();
		$imap				  =		 new Imap();
		$MatchArray  		  =      $imap->findEmailAddress($email);
		
		if(count($MatchArray)>0){
			if($MatchArray['MatchType']=='Contact'){
				$final = array("ContactID"=>$MatchArray['MatchID'],"AccountID"=>0,"UserID"=>0);
			}
			
			if($MatchArray['MatchType']=='Account' || $MatchArray['MatchType']=='Lead'){
				$final = array("ContactID"=>0,"AccountID"=>$MatchArray['MatchID'],"UserID"=>0);
			}
			
			if($MatchArray['MatchType']=='User'){
				$final = array("ContactID"=>0,"AccountID"=>0,"UserID"=>$MatchArray['MatchID']);
			}
		}
		return $final;
	}
	
	static function GetConversation($ticket_number){
		$Ticketconversation = '';
		$ticketdata 	 =  TicketsTable::find($ticket_number);
		$allConversation = 	AccountEmailLog::WhereRaw("EmailParent >0")->where(['TicketID'=>$ticket_number])->orderBy('AccountEmailLogID', 'DESC')->get();
		
		/*if($ticketdata->AccountEmailLogID){
			$Ticketconversation = '';
		}else{
			$Ticketconversation = $ticketdata->Description."<br><hr><br>";
		}*/
		foreach($allConversation as $allConversationData){
			Log::info("Message:".$allConversationData->Message);
			$Ticketconversation .= $allConversationData->Message."<br><hr><br>";	
		} 
		return $Ticketconversation;
	}
	
	static function filterEmailAddressFromName($emails){
		$final = array();
		if(!is_array($emails)){
			$emails = explode(",",$emails);
		}
		foreach($emails as $emailsData){
				
			if (strpos($emailsData, '<') !== false && strpos($emailsData, '>') !== false)
			{
				$RequesterData 	   =  explode(" <",$emailsData);
				$final[] =  substr($RequesterData[1],0,strlen($RequesterData[1])-1);	
			}else{
				$final[]	   =  trim($emailsData);					
			}
		}
		return implode(",",$final);
	}
}