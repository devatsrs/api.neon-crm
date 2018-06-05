<?php

namespace Api\Controllers;

use Api\Model\TicketfieldsValues;
use Api\Model\TicketLog;
use Api\Model\TicketSla;
use Dingo\Api\Http\Request;
use Api\Model\AccountBalance;
use Api\Model\AccountBalanceHistory;
use Api\Model\DataTableSql;
use Api\Model\User;
use Api\Model\Account;
use Api\Model\Ticket;
use Api\Model\TicketsTable;
use Api\Model\Ticketfields;
use Api\Model\TicketsDetails;
use Api\Model\TicketPriority;
use Api\Model\TicketGroups;
use Api\Model\Note;
use Api\Model\AccountEmailLog;
use Api\Model\Contact;
use Api\Model\Messages;
use App\Http\Requests;
use Dingo\Api\Facade\API;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\TicketEmails;
use Api\Model\Company;
use Api\Model\TicketGroupAgents;
use Api\Model\TicketDashboardTimeline;
use \App\Imap;

class TicketsController extends BaseController
{

private $validlicense;	

	public function __construct(Request $request){ 
        $this->middleware('jwt.auth');
        Parent::__Construct($request);
    }
	 

	  function GetResult(){ 
		   $data 					= 	Input::all(); 
		   $CompanyID 				= 	User::get_companyID(); 
		   $search		 			=	isset($data['Search'])?$data['Search']:'';	   		   
		   $status					=	isset($data['status'])?is_array($data['status'])?implode(",",$data['status']):$data['status']:'';		   
		   $priority				=	isset($data['priority'])?is_array($data['priority'])?implode(",",$data['priority']):$data['priority']:'';
		   $Group					=	isset($data['group'])?is_array($data['group'])?implode(",",$data['group']):$data['group']:'';		  
		   $agent					=	isset($data['agent'])?$data['agent']:'';	
		   $DueBy					=	isset($data['DueBy'])?is_array($data['DueBy'])?implode(",",$data['DueBy']):$data['DueBy']:'';		
		   $columns 	 			= 	array('TicketID','Subject','Requester','Type','Status','Priority','Group','Agent','created_at');		
		   $sort_column 			= 	$data['iSortCol_0'];
		   $AccessPermission		=	isset($data['AccessPermission'])?$data['AccessPermission']:0;
		   $data['iDisplayStart']   +=	1;
		   $data['Export']  		=	isset($data['Export'])?$data['Export']:0;
		   
		   if($AccessPermission == TicketsTable::TICKETGLOBALACCESS){
		   	// no restrictions
		   }else if($AccessPermission == TicketsTable::TICKETGROUPACCESS){ //group access
			   	$Group = TicketGroups::Get_User_Groups(User::get_userID());
		   }else if($AccessPermission == TicketsTable::TICKETRESTRICTEDACCESS){ //assigned ticket access
			   	$agent = User::get_userID();
		   }
          if(!empty($status)) {
              $statusArray	= TicketsTable::getTicketStatus(0);
              $tempStatus = explode(',', $status);
              if (in_array(array_search('All UnResolved', $statusArray), $tempStatus)) {
                  unset($statusArray[array_search('Resolved', $statusArray)]);
                  unset($statusArray[array_search('Closed', $statusArray)]);
                  $status = implode(',',array_unique(array_merge($tempStatus,array_keys($statusArray))));
              }
          }
		   if(isset($data['LoginType']) && $data['LoginType']=='customer')
		   {		
				   $agent		=	'';
				   $emails 		=	Account::GetAccountAllEmails(User::get_userID());				 
				   $query 		= 	"call prc_GetSystemTicketCustomer ('".$CompanyID."','".$search."','".$status."','".$priority."','".$Group."','".$agent."','".$emails."',".( ceil($data['iDisplayStart']/$data['iDisplayLength']) )." ,".$data['iDisplayLength'].",'".$sort_column."','".$data['sSortDir_0']."',".$data['Export'].")";  
				 
		   }else
		   {			 	  		   			   
			  	  $query 		= 	"call prc_GetSystemTicket ('".$CompanyID."','".$search."','".$status."','".$priority."','".$Group."','".$agent."','".$DueBy."','".date('Y-m-d H:i')."',".( ceil($data['iDisplayStart']/$data['iDisplayLength']) )." ,".$data['iDisplayLength'].",'".$sort_column."','".$data['sSortDir_0']."',".$data['Export'].")"; 
			} 
					
			$resultdata   	=  DataTableSql::of($query)->getProcResult(array('ResultCurrentPage','TotalResults','GroupsData'));	
			$resultpage  	=  DataTableSql::of($query)->make(false);				
			$groupData = isset($resultdata->data['GroupsData'])?$resultdata->data['GroupsData']:array(); 
						
			if($data['Export'])
			{
				$result = ["resultpage"=>$resultpage,"iTotalRecords"=>$resultdata->iTotalRecords,"iTotalDisplayRecords"=>$resultdata->iTotalDisplayRecords,"ResultCurrentPage"=>$resultdata->data['ResultCurrentPage'],"GroupsData"=>$groupData];
			}
			else
			{
				$result = ["resultpage"=>$resultpage,"iTotalRecords"=>$resultdata->iTotalRecords,"iTotalDisplayRecords"=>$resultdata->iTotalDisplayRecords,"totalcount"=>$resultdata->data['TotalResults'][0]->totalcount,"ResultCurrentPage"=>$resultdata->data['ResultCurrentPage'],"GroupsData"=>$groupData];
			}
			 return generateResponse('success', false, false,$result);
	  }
	  
