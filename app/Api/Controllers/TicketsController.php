<?php

namespace Api\Controllers;

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
use Api\Model\TicketsConversation;
use Api\Model\Messages;
use App\Http\Requests;
use Dingo\Api\Facade\API;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class TicketsController extends BaseController
{

private $validlicense;	

	public function __construct(Request $request){ 
        $this->middleware('jwt.auth');
        Parent::__Construct($request);
		$this->validlicense = TicketsTable::CheckTicketLicense();
    }
	 
	 protected function IsValidLicense(){
	 	return $this->validlicense;		
	 }
	  
	  function GetResult(){ 
		   $data 					= 	Input::all();  
		   $CompanyID 				= 	User::get_companyID(); 
		   $search		 			=	isset($data['Search'])?$data['Search']:'';	   		   
		   $status					=	isset($data['status'])?is_array($data['status'])?implode(",",$data['status']):'':'';		   
		   $priority				=	isset($data['priority'])?is_array($data['priority'])?implode(",",$data['priority']):'':'';
		   $Group					=	isset($data['group'])?is_array($data['group'])?implode(",",$data['group']):'':'';		  
		   $agent					=	isset($data['agent'])?$data['agent']:'';	
		   $columns 	 			= 	array('TicketID','Subject','Requester','Type','Status','Priority','Group','Agent','created_at');		
		   $sort_column 			= 	$data['iSortCol_0'];
		   
		   
		   if($data['LoginType']=='customer'){		
				   $agent		=	'';
				   $emails 		=	Account::GetAccountAllEmails(User::get_userID());				 
				   $query 		= 	"call prc_GetSystemTicketCustomer ('".$CompanyID."','".$search."','".$status."','".$priority."','".$Group."','".$agent."','".$emails."',".( ceil($data['iDisplayStart']/$data['iDisplayLength']) )." ,".$data['iDisplayLength'].",'".$sort_column."','".$data['sSortDir_0']."',0)";  
		   }else{			 	  		   			   
			  	  $query 		= 	"call prc_GetSystemTicket ('".$CompanyID."','".$search."','".$status."','".$priority."','".$Group."','".$agent."',".( ceil($data['iDisplayStart']/$data['iDisplayLength']) )." ,".$data['iDisplayLength'].",'".$sort_column."','".$data['sSortDir_0']."',0)";  
			}
		Log::info("query".$query);
			$resultdata   	=  DataTableSql::of($query)->getProcResult(array('ResultCurrentPage','TotalResults'));	
			$resultpage  	=  DataTableSql::of($query)->make(false);				
		
			$result = ["resultpage"=>$resultpage,"iTotalRecords"=>$resultdata->iTotalRecords,"iTotalDisplayRecords"=>$resultdata->iTotalDisplayRecords,"totalcount"=>$resultdata->data['TotalResults'][0]->totalcount,"ResultCurrentPage"=>$resultdata->data['ResultCurrentPage']];
			
			 return generateResponse('success', false, false,$result);
	}
	  
	  function Store(){
	    $this->IsValidLicense();
		$data 			= 	Input::all();  

		if(!isset($data['Ticket'])){
			return generateResponse("Please submit required fields.",true);
		}
		
		//Log::info(print_r($data,true));
		//Log::info(".....................................");
		$RulesMessages      = 	TicketsTable::GetAgentSubmitRules();       
        $validator 			= 	Validator::make($data['Ticket'], $RulesMessages['rules'], $RulesMessages['messages']);
        if ($validator->fails()) {
			return generateResponse($validator->errors(),true);
        }
		
		
		 $files = '';
		 if (isset($data['file']) && !empty($data['file'])) {
            $files = serialize(json_decode($data['file'],true));
        }

		    $Ticketfields      =  $data['Ticket'];
			$RequesterData 	   =  explode(" <",$Ticketfields['default_requester']);
			$RequesterName	   =  $RequesterData[0];
			$RequesterEmail	   =  substr($RequesterData[1],0,strlen($RequesterData[1])-1);	
		
			if($data['LoginType']=='user')
			{
				$TicketData = array(
					"CompanyID"=>User::get_companyID(),
					"Requester"=>$RequesterEmail,
					"RequesterName"=>$RequesterName,
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
					"CompanyID"=>User::get_companyID(),
					"Requester"=>$RequesterEmail,
					"RequesterName"=>$RequesterName,
					"Subject"=>$Ticketfields['default_subject'],
					"Type"=>$Ticketfields['default_ticket_type'],
					"Status"=>$Ticketfields['default_status'],
					"Priority"=>$Ticketfields['default_priority'],					
					"Description"=>$Ticketfields['default_description'],	
					"AttachmentPaths"=>$files,
					"created_at"=>date("Y-m-d H:i:s"),
					"created_by"=>User::get_user_full_name()
				);
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
				 DB::commit();	
				 SendTicketEmail('store',$TicketID,$TicketData);
				 return generateResponse('Ticket Successfully Created');
      		 }catch (Exception $ex){ 	
			      DB::rollback();
				  return generateResponse($ex->getMessage(), true, true);
       		 }    
	  }	  
	
	  
	  function GetSingleTicket($id){
			$post_data = Input::all();
			
			if ($id > 0){           
				try {
					$ticketdata = TicketsTable::findOrFail($id);
				} catch (\Exception $e) {
					Log::info($e);
					return generateResponse('Ticket not found.',true,true);
				}
				return generateResponse('success', false, false, $ticketdata);
			}else{
				return generateResponse('Provide Valid Integer Value.', true, true);
			}
	   }
		
		
	  function GetSingleTicketDetails($id){
			$post_data = Input::all();
			
			if ($id > 0){           
				try {
					$ticketdata =TicketsDetails::where(["TicketID"=>$id])->get();
				} catch (\Exception $e) {
					Log::info($e);
					return generateResponse('Ticket not found.',true,true);
				}
				return generateResponse('success', false, false, $ticketdata);
			}else{
				return generateResponse('Provide Valid Integer Value.', true, true);
			}
	  }
	  
	public function Edit($id)
	{
		$this->IsValidLicense();
	    if($id > 0)
		{	
			$data['TicketID'] 					=	 $id;
			$data['ticketdata']					=	 TicketsTable::find($id);
			$data['ticketdetaildata']			=	 TicketsDetails::where(["TicketID"=>$id])->get();								
			$data['Ticketfields']	   			=	 DB::table('tblTicketfields')->orderBy('FieldOrder', 'asc')->get(); 
			$data['Agents']			   			= 	 User::getUserIDListAll(0);
			$AllUsers		   					= 	 User::getUserIDListAll(0); 
			$AllUsers[0] 	   					= 	 'None';	
			ksort($AllUsers);			
			$data['AllUsers']					=	$AllUsers;
			$data['htmlgroupID'] 	   			= 	 '';
			$data['htmlagentID']       			= 	 '';
			$data['AllEmails'] 					= 	implode(",",(Messages::GetAllSystemEmailsWithName(0))); 
			
		   $data['agentsAll'] = DB::table('tblTicketGroupAgents')
            ->join('tblUser', 'tblUser.UserID', '=', 'tblTicketGroupAgents.UserID')->distinct()          
            ->select('tblUser.UserID', 'tblUser.FirstName', 'tblUser.LastName')
            ->get();
			
		   $data['ticketSavedData'] = 	TicketsTable::SetUpdateValues($data['ticketdata'],$data['ticketdetaildata'],$data['Ticketfields']);
			
			//echo "<pre>";			print_r($agentsAll);			echo "</pre>";					exit;
			return generateResponse('success', false, false, $data);
		}else{
		    return generateResponse("invalid Ticket.",true);
		}
	}
	  
	  function Update($id){
	  
	    $this->IsValidLicense();
		$data 			= 	Input::all();  
		$ticketdata		=	 TicketsTable::find($id);
	    if($ticketdata)
		{
			if(!isset($data['Ticket']))
			{
				return generateResponse("Please submit required fields.",true);
			}
			
			//Log::info(print_r($data,true));
			//Log::info(".....................................");
			$RulesMessages      = 	TicketsTable::GetAgentSubmitRules();       
			$validator 			= 	Validator::make($data['Ticket'], $RulesMessages['rules'], $RulesMessages['messages']);
			if ($validator->fails()) {
					return generateResponse($validator->errors(),true);
			}
			
			
				$files = '';
				 if (isset($data['file']) && !empty($data['file'])) {
					$files = serialize(json_decode($data['file'],true));
				 }
	
				$Ticketfields 	   =  $data['Ticket'];
				
			
				if($data['LoginType']=='user')
				{	
					$RequesterData 	   =  explode(" <",$Ticketfields['default_requester']);
					$RequesterName	   =  $RequesterData[0];
					$RequesterEmail	   =  substr($RequesterData[1],0,strlen($RequesterData[1])-1);		
					$TicketData = array(
					"Requester"=>$RequesterEmail,
					"RequesterName"=>$RequesterName,
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
				
				}else{
					
					$TicketData = array(
					"Subject"=>$Ticketfields['default_subject'],
					"Type"=>$Ticketfields['default_ticket_type'],
					"Status"=>$Ticketfields['default_status'],
					"Priority"=>$Ticketfields['default_priority'],					
					"Description"=>$Ticketfields['default_description'],	
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
						if(!in_array($key,Ticketfields::$staticfields))
						{
							$TicketFieldsID =  Ticketfields::where(["FieldType"=>$key])->pluck('TicketFieldsID');
							TicketsDetails::insert(array("TicketID"=>$id,"FieldID"=>$TicketFieldsID,"FieldValue"=>$TicketfieldsData));
						}
					}				
					 DB::commit();	
					 SendTicketEmail('update',$ticketdata,$TicketData);
					 return generateResponse('Ticket Successfully Updated');
				 }catch (Exception $ex){ 	
					  DB::rollback();
					  return generateResponse($ex->getMessage(), true, true);
				 } 
		  }else{
		  	return generateResponse("invalid Ticket",true,true);
		  }
	  }
	
	public function Delete($id)
    {
        if( $id > 0){
            try{
                DB::beginTransaction();
                TicketsTable::where(["TicketID"=>$id])->delete();
              	TicketsDetails::where(["TicketID"=>$id])->delete();
				TicketsConversation::where(array('TicketID'=>$id))->delete();
                DB::commit();
				return generateResponse("Ticket Successfully Deleted");
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
		   $data['status']	 			 =   	TicketsTable::getTicketStatus();
		   $data['Priority']		 	 =	 	TicketPriority::getTicketPriority();
		   $data['Groups']			 	 =	 	TicketGroups::getTicketGroups(); 
		   $Agents			 			 = 	 	User::getUserIDListAll(0);
		   $data['Agents']				 = 	 	$row =  array("0"=> "Select")+json_decode(json_encode($Agents),true);   
		   $data['CloseStatus'] 		 =  	TicketsTable::getClosedTicketStatus();  //close status id for ticket 
			if($postdata['id'])
			{
				$data['TicketConversation']	 =		TicketsConversation::where(array('TicketID'=>$postdata['id']))->get();
				if($postdata['admin'])
				{
					 $data['NextTicket'] 				 =	TicketsTable::WhereRaw("TicketID > ".$postdata['id'])->pluck('TicketID');
					 $data['PrevTicket'] 				 =	TicketsTable::WhereRaw("TicketID < ".$postdata['id'])->pluck('TicketID');
				}
				else
				{
					if($postdata['LoginType']=='customer'){	
					 $emails 		=	Account::GetAccountAllEmails(User::get_userID());			
					$data['NextTicket'] 				 =	TicketsTable::WhereRaw("TicketID > ".$postdata['id'])->WhereRaw("find_in_set(Requester,'".$emails."')")->pluck('TicketID'); 
					 $data['PrevTicket'] 				 =	TicketsTable::WhereRaw("TicketID < ".$postdata['id'])->WhereRaw("find_in_set(Requester,'".$emails."')")->pluck('TicketID'); 					
					}else{
					 $data['NextTicket'] 				 =	TicketsTable::WhereRaw("TicketID > ".$postdata['id'])->where(array("Agent"=>user::get_userID()))->pluck('TicketID'); 
					 $data['PrevTicket'] 				 =	TicketsTable::WhereRaw("TicketID < ".$postdata['id'])->where(array("Agent"=>user::get_userID()))->pluck('TicketID'); 
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
				$postdata['AccountEmail'] 		= 	  $postdata['response_data']->Requester;	
				$postdata['parent_id']			=	  0;
			}else{
				$postdata['response_data']      =     TicketsConversation::find($ticket_number);
				$postdata['AccountEmail'] 		= 	  TicketsConversation::where(array('TicketConversationID'=>$ticket_number))->pluck('Requester');
				$postdata['parent_id']			=	  TicketsConversation::where(array('TicketConversationID'=>$ticket_number))->pluck('TicketConversationID');
				$postdata['response_data']->Description		=	  $postdata['response_data']->TicketMessage;
			}
				
				return generateResponse('success', false, false, $postdata);
	   }catch (Exception $e){
			   return generateResponse($e->getMessage(),true);
       }
		
	}
	
	function UpdateTicketAttributes($id)
	{
		 $this->IsValidLicense();
		 $data 	= 	Input::all();   
		 if($id)
		 {
			   $ticketdata		=	 TicketsTable::find($id);
			   if($ticketdata)
			   {
				   if(!$data['admin'])
				   {
					   if($data['LoginType']=='customer'){	
						 $emails 		=	Account::GetAccountAllEmails(User::get_userID(),true);		
						  if(!in_array($ticketdata->Requester,$emails))
						  {
									return generateResponse("You have not access to update this ticket",true,true);
						  }
					   }else{
						  if($ticketdata->Agent!=user::get_userID())
						  {
								return generateResponse("You have not access to update this ticket",true,true);
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
				$ticketdata->update($TicketData);	
				return generateResponse("Ticket Successfully Updated");
			}			
		 }
	    return generateResponse("invalid Ticket",true,true);
	}
	
	function ActionSubmit($id){
		 $this->IsValidLicense();
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
					 "email-to.required" => "The email recipient is required",
					 "Subject.required" => "The email Subject is required",
					 "Message.required" => "The email message field is required",				 
				];
		
					$validator = Validator::make($data, $rules,$messages);
					if ($validator->fails()) {
						return generateResponse($validator->errors(),true);
					}
					
					DB::beginTransaction();
					
					
					 $files = '';
					 $FilesArray = array();
					 if (isset($data['file']) && !empty($data['file'])) {
						 $FilesArray = json_decode($data['file'],true);
						$files = serialize(json_decode($data['file'],true));
					}
					
					$ticketCoversationData = array(
						"TicketID"=>$id,
						"Requester"=>trim($data['email-to']),
						"Cc"=>trim($data['cc']),
						"Bcc"=>trim($data['bcc']),
						"Subject"=>trim($data['Subject']),
						"TicketMessage"=>trim($data['Message']),
						"TicketParentID"=>$data['TicketParent'],
						"AttachmentPaths"=>$files
					);
					
					 $data['EmailTo']  		  	= 	$data['email-to'];
					 $data['AttachmentPaths'] 	= 	$FilesArray;
					 $data['cc'] 				= 	trim($data['cc']);
					 $data['bcc'] 				= 	trim($data['bcc']);					 
					 $status 					= 	sendMail('emails.tickets.ticket', $data);
					if($status['status'] == 1)
					{
						TicketsConversation::create($ticketCoversationData);	
						if(!empty($files_array) && count($files_array)>0){	
							foreach($files_array as $key=> $array_file_data){
							@unlink($array_file_data['filepath']);	
							}
						}
						 DB::commit();	
						return generateResponse("Successfully Updated");
					}else{
						 return generateResponse("Problem Sending Email",true);
					}
				}
				catch (Exception $e){
					DB::rollback();
					return generateResponse($e->getMessage(),true);
				}
			}	
			 return generateResponse("invalid Ticket.",true);
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
	
	public function getConversationAttachment($ticketID,$attachmentID){
		
		$Ticketdata 	=   TicketsConversation::find($ticketID);	
				
		if($Ticketdata)
		{
			$attachments 	=   unserialize($Ticketdata->AttachmentPaths); print_r($attachments); exit;
			$attachment 	=   $attachments[$attachmentID]; echo $attachment; exit;
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
		if($Ticketdata)
		{ 	 $CloseStatus =  TicketsTable::getClosedTicketStatus(); 
			 $Ticketdata->update(array("Status"=>$CloseStatus));	
			// return Response::json(array("status" => "success", "message" => "Ticket Successfully Closed.","close_id"=>$CloseStatus)); 	
			 return generateResponse('Ticket Successfully Closed');
			 //return generateResponse("Ticket Successfully Closed");
		}
		return generateResponse("invalid Ticket",true,true);
	}
}