	  function Store(){
		$data 			= 	Input::all();
		$CompanyID 		= 	User::get_companyID();

		  if(!isset($data['Ticket'])){
			return generateResponse(cus_lang("MESSAGE_PLEASE_SUBMIT_REQUIRED_FIELDS"),true);
		}
		//Log::info(print_r($data,true)); exit;
		
		//$RulesMessages      = 	TicketsTable::GetAgentSubmitRules();       
		if(isset($data['LoginType']) && $data['LoginType']=='customer'){
			$RulesMessages      = 	TicketsTable::GetCustomerSubmitRules();       
		}else{
			$RulesMessages      = 	TicketsTable::GetAgentSubmitRules();       
		}
        $validator 			= 	Validator::make($data['Ticket'], $RulesMessages['rules'], $RulesMessages['messages']);
        if ($validator->fails()) {
			return generateResponse($validator->errors(),true);
        }
		
		
		 $files = 'a:0:{}';
		 if (isset($data['file']) && !empty($data['file'])) {
            $files = serialize(json_decode($data['file'],true));
        }
			
		    $Ticketfields      =  $data['Ticket'];
			
			if (strpos($Ticketfields['default_requester'], '<') !== false && strpos($Ticketfields['default_requester'], '>') !== false)
			{
				$RequesterData 	   =  explode(" <",$Ticketfields['default_requester']);
				$RequesterName	   =  $RequesterData[0];
				$RequesterEmail	   =  substr($RequesterData[1],0,strlen($RequesterData[1])-1);	
			}else{
				$RequesterName	   =  '';
				$RequesterEmail	   =  trim($Ticketfields['default_requester']);					
			}
			
			$email_from 		= 	'';
			$email_from_name 	= 	'';
			if(!isset($Ticketfields['default_group']) || $Ticketfields['default_group']==0){
			 $ticketGroupcount = 	TicketGroups::get()->count();
			 if($ticketGroupcount==1){
			 	$ticketGroupDataSingle = DB::table('tblTicketGroups')->first();
				$Ticketfields['default_group'] = $ticketGroupDataSingle->GroupID;
			 }
			}

			$MatchArray  		  =    TicketsTable::SetEmailType($RequesterEmail);
			
			if(empty($RequesterName)){
					$imap				    =	   new Imap();
					$MatchArrayTitle  		=      $imap->findEmailAddress($RequesterEmail);
					$RequesterName			=		isset($MatchArrayTitle['AccountTitle'])?$MatchArrayTitle['AccountTitle']:'';
			}
			
			if($data['LoginType']=='user')
			{	
				$email_from		   =  TicketGroups::where(["GroupID"=>$Ticketfields['default_group']])->pluck('GroupReplyAddress');
				$email_from_name   =  TicketGroups::where(["GroupID"=>$Ticketfields['default_group']])->pluck('GroupName'); 
				$TicketData = array(
					"CompanyID"=>$CompanyID,
					"Requester"=>$RequesterEmail,
					"RequesterName"=>$RequesterName,
					"RequesterCC"=>TicketsTable::filterEmailAddressFromName($Ticketfields['cc']),
					"Subject"=>$Ticketfields['default_subject'],
					"Type"=>$Ticketfields['default_ticket_type'],
					"Status"=>$Ticketfields['default_status'],
					"Priority"=>$Ticketfields['default_priority'],
					"Group"=>$Ticketfields['default_group'],
					"Agent"=>$Ticketfields['default_agent'],
					"Description"=>$Ticketfields['default_description'],	
					"AttachmentPaths"=>$files,
					"created_at"=>date("Y-m-d H:i:s"),
					"created_by"=>User::get_user_full_name()
				);
			}else{
				$TicketData = array(
					"CompanyID"=>$CompanyID,
					"Requester"=>$RequesterEmail,
					"RequesterName"=>$RequesterName,
					"AccountID"=>$data['TicketAccount'],
					"RequesterCC"=>isset($Ticketfields['cc'])?$Ticketfields['cc']:'',
					"Subject"=>isset($Ticketfields['default_subject'])?$Ticketfields['default_subject']:'',
					"Type"=>isset($Ticketfields['default_ticket_type'])?$Ticketfields['default_ticket_type']:0,
					"Status"=>isset($Ticketfields['default_status'])?$Ticketfields['default_status']:TicketsTable::getDefaultStatus(),
					"Priority"=>isset($Ticketfields['default_priority'])?$Ticketfields['default_priority']:TicketPriority::getDefaultPriorityStatus(),					
					"Description"=>isset($Ticketfields['default_description'])?$Ticketfields['default_description']:'',	 
					"Group"=>isset($Ticketfields['default_group'])?$Ticketfields['default_group']:0,
					"AttachmentPaths"=>$files,
					"created_at"=>date("Y-m-d H:i:s"),
					"created_by"=>User::get_user_full_name()
				);
			}
			unset($Ticketfields['cc']);
			if(!isset($TicketData['AccountID']))
			{
				$TicketData = array_merge($TicketData,$MatchArray);
			}
			
			try{
 			    DB::beginTransaction();
				$TicketID = TicketsTable::insertGetId($TicketData);

				foreach($Ticketfields as $key => $TicketfieldsData)
				{	
					if(!in_array($key,Ticketfields::$staticfields))
					{
						$TicketFieldsID =  Ticketfields::where(["FieldType"=>$key])->pluck('TicketFieldsID');
						TicketsDetails::insert(array("TicketID"=>$TicketID,"FieldID"=>$TicketFieldsID,"FieldValue"=>$TicketfieldsData));
					}
				}

                TicketLog::insertTicketLog($TicketID,TicketLog::TICKET_ACTION_CREATED,($data['LoginType']=='user')?0:1);
				//create contact if email not found in system
			 	$AllEmails  =   Messages::GetAllSystemEmails();
				if(!in_array($RequesterEmail,$AllEmails))
				{
					$ContactData = array("Email"=>$RequesterEmail,"CompanyId"=>$CompanyID);
					$ContactID = Contact::insertGetId($ContactData);
					TicketsTable::find($TicketID)->update(array("ContactID"=>$ContactID));

				}	 
				 $TicketData['email_from']  	= 	$email_from;
				 $TicketData['email_from_name'] = 	$email_from_name;
				 $TicketData['AttachmentPaths'] =   $files;
				/* $logID =  SendTicketEmail('store',$TicketID,$TicketData);
				 TicketsTable::find($TicketID)->update(array("AccountEmailLogID"=>$logID));


				 if(!isset($logID['status'])){
				  	TicketsTable::find($TicketID)->update(array("AccountEmailLogID"=>$logID));
				 }else{
				 	return generateResponse($logID['message'], true, true);
				 }*/

				 if(isset($Ticketfields['default_group']) && $Ticketfields['default_group']>0){
			  	  $TicketEmails 	=  new TicketEmails(array("TicketID"=>$TicketID,"TriggerType"=>array("AgentAssignedGroup")));
				 }

				 if(isset($Ticketfields['default_agent']) && $Ticketfields['default_agent']>0){
				 	 $TicketEmails 	=  new TicketEmails(array("TicketID"=>$TicketID,"TriggerType"=>array("TicketAssignedtoAgent")));
				 }
				  $TicketEmails 	=  new TicketEmails(array("TicketID"=>$TicketID,"TriggerType"=>array("RequesterNewTicketCreated")));
				  $TicketEmails 	=  new TicketEmails(array("TicketID"=>$TicketID,"TriggerType"=>"CCNewTicketCreated"));

				 TicketsTable::CheckTicketStatus('',isset($Ticketfields['default_status'])?$Ticketfields['default_status']:TicketsTable::getDefaultStatus(),$TicketID);
				 DB::commit();
				try {
					TicketSla::assignSlaToTicket($CompanyID,$TicketID);
				}catch (Exception $ex){
					Log::info("fail TicketSla::assignSlaToTicket");
					Log::info($ex);
				}
				return generateResponse(cus_lang("PAGE_TICKET_MSG_TICKET_SUCCESSFULLY_CREATED"));
      		 }catch (Exception $ex){ 	
			      DB::rollback();
				  return generateResponse($ex->getMessage(), true, true);
       		 }    
	  }	  
	
	  
	  function GetSingleTicket($id){
			$post_data = Input::all();
			
			if ($id > 0){           
				try {

					//$ticketdata = TicketsTable::find($id);
					$ticketdata_query  =      	"call prc_GetSingleTicket (".$id.")";
					$ticketdata		   =		DB::select($ticketdata_query);
					if(isset($ticketdata[0])){
						$ticketdata = $ticketdata[0];
					}else {
						return generateResponse(cus_lang("PAGE_TICKET_MSG_TICKET_NOT_FOUND"),true,true);
					}
				} catch (\Exception $e) {
					Log::info($e);
					return generateResponse(cus_lang("PAGE_TICKET_MSG_TICKET_NOT_FOUND"),true,true);
				}
				return generateResponse('success', false, false, $ticketdata);
			}else{
				return generateResponse(cus_lang("PAGE_TICKET_MSG_PROVIDE_VALID_INTEGER_VALUE"), true, true);
			}
	   }
		
		
	  function GetSingleTicketDetails($id){
			$post_data = Input::all();
			
			if ($id > 0){           
				try {
					$ticketdata =TicketsDetails::where(["TicketID"=>$id])->get();
				} catch (\Exception $e) {
					Log::info($e);
					return generateResponse(cus_lang("PAGE_TICKET_MSG_TICKET_NOT_FOUND"),true,true);
				}
				return generateResponse('success', false, false, $ticketdata);
			}else{
				return generateResponse(cus_lang("PAGE_TICKET_MSG_PROVIDE_VALID_INTEGER_VALUE"), true, true);
			}
	  }
	  
	public function Edit($id)
	{
		$post_data = Input::all();
	    if($id > 0)
		{	
			$data['TicketID'] 					=	 $id;
			$data['ticketdata']					=	 TicketsTable::find($id);
			$data['ticketdetaildata']			=	 TicketsDetails::where(["TicketID"=>$id])->get();								
			
			
			if(isset($data['LoginType']) && $post_data['LoginType']=='customer'){		
				$data['Ticketfields']			=	DB::table('tblTicketfields')->Where(['CustomerDisplay'=>1])->orderBy('FieldOrder', 'asc')->get(); 
			}else{
				$data['Ticketfields']			=	DB::table('tblTicketfields')->orderBy('FieldOrder', 'asc')->get();
			}
			
			
			$data['Agents']			   			= 	 User::getUserIDListAll(0);
			$AllUsers		   					= 	 User::getUserIDListAll(0); 
			$AllUsers[0] 	   					= 	 'None';	
			ksort($AllUsers);			
			$data['AllUsers']					=	$AllUsers;
			$data['htmlgroupID'] 	   			= 	 '';
			$data['htmlagentID']       			= 	 '';
			//$data['AllEmails'] 					= 	implode(",",(Messages::GetAllSystemEmailsWithName(0))); 
			
		   $data['agentsAll'] = DB::table('tblTicketGroupAgents')
            ->join('tblUser', 'tblUser.UserID', '=', 'tblTicketGroupAgents.UserID')->distinct()          
            ->select('tblUser.UserID', 'tblUser.FirstName', 'tblUser.LastName')
            ->get();
			
		   $data['ticketSavedData'] = 	TicketsTable::SetUpdateValues($data['ticketdata'],$data['ticketdetaildata'],$data['Ticketfields']);
			
			//echo "<pre>";			print_r($agentsAll);			echo "</pre>";					exit;
			return generateResponse('success', false, false, $data);
		}else{
		    return generateResponse(cus_lang("PAGE_TICKET_MSG_INVALID_TICKET"),true);
		}
	}
	  
	  function Update($id){

		$TicketID = $id;
		$CompanyID 				= 	User::get_companyID();
		$data 			= 	Input::all();
		$ticketdata		=	 TicketsTable::find($id);
	    if($ticketdata)
		{
			if(!isset($data['Ticket']))
			{
				return generateResponse(cus_lang("MESSAGE_PLEASE_SUBMIT_REQUIRED_FIELDS"),true);
			}
			
			
			$DetailPage 		=   isset($data['Page'])?$data['Page']:'all';
			
			//$RulesMessages      = 	TicketsTable::GetAgentSubmitRules($DetailPage);       
			if(isset($data['LoginType']) && $data['LoginType']=='customer'){
			$RulesMessages      = 	TicketsTable::GetCustomerSubmitRules($DetailPage);       
			}else{
				$RulesMessages      = 	TicketsTable::GetAgentSubmitRules($DetailPage);       
			}
			$validator 			= 	Validator::make($data['Ticket'], $RulesMessages['rules'], $RulesMessages['messages']);
			if ($validator->fails()) {
					return generateResponse($validator->errors(),true);
			}
				$files = '';
				 if (isset($data['file']) && !empty($data['file'])) {
					$files = serialize(json_decode($data['file'],true));
				 }
	
				$Ticketfields 	   =  $data['Ticket'];
				
			
				if(isset($data['LoginType']) && $data['LoginType']=='user')
				{	
					$RequesterData 	   =  explode("<",$Ticketfields['default_requester']);
					$RequesterName	   =  trim($RequesterData[0]);
					$RequesterEmail	   =  substr($RequesterData[1],0,strlen($RequesterData[1])-1);
					$email_from		   =  TicketGroups::where(["GroupID"=>$Ticketfields['default_group']])->pluck('GroupReplyAddress');
					$email_from_name   =  TicketGroups::where(["GroupID"=>$Ticketfields['default_group']])->pluck('GroupName'); 
					
					$TicketData = array(
					"Requester"=>$RequesterEmail,
					"RequesterName"=>$RequesterName,
					"RequesterCC"=>TicketsTable::filterEmailAddressFromName($Ticketfields['cc']),
					"Subject"=>$Ticketfields['default_subject'],
					"Type"=>$Ticketfields['default_ticket_type'],
					"Status"=>$Ticketfields['default_status'],
					"Priority"=>$Ticketfields['default_priority'],
					"Group"=>$Ticketfields['default_group'],
					"Agent"=>$Ticketfields['default_agent'],
					"Description"=>$Ticketfields['default_description'],	
					"AttachmentPaths"=>$files,
					"updated_at"=>date("Y-m-d H:i:s"),
					"updated_by"=>User::get_user_full_name()
				);
				
					if($RequesterEmail!=$ticketdata->Requester){
						$MatchArray  		  =     TicketsTable::SetEmailType($RequesterEmail);
						$TicketData 		  = 	array_merge($TicketData,$MatchArray);
					}
				
				}else{
					
					$TicketData = array(
					"Subject"=>isset($Ticketfields['default_subject'])?$Ticketfields['default_subject']:'',
					"Type"=>isset($Ticketfields['default_ticket_type'])?$Ticketfields['default_ticket_type']:0,
					"Status"=>isset($Ticketfields['default_status'])?$Ticketfields['default_status']:TicketsTable::getDefaultStatus(),
					"Priority"=>isset($Ticketfields['default_priority'])?$Ticketfields['default_priority']:TicketPriority::getDefaultPriorityStatus(),		
					"Description"=>isset($Ticketfields['default_description'])?$Ticketfields['default_description']:'',
					"AttachmentPaths"=>$files,
					"updated_at"=>date("Y-m-d H:i:s"),
					"updated_by"=>User::get_user_full_name()
					);								
				}
				
				try{
					DB::beginTransaction();
					$ticketdata->update($TicketData);	
					
					TicketsDetails::where(["TicketID"=>$id])->delete();
					foreach($Ticketfields as $key => $TicketfieldsData)
					{
						if(!in_array($key,Ticketfields::$staticfields) && $key!="cc")
						{
							$TicketFieldsID =  Ticketfields::where(["FieldType"=>$key])->pluck('TicketFieldsID');
							TicketsDetails::insert(array("TicketID"=>$id,"FieldID"=>$TicketFieldsID,"FieldValue"=>$TicketfieldsData));
						}
					}	
					
					 $TicketData['email_from']  	= 	$email_from;
					 $TicketData['email_from_name'] = 	$email_from_name;				
					 SendTicketEmail('update',$ticketdata,$TicketData);
					 TicketsTable::CheckTicketStatus($ticketdata->Status,$Ticketfields['default_status'],$id);
					 DB::commit();
					try {
						TicketSla::assignSlaToTicket($CompanyID,$TicketID);
					} catch (Exception $ex) {
						Log::info("fail TicketSla::assignSlaToTicket");
						Log::info($ex);
					}
					 return generateResponse(cus_lang("PAGE_TICKET_MSG_TICKET_SUCCESSFULLY_UPDATED"));
				 }catch (Exception $ex){ 	
					  DB::rollback();
					  return generateResponse($ex->getMessage(), true, true);
				 } 
		  }else{
		  	return generateResponse(cus_lang("PAGE_TICKET_MSG_INVALID_TICKET"),true,true);
		  }
	  }
	
	
	
	 function UpdateDetailPage($id){
	  

		$data 			= 	Input::all();
		$ticketdata		=	 TicketsTable::find($id);
		$TicketID 		= $id;
	    if($ticketdata)
		{
			$agent = $ticketdata->Agent;
			$group = $ticketdata->Group;
			if(!isset($data['Ticket']))
			{
				return generateResponse(cus_lang("MESSAGE_PLEASE_SUBMIT_REQUIRED_FIELDS"),true);
			}
			$DetailPage 		=   isset($data['Page'])?$data['Page']:'all'; 
			if(isset($data['LoginType']) && $data['LoginType']=='customer'){
				$RulesMessages      = 	TicketsTable::GetCustomerSubmitRules($DetailPage);       
			}else{
				$RulesMessages      = 	TicketsTable::GetAgentSubmitRules($DetailPage);       
			}
			$validator 			= 	Validator::make($data['Ticket'], $RulesMessages['rules'], $RulesMessages['messages']);
			if ($validator->fails()) {
					return generateResponse($validator->errors(),true);
			}
			
				$Ticketfields 	   =  $data['Ticket'];
				
			
				if($data['LoginType']=='user')
				{	
					$TicketData = array(
					
					"Type"=>$Ticketfields['default_ticket_type'],
					"Status"=>$Ticketfields['default_status'],
					"Priority"=>$Ticketfields['default_priority'],
					"Group"=>$Ticketfields['default_group'],
					"Agent"=>$Ticketfields['default_agent'],
					"updated_at"=>date("Y-m-d H:i:s"),
					"updated_by"=>User::get_user_full_name()
				);
				
				}else{
					
					$TicketData = array(					
					"Type"=>isset($Ticketfields['default_ticket_type'])?$Ticketfields['default_ticket_type']:$ticketdata->Type,
					"Status"=>isset($Ticketfields['default_status'])?$Ticketfields['default_status']:$ticketdata->Status,
					"Priority"=>isset($Ticketfields['default_priority'])?$Ticketfields['default_priority']:$ticketdata->Priority,				
					"updated_at"=>date("Y-m-d H:i:s"),
					"updated_by"=>User::get_user_full_name()
					);
				
				}
				
				try{
					DB::beginTransaction();
					$ticketdata->update($TicketData);	
					
					TicketsDetails::where(["TicketID"=>$id])->delete();
					foreach($Ticketfields as $key => $TicketfieldsData)
					{
						if(!in_array($key,Ticketfields::$staticfields))
						{
							$TicketFieldsID =  Ticketfields::where(["FieldType"=>$key])->pluck('TicketFieldsID');
							TicketsDetails::insert(array("TicketID"=>$id,"FieldID"=>$TicketFieldsID,"FieldValue"=>$TicketfieldsData));
						}
					}				
					 DB::commit();	
					 SendTicketEmail('update',$ticketdata,$TicketData);
					 
						$ticketdata->update($TicketData);	
						if($group!=$Ticketfields['default_group']){ //Agent - Ticket Assigned to Agent email
							$TicketEmails 	=  new TicketEmails(array("TicketID"=>$id,"TriggerType"=>array("AgentAssignedGroup")));
							Log::info("error:".$TicketEmails->GetError());
						}						
						if($agent!=$Ticketfields['default_agent']){ //Agent - Ticket Assigned to Agent email
							$TicketEmails 	=  new TicketEmails(array("TicketID"=>$id,"TriggerType"=>array("TicketAssignedtoAgent")));
							Log::info("error:".$TicketEmails->GetError());
						}
						TicketsTable::CheckTicketStatus($ticketdata->Status,$Ticketfields['default_status'],$id);
						try {
							TicketSla::assignSlaToTicket($ticketdata->CompanyID,$TicketID);
						} catch (Exception $ex) {
							Log::info("fail TicketSla::assignSlaToTicket");
							Log::info($ex);
						}
					 return generateResponse(cus_lang("PAGE_TICKET_MSG_TICKET_SUCCESSFULLY_UPDATED"));
				 }catch (Exception $ex){ 	
					  DB::rollback();
					  return generateResponse($ex->getMessage(), true, true);
				 } 
		  }else{
		  	return generateResponse(cus_lang("PAGE_TICKET_MSG_INVALID_TICKET"),true,true);
		  }
	  }
	
	public function Delete($id)
    {
        if( $id > 0){
            try{
                DB::beginTransaction();
				TicketsTable::MoveTicketToDeletedLog(["TicketID" => $id]);
                DB::commit();
				return generateResponse(cus_lang("PAGE_TICKET_MSG_TICKET_SUCCESSFULLY_DELETED"));
            }catch (Exception $e){
                DB::rollback();
				return generateResponse($e->getMessage(),true,true);
            }

        }
    }
	
	function GetTicketDetailsData()
	{	
	   try
	   {	
	   	   $postdata 					 = 		Input::all();    
		   $data						 =		array();
		   $CompanyID 					 = 		User::get_companyID(); 
		   $data['status']	 			 =   	TicketsTable::getTicketStatus();
		   $data['Priority']		 	 =	 	TicketPriority::getTicketPriority();
		   $data['Groups']			 	 =	 	TicketGroups::getTicketGroups(); 
		   $Agents			 			 = 	 	User::getUserIDListAll(0);
		   $data['Agents']				 = 	 	$row =  array("0"=> "Select")+json_decode(json_encode($Agents),true);   
		   $data['CloseStatus'] 		 =  	TicketsTable::getClosedTicketStatus();  //close status id for ticket 
		   $data['ticketdata']			 =	    TicketsTable::find($postdata['id']);
		   $data['ticketdetaildata']	 =	    TicketsDetails::where(["TicketID"=>$postdata['id']])->get();	
		   $customer 					 = 		0;	   							
		   if(isset($postdata['LoginType']) && $postdata['LoginType']=='customer'){		
				$data['Ticketfields']	=	DB::table('tblTicketfields')->Where(['CustomerDisplay'=>1])->orderBy('FieldOrder', 'asc')->get(); 
			}else{
				$data['Ticketfields']	=	DB::table('tblTicketfields')->orderBy('FieldOrder', 'asc')->get();
			}
		   $data['ticketSavedData'] 	 = 		TicketsTable::SetUpdateValues($data['ticketdata'],$data['ticketdetaildata'],$data['Ticketfields']);
		  
		   $data['agentsAll'] = DB::table('tblTicketGroupAgents')
            ->join('tblUser', 'tblUser.UserID', '=', 'tblTicketGroupAgents.UserID')->distinct()          
            ->select('tblUser.UserID', 'tblUser.FirstName', 'tblUser.LastName')
            ->get();
			
			 if($postdata['LoginType']=='customer'){	
				 $customer  = 1;
			 }
		   
			if($postdata['id'])
			{	
				$timeline_query 				=      	"call prc_getTicketTimeline (".$CompanyID.",".$postdata['id'].",".$customer.")";  
					
				$data['TicketConversation']		 =		$result_array = DB::select($timeline_query);  
				/*if($data['ticketdata']->AccountEmailLogID>0){
				$data['TicketConversation'] 	 = 		AccountEmailLog::where(['EmailParent'=>$data['ticketdata']->AccountEmailLogID,'CompanyID'=>$CompanyID])->get();
				}else{
					$data['TicketConversation'] 	 = array();
				}*/
				if($postdata['admin'])
				{
					 $data['NextTicket'] 				 =	TicketsTable::WhereRaw("TicketID > ".$postdata['id'])->orderby('created_at','asc')->pluck('TicketID');
					 $data['PrevTicket'] 				 =	TicketsTable::WhereRaw("TicketID < ".$postdata['id'])->orderby('created_at','desc')->pluck('TicketID');
				}
				else
				{
					if($postdata['LoginType']=='customer'){	
					 $emails 		=	Account::GetAccountAllEmails(User::get_userID());			
					$data['NextTicket'] 				 =	TicketsTable::WhereRaw("TicketID > ".$postdata['id'])->WhereRaw("find_in_set(Requester,'".$emails."')")->orderby('created_at','asc')->pluck('TicketID'); 
					 $data['PrevTicket'] 				 =	TicketsTable::WhereRaw("TicketID < ".$postdata['id'])->WhereRaw("find_in_set(Requester,'".$emails."')")->orderby('created_at','desc')->pluck('TicketID'); 					
					}else{
					 $data['NextTicket'] 				 =	TicketsTable::WhereRaw("TicketID > ".$postdata['id'])->where(array("Agent"=>user::get_userID()))->orderby('created_at','asc')->pluck('TicketID'); 
					 $data['PrevTicket'] 				 =	TicketsTable::WhereRaw("TicketID < ".$postdata['id'])->where(array("Agent"=>user::get_userID()))->orderby('created_at','desc')->pluck('TicketID'); 
					}
				}
			}  
			return generateResponse('success', false, false, $data);
		}catch (Exception $e){
				return generateResponse($e->getMessage(), true);
        }		
	}
	
	function TicketAction()
	{
		try{
			
			$data 		   		= 	  Input::all();
			$postdata			=	  array();
			$action_type   		=     $data['action_type'];
			$ticket_number  	=     $data['ticket_number'];
			$ticket_type		=	  $data['ticket_type'];
			
			if($ticket_type=='parent'){
				$postdata['response_data']      =     TicketsTable::find($ticket_number);
				$postdata['conversation']      =      TicketsTable::GetConversation($ticket_number);
				$postdata['AccountEmail'] 		= 	  $postdata['response_data']->Requester;
				$postdata['Cc'] 				= 	  $postdata['response_data']->RequesterCC;	
				$postdata['Bcc'] 				= 	  $postdata['response_data']->RequesterBCC;	
				$postdata['parent_id']			=	  0;
				$postdata['GroupEmail']			=	  TicketGroups::where(["GroupID"=>$postdata['response_data']->Group])->pluck('GroupReplyAddress');
				
			}else{
				$postdata['response_data']      =     AccountEmailLog::find($ticket_number);
				$postdata['conversation']      =      $postdata['response_data']->Message;
				$TicketData 				    =     TicketsTable::find($postdata['response_data']->TicketID);
				$postdata['Cc'] 				= 	  $postdata['response_data']->Cc;	
				$postdata['Bcc'] 				= 	  $postdata['response_data']->Bcc;	
				$postdata['AccountEmail'] 		= 	  '';
				$postdata['parent_id']			=	  '';
				$postdata['response_data']->Description		=	  $postdata['response_data']->Message;
				$postdata['GroupEmail']			=	  "";
				$postdata['GroupEmail']			=	  TicketGroups::where(["GroupID"=>$TicketData->Group])->pluck('GroupReplyAddress');
			}
				
				return generateResponse('success', false, false, $postdata);
	   }catch (Exception $e){
			   return generateResponse($e->getMessage(),true);
       }
		
	}

	function UpdateTicketAttributes($id)
	{

		 $data 	= 	Input::all();
		 if($id)
		 {
			   $ticketdata		=	 TicketsTable::find($id);
			   $agent			=	 $ticketdata->Agent;
			   if($ticketdata)
			   {
				   if(!$data['admin'])
				   {
					   if($data['LoginType']=='customer'){	
						 $emails 		=	Account::GetAccountAllEmails(User::get_userID(),true);		
						  if(!in_array($ticketdata->Requester,$emails))
						  {
									return generateResponse(cus_lang("PAGE_TICKET_MSG_YOU_HAVE_NOT_ACCESS_TO_UPDATE_THIS_TICKET"),true,true);
						  }
					   }else{
						  if($ticketdata->Agent!=user::get_userID())
						  {
								return generateResponse(cus_lang("PAGE_TICKET_MSG_YOU_HAVE_NOT_ACCESS_TO_UPDATE_THIS_TICKET"),true,true);
						  }
					   }
				   }
				   if($data['LoginType']=='customer'){	
				   		 $TicketData = array(
							"Status"=>$data['status'],
							"Priority"=>$data['priority'],										
							"updated_at"=>date("Y-m-d H:i:s"),
							"updated_by"=>User::get_user_full_name()
						);

				   }else{
						   $TicketData = array(
							"Status"=>$data['status'],
							"Priority"=>$data['priority'],
							"Group"=>$data['group'],
							"Agent"=>$data['agent'],				
							"updated_at"=>date("Y-m-d H:i:s"),
							"updated_by"=>User::get_user_full_name()
						);
				   }
				 TicketsTable::CheckTicketStatus($ticketdata->Status,$data['status'],$id);
						
				return generateResponse(cus_lang("PAGE_TICKET_MSG_TICKET_SUCCESSFULLY_UPDATED"));
			}			
		 }
	    return generateResponse(cus_lang("PAGE_TICKET_MSG_INVALID_TICKET"),true,true);
	}
	
	function ActionSubmit($id){

		 $data    =  Input::all();
		if($id)
		{
			$ticketdata		=	 TicketsTable::find($id);
			if(isset($ticketdata->TicketID) && $ticketdata->TicketID > 0)
			{
				try
				{				 
				  $rules = array(
						'email-to' =>'required',
						'Subject'=>'required',
						'Message'=>'required',					
					);
					
				 $messages = [
					 "email-to.required" => cus_lang("PAGE_TICKET_MSG_EMAIL_RECIPIENT_REQUIRED"),
					 "Subject.required" => cus_lang("PAGE_TICKET_MSG_EMAIL_SUBJECT_REQUIRED"),
					 "Message.required" => cus_lang("PAGE_TICKET_MSG_EMAIL_MESSAGE_REQUIRED"),
				];
		
					$validator = Validator::make($data, $rules,$messages);
					if ($validator->fails()) {
						return generateResponse($validator->errors(),true);
					}
					
					DB::beginTransaction();
					
					$email_from_data   =  TicketGroups::where(["GroupReplyAddress"=>$data['email-from']])->select( 'GroupReplyAddress','GroupName')->get();
					$files = '';
					$FilesArray = array();

					if (isset($data['file']) && !empty($data['file'])) {
						$FilesArray = json_decode($data['file'],true);
						$files = serialize(json_decode($data['file'],true));
					}

					$data['EmailFrom']  		=   $data['email-from'];
					$data['EmailTo']  		  	= 	TicketsTable::filterEmailAddressFromName($data['email-to']);
					$data['AttachmentPaths'] 	= 	$FilesArray;
					$data['cc'] 				= 	trim(TicketsTable::filterEmailAddressFromName($data['cc']));
					$data['bcc'] 				= 	trim(TicketsTable::filterEmailAddressFromName($data['bcc']));
					$ticketAgentReplay 			=  new TicketEmails(array("TicketID"=>$ticketdata->TicketID,"TriggerType"=>"AgentReplay", "arrOtherData"=>$data));
					 
					if(empty($ticketAgentReplay->GetError()))
					{
						$TicketEmails 	=  new TicketEmails(array("TicketID"=>$ticketdata->TicketID,"TriggerType"=>"CCNoteaddedtoticket","Comment"=>$data['Message'],"NoteUser"=>User::get_user_full_name()));

						//if not agent in ticket then assign current agent to ticket if exits in group
						if($ticketdata->Group){
							$AgentExists =  TicketGroupAgents::where(['GroupID'=>$ticketdata->Group,"UserID"=>User::get_userID()])->count();							
							if($AgentExists>0){
								$ticketdata->update(["Agent"=>User::get_userID()]);
							}
						}
						
						$ticketdataAll		=	 TicketsTable::find($ticketdata->TicketID);
						//if($ticketdata->Agent==User::get_userID()){ //removed as mam said
							$ticketdataAll->update(["AgentRepliedDate"=>date('Y-m-d H:i:s')]);
						//}


						//update cc and bcc in ticket
						if(!empty($data['cc']) || !empty($data['bcc'])) {
							$ticketdatacc =	TicketsTable::find($ticketdata->TicketID);

							$cc 	= explode(',',$data['cc']);
							$bcc 	= explode(',',$data['bcc']);

							$ticketcc  = explode(',',$ticketdatacc->RequesterCC);
							$ticketbcc = explode(',',$ticketdatacc->RequesterBCC);

							$ticketcc  = implode(',',array_unique(array_merge(array_filter($ticketcc),array_filter($cc))));
							$ticketbcc = implode(',',array_unique(array_merge(array_filter($ticketbcc),array_filter($bcc))));

							$ticketdatacc->update(['RequesterCC'=>$ticketcc,'RequesterBCC'=>$ticketbcc]);
						}

						TicketLog::insertTicketLog($ticketdata->TicketID,TicketLog::TICKET_ACTION_AGENT_REPLIED,($data['LoginType']=='user')?0:1);


						DB::commit();
						return generateResponse(cus_lang("MESSAGE_SUCCESSFULLY_UPDATED"));
					}else{
						 return generateResponse(cus_lang("PAGE_TICKET_MSG_PROBLEM_SENDING_EMAIL"),true);
					}
				}
				catch (Exception $e){
					DB::rollback();
					return generateResponse($e->getMessage(),true);
				}
			}	
			 return generateResponse(cus_lang("PAGE_TICKET_MSG_INVALID_TICKET"),true);
		}
		   
	}
	
	public function CustomerActionSubmit($id){

		 $data    =  Input::all();
		if($id)
		{
			$ticketdata		=	 TicketsTable::find($id);
			if($ticketdata)
			{
				try
				{				 
				  $rules = array(
						'email-to' =>'required',
						'Subject'=>'required',
						'Message'=>'required',					
					);
					
				 $messages = [
					 "email-to.required" => cus_lang("PAGE_TICKET_MSG_EMAIL_RECIPIENT_REQUIRED"),
					 "Subject.required" => cus_lang("PAGE_TICKET_MSG_EMAIL_SUBJECT_REQUIRED"),
					 "Message.required" => cus_lang("PAGE_TICKET_MSG_EMAIL_MESSAGE_REQUIRED"),
				];
		
					$validator = Validator::make($data, $rules,$messages);
					if ($validator->fails()) {
						return generateResponse($validator->errors(),true);
					}
					
					DB::beginTransaction();
					
					$email_from_data   =  TicketGroups::where(["GroupReplyAddress"=>$data['email-to']])->select('GroupReplyAddress','GroupName')->get();
					
					 $files = '';
					 $FilesArray = array();
					 if (isset($data['file']) && !empty($data['file'])) {
						 $FilesArray = json_decode($data['file'],true);
						$files = serialize(json_decode($data['file'],true));
					}

					 $data['EmailFrom']  		=   $data['email-from'];
					 $data['CompanyName'] 	    =   $email_from_data[0]->GroupName;
					 $data['EmailTo']  		  	= 	$data['email-to'];
					 $data['AttachmentPaths'] 	= 	$FilesArray;
					 $data['cc'] 				= 	trim($data['cc']);
					 $data['bcc'] 				= 	trim($data['bcc']);		
					 $data['In-Reply-To'] 		= 	AccountEmailLog::getLastMessageIDByTicketID($ticketdata->TicketID);
					 $data['Message-ID']		= 	$ticketdata->TicketID;
					 $status 					= 	sendMail('emails.tickets.ticket', $data);
					if($status['status'] == 1)
					{	
						/*$message_id = isset($status['message_id'])?$status['message_id']:'';
						
						$logData = ['EmailFrom'=>$data['email-from'],
						'EmailTo'=>trim($data['email-to']),
						'Subject'=>trim($data['Subject']),
						'Message'=>trim($data['Message']),
						'CompanyID'=>\Api\Model\User::get_companyID(),
						'UserID'=>\Api\Model\User::get_userID(),
						'CreatedBy'=>\Api\Model\User::get_user_full_name(),
						"created_at"=>date("Y-m-d H:i:s"),
						'Cc'=>$data['cc'],
						'Bcc'=>$data['bcc'],
						"AttachmentPaths"=>$files,
						"MessageID"=>$message_id,
						"EmailParent"=>isset($ticketdata->AccountEmailLogID)?$ticketdata->AccountEmailLogID:0,
						"EmailCall"=>Messages::Sent,
					];
						AccountEmailLog::create($logData);	
						*/
						
						$ticketdata->update(["CustomerRepliedDate"=>date('Y-m-d H:i:s')]);
						
						 DB::commit();	
						return generateResponse(cus_lang("MESSAGE_SUCCESSFULLY_UPDATED"));
					}else{
						 return generateResponse(cus_lang("PAGE_TICKET_MSG_PROBLEM_SENDING_EMAIL"),true);
					}
				}
				catch (Exception $e){
					DB::rollback();
					return generateResponse($e->getMessage(),true);
				}
			}	
				 return generateResponse(cus_lang("PAGE_TICKET_MSG_INVALID_TICKET"),true);
		}
		   
	
	}
	
	public function GetTicketAttachment($ticketID,$attachmentID){
		$Ticketdata 	=   TicketsTable::find($ticketID);	
		
		if($Ticketdata)
		{
			$attachments 	=   unserialize($Ticketdata->AttachmentPaths);
			$attachment 	=   $attachments[$attachmentID];  
			$FilePath 		=  	AmazonS3::preSignedUrl($attachment['filepath']);	
			
			if(file_exists($FilePath)){
					download_file($FilePath);
			}else{
					header('Location: '.$FilePath);
			}
		}
         exit;		
	}
	
	
	function CloseTicket($ticketID)
	{
		$Ticketdata 	=   TicketsTable::find($ticketID);		
		$data 			= 	Input::all(); 
		
		if($Ticketdata)
		{ 	 $CloseStatus =  TicketsTable::getClosedTicketStatus(); 
			 $Ticketdata->update(array("Status"=>$CloseStatus));	
			// return Response::json(array("status" => "success", "message" => "Ticket Successfully Closed.","close_id"=>$CloseStatus)); 	
			if(isset($data['isSendEmail']) && $data['isSendEmail']>0){ 
				$TicketEmails 	=  new TicketEmails(array("TicketID"=>$ticketID,"TriggerType"=>"AgentClosestheTicket"));
			}
			 return generateResponse(cus_lang("PAGE_TICKET_MSG_TICKET_SUCCESSFULLY_CLOSED"));
			 //return generateResponse("Ticket Successfully Closed");
		}
		return generateResponse(cus_lang("PAGE_TICKET_MSG_INVALID_TICKET"),true,true);
	}
	
	
	function SendMailTicket(){


		$data =	Input::all();
		$CompanyID = User::get_companyID();

		if(!isset($data['Ticket'])){
			return generateResponse(cus_lang("MESSAGE_PLEASE_SUBMIT_REQUIRED_FIELDS"),true);
		}

		//$RulesMessages      = 	TicketsTable::GetAgentSubmitRules();       
		if(isset($data['LoginType']) && $data['LoginType']=='customer'){
			$RulesMessages      = 	TicketsTable::GetCustomerSubmitRules();       
		}else{
			$RulesMessages      = 	TicketsTable::GetAgentSubmitComposeRules();       
		} 
        $validator 			= 	Validator::make($data['Ticket'], $RulesMessages['rules'], $RulesMessages['messages']);
        if ($validator->fails()) {
			return generateResponse($validator->errors(),true);
        }
		
		
		 $files = '';
		 if (isset($data['file']) && !empty($data['file'])) {
            $files = serialize(json_decode($data['file'],true));
        }
		
			//$email_from		   =  TicketGroups::where(["GroupID"=>$data['email-from']])->pluck('GroupReplyAddress'); 
			//$email_from_name   =  TicketGroups::where(["GroupID"=>$data['email-from']])->pluck('GroupName'); 
			$email_from_data   				= 	TicketGroups::where(["GroupEmailAddress"=>$data['email-from']])->get(array('GroupID'));
			$Ticketfields      				= 	$data['Ticket'];
			
			if(count($email_from_data)>0){
				$Ticketfields['default_group']  = 	$email_from_data[0]->GroupID;
			}else{
				$Ticketfields['default_group']  = 	0;
			}
			
			//$RequesterEmail	  		=  	trim($data['email-to']);		
			if (strpos($data['email-to'], '<') !== false && strpos($data['email-to'], '>') !== false)
			{
				$RequesterData 	   =  explode(" <",$data['email-to']);
				$RequesterName	   =  $RequesterData[0];
				$RequesterEmail	   =  substr($RequesterData[1],0,strlen($RequesterData[1])-1);	
			}else{
				$RequesterName	   =  '';
				$RequesterEmail	   =  trim($data['email-to']);					
			}			
			
			if($data['LoginType']=='user')
			{
				$TicketData = array(
					"CompanyID"=>$CompanyID,
					"Requester"=>$RequesterEmail,
					"RequesterName"=>$RequesterName,
					"RequesterCC"=>isset($data['cc'])?TicketsTable::filterEmailAddressFromName($data['cc']):'',
					"Subject"=>$data['Subject'],
					"Type"=>$Ticketfields['default_ticket_type'],
					"Status"=>$Ticketfields['default_status'],
					"Priority"=>$Ticketfields['default_priority'],
					"Group"=>$Ticketfields['default_group'],
					//"Agent"=>$Ticketfields['default_agent'],
					"Description"=>$data['Message'],	
					"AttachmentPaths"=>$files,
					"TicketType"=>TicketsTable::EMAIL,
					"created_at"=>date("Y-m-d H:i:s"),
					"created_by"=>User::get_user_full_name()
				);
			}else{
				$TicketData = array(
					"CompanyID"=>$CompanyID,
					"Requester"=>$RequesterEmail,
					"RequesterCC"=>isset($data['cc'])?$data['cc']:'',
					//"RequesterName"=>$RequesterName,
					"Subject"=>isset($data['Subject'])?$data['Subject']:'',
					"Type"=>isset($Ticketfields['default_ticket_type'])?$Ticketfields['default_ticket_type']:0,
					"Status"=>isset($Ticketfields['default_status'])?$Ticketfields['default_status']:TicketsTable::getDefaultStatus(),
					"Priority"=>isset($Ticketfields['default_priority'])?$Ticketfields['default_priority']:TicketPriority::getDefaultPriorityStatus(),					
					"Description"=>isset($data['Message'])?$data['Message']:'',	 
					"AttachmentPaths"=>$files,
					"TicketType"=>TicketsTable::EMAIL,
					"created_at"=>date("Y-m-d H:i:s"),
					"created_by"=>User::get_user_full_name()
				);
			}
			
			$MatchArray  		  =     TicketsTable::SetEmailType($RequesterEmail);
			$TicketData 		  = 	array_merge($TicketData,$MatchArray);
			
			try{
 			    DB::beginTransaction();
				log::info("--Ticket Data--");
				$TicketID = TicketsTable::insertGetId($TicketData);
				log::info("--Ticket Data over -- ".$TicketID);

				log::info("--Ticket Filed--");

				foreach($Ticketfields as $key => $TicketfieldsData)
				{
					if(!in_array($key,Ticketfields::$staticfields))
					{
						$TicketFieldsID =  Ticketfields::where(["FieldType"=>$key])->pluck('TicketFieldsID');
						log::info("--Ticket New Filed -- ".$TicketFieldsID);
						TicketsDetails::insert(array("TicketID"=>$TicketID,"FieldID"=>$TicketFieldsID,"FieldValue"=>$TicketfieldsData));
					}
				}

				log::info("--Ticket Fileds over--");

				log::info("--Ticket log --");

				TicketLog::insertTicketLog($TicketID,TicketLog::TICKET_ACTION_CREATED,($data['LoginType']=='user')?0:1);
                TicketLog::insertTicketLog($TicketID,TicketLog::TICKET_ACTION_STATUS_CHANGED,($data['LoginType']=='user')?0:1,$Ticketfields['default_status']);

				log::info("--Ticket log over --");
				//create contact if email not found in system

				log::info("--Contact log --");
				$AllEmails  =   Messages::GetAllSystemEmails();


				if(!in_array($RequesterEmail,$AllEmails))
				{
					$ContactData = array("Email"=>$RequesterEmail,"CompanyId"=>$CompanyID);
					log::info("--Contact Email -- ".$RequesterEmail);
					log::info("--Contact CompanyId -- ".$CompanyID);
					$ContactID = Contact::insertGetId($ContactData);
					TicketsTable::find($TicketID)->update(array("ContactID"=>$ContactID));

				}

				log::info("--Contact over --");

				/* Ticket Email Send Section */
				log::info("--Email send --");
				$TicketEmails 		=  new TicketEmails(array("TicketID"=>$TicketID,"TriggerType"=>"CCEmailTicketCreated","EmailSenderFrom"=>$data['email-from']));

				log::info("--Email over --");
				/* Ticket Email Send Section over*/

				/*if(count($email_from_data)>0){	
					 $TicketData['AddReplyTo']	 	  = 	$email_from_data[0]->GroupEmailAddress;				
					 $TicketData['email_from']	   	  = 	$email_from_data[0]->GroupReplyAddress;
					 $TicketData['email_from_name']   = 	$email_from_data[0]->GroupName;		
				}else{
					$TicketData['email_from']	   	  = 	$data['email-from'];
					$TicketData['email_from_name']    = 	Company::getName();
					$TicketData['AddReplyTo']	 	  = 	$data['email-from'];		
				}
				 $TicketData['cc']				  =     isset($data['cc'])?$data['cc']:''; 
				 $TicketData['bcc']				  =     isset($data['bcc'])?$data['bcc']:''; 
				 $TicketData['TicketID']	 	  = 	$TicketID; 
				 $logID 						  = 	SendComposeTicketEmail($TicketData); 
				 if(!isset($logID['status'])){
				  	TicketsTable::find($TicketID)->update(array("AccountEmailLogID"=>$logID));
				 }else{
				 	return generateResponse($logID['message'], true, true);
				 }*/
				/* comment ticket assign section
				 if($Ticketfields['default_group']){
				 	$TicketEmails 		=  new TicketEmails(array("TicketID"=>$TicketID,"TriggerType"=>array("AgentAssignedGroup")));
				 }
				 $TicketEmails1		=  new TicketEmails(array("TicketID"=>$TicketID,"TriggerType"=>array("RequesterNewTicketCreated")));				 
				 $TicketEmails 		=  new TicketEmails(array("TicketID"=>$TicketID,"TriggerType"=>"CCNewTicketCreated"));
				*/
				 DB::commit();

				try {
					TicketSla::assignSlaToTicket($CompanyID,$TicketID);
				} catch (Exception $ex) {
					Log::info("fail TicketSla::assignSlaToTicket");
					Log::info($ex);
				}
				 return generateResponse(cus_lang("PAGE_TICKET_MSG_TICKET_SUCCESSFULLY_CREATED"));
      		 }catch (Exception $ex){ 	
			      DB::rollback();
				  return generateResponse($ex->getMessage(), true, true);
       		 }    
	}
	
	function add_note(){

		$data 			= 	Input::all();
		
		 $rules = array(
				'TicketID' =>'required',
				'Note' =>'required',
			);
			$validator = Validator::make($data, $rules);
			if ($validator->fails()) {
				return generateResponse($validator->errors(),true);
			}
			try{
				$Account = TicketsTable::CheckTicketAccount($data['TicketID']);
				$NoteData = array(
						"CompanyID"=>User::get_companyID(),
						"Note"=>$data['Note'],
						"TicketID"=>$data['TicketID'],
						"AccountID"=>$Account,
                        "UserID"=>User::get_userID(),
						"created_at"=>date("Y-m-d H:i:s"),
						"created_by"=>User::get_user_full_name()
					);
				 
				Note::insertGetId($NoteData);
				if(isset($data['LoginType']) && $data['LoginType']=='customer'){		
					
				}else{
		//		$TicketEmails 	=  new TicketEmails(array("TicketID"=>$data['TicketID'],"TriggerType"=>"AgentAddsCommenttoTicket","Comment"=>$data['Note']));
					$TicketEmails 	=  new TicketEmails(array("TicketID"=>$data['TicketID'],"TriggerType"=>"Noteaddedtoticket","Comment"=>$data['Note'],"NoteUser"=>User::get_user_full_name()));
				}
				return generateResponse(cus_lang("PAGE_TICKET_MSG_NOTE_SUCCESSFULLY_CREATED"));
			} catch (\Exception $ex) {
				 return generateResponse($ex->getMessage(), true, true);			
			}
	}

    function BulkAction(){
        $data = Input::all();
        if((isset($data['Type']) && $data['Type'] == 0 && isset($data['TypeCheck'])) &&
            (isset($data['Status']) && $data['Status'] == 0 && isset($data['StatusCheck'])) &&
            (isset($data['Priority']) && $data['Priority'] == 0 && isset($data['PriorityCheck'])) &&
            (isset($data['Group']) && $data['Group'] == 0 && isset($data['GroupCheck'])) &&
            (isset($data['Agent']) && $data['Agent'] == 0 && isset($data['AgentCheck']))){
            return generateResponse('Please select at least one option.',true,true);
        }elseif(!isset($data['selectedIDs']) || empty($data['selectedIDs'])){
            return generateResponse('Please select at least one ticket.',true,true);
        }
        $update = [];
        if(isset($data['Type']) && $data['Type'] != 0 && isset($data['TypeCheck'])){
            $update['Type'] = $data['Type'];
        }
        if(isset($data['Status']) && $data['Status'] != 0 && isset($data['StatusCheck'])){
            $update['Status'] = $data['Status'];
        }
        if(isset($data['Priority']) && $data['Priority'] != 0 && isset($data['PriorityCheck'])){
            $update['Priority'] = $data['Priority'];
        }
        if(isset($data['Group']) && $data['Group'] != 0 && isset($data['GroupCheck'])){
            $update['Group'] = $data['Group'];
        }
        if(isset($data['Agent']) && $data['Agent'] != 0 && isset($data['AgentCheck'])){
            $update['Agent'] = $data['Agent'];
        }
        $selectedIDs = explode(',',$data['selectedIDs']);
        try {

            //Implement loop because boot is triggering for each updated record to log the changes.
            foreach ($selectedIDs as $id) {
                $ticket = TicketsTable::find($id);
				DB::beginTransaction();
                if(isset($update['Status']) && ($update['Status'] != 0) && $data['isSendEmail'] == 1){
                    TicketsTable::CheckTicketStatus($ticket->Status,$update['Status'],$id);
                }
                //TicketsTable::where(['TicketID'=>$id])->update($update);
				$ticket->update($update);
				DB::commit();
				try {
					$TicketID=$id;
					TicketSla::assignSlaToTicket($ticket->CompanyID,$TicketID);
				} catch (Exception $ex) {
					Log::info("fail TicketSla::assignSlaToTicket");
					Log::info($ex);
				}
            }
            return generateResponse('Tickets updated successfully.');
        }catch (Exception $e) {
            DB::rollback();
            return generateResponse($e->getMessage(), true, true);
        }
    }

    function BulkDelete(){
        $data = Input::all();
        if(isset($data['SelectedIDs']) && empty($data['SelectedIDs'])){
            return generateResponse('Please select at least one ticket.',true,true);
        }
        try {
            DB::beginTransaction();

			TicketsTable::MoveTicketToDeletedLog(["TicketIDs" => $data['SelectedIDs']]);
			DB::commit();
            return generateResponse('Tickets deleted successfully.');
        }catch (Exception $e) {
            DB::rollback();
            return generateResponse($e->getMessage(), true, true);
        }
    }
	
	public function get_priorities(){
		$row =  TicketPriority::orderBy('PriorityID')->lists('PriorityValue', 'PriorityID');
		return $row;
	}
	
	function UpdateTicketDueTime(){
		$data 		= 	Input::all();

		$rules['TicketID'] = 'required';
		$rules['DueDate'] = 'required';
		$rules['DueTime'] = 'required';

		$validator = Validator::make($data, $rules);

		if ($validator->fails()) {
			return generateResponse($validator->errors(),true);
		}

		$TicketID = $data["TicketID"];
		$due_date  = date( "Y-m-d H:i:s", strtotime($data["DueDate"] . ' ' . $data["DueTime"]));

		if($TicketID > 0){
			if(TicketsTable::find($TicketID)->update(["DueDate"=>$due_date,"CustomDueDate"=>1])){
				return generateResponse(cus_lang("MESSAGE_SUCCESSFULLY_UPDATED"));
			}
		}
		return generateResponse(cus_lang("PAGE_TICKET_MSG_FAILED_TO_UPDATED_DUE_DATE"));

	}
}