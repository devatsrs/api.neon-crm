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
		   $data['iDisplayStart']    +=1;
		   
		   if($data['LoginType']=='customer'){		
				   $agent		=	'';
				   $emails 		=	Account::GetAccountAllEmails(User::get_userID());				 
				   $query 		= 	"call prc_GetSystemTicketCustomer ('".$CompanyID."','".$search."','".$status."','".$priority."','".$Group."','".$agent."','".$emails."','".Messages::Received."',".( ceil($data['iDisplayStart']/$data['iDisplayLength']) )." ,".$data['iDisplayLength'].",'".$sort_column."','".$data['sSortDir_0']."',0)";  
		   }else{			 	  		   			   
			  	  $query 		= 	"call prc_GetSystemTicket ('".$CompanyID."','".$search."','".$status."','".$priority."','".$Group."','".$agent."','".Messages::Received."',".( ceil($data['iDisplayStart']/$data['iDisplayLength']) )." ,".$data['iDisplayLength'].",'".$sort_column."','".$data['sSortDir_0']."',0)";  
			}
		Log::info("query:".$query);
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
		
		Log::info(print_r($data,true));
		//Log::info(".....................................");
		//$RulesMessages      = 	TicketsTable::GetAgentSubmitRules();       
		if($data['LoginType']=='customer'){
			$RulesMessages      = 	TicketsTable::GetCustomerSubmitRules();       
		}else{
			$RulesMessages      = 	TicketsTable::GetAgentSubmitRules();       
		}
        $validator 			= 	Validator::make($data['Ticket'], $RulesMessages['rules'], $RulesMessages['messages']);
        if ($validator->fails()) {
			return generateResponse($validator->errors(),true);
        }
		
		
		 $files = '';
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
			if($data['LoginType']=='user')
			{	
				$email_from		   =  TicketGroups::where(["GroupID"=>$Ticketfields['default_group']])->pluck('GroupReplyAddress'); 
				$email_from_name   =  TicketGroups::where(["GroupID"=>$Ticketfields['default_group']])->pluck('GroupName'); 
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
					"Subject"=>isset($Ticketfields['default_subject'])?$Ticketfields['default_subject']:'',
					"Type"=>isset($Ticketfields['default_ticket_type'])?$Ticketfields['default_ticket_type']:0,
					"Status"=>isset($Ticketfields['default_status'])?$Ticketfields['default_status']:TicketsTable::getDefaultStatus(),
					"Priority"=>isset($Ticketfields['default_priority'])?$Ticketfields['default_priority']:TicketPriority::getDefaultPriorityStatus(),					
					"Description"=>isset($Ticketfields['default_description'])?$Ticketfields['default_description']:'',	 
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
				
				//create contact if email not found in system
			 	$AllEmails  =   Messages::GetAllSystemEmails();
				if(!in_array($RequesterEmail,$AllEmails))
				{
					$ContactData = array("Email"=>$RequesterEmail,"CompanyId"=>User::get_companyID());
					Contact::create($ContactData);
				}	 
				 $TicketData['email_from']  	= 	$email_from;
				 $TicketData['email_from_name'] = 	$email_from_name;
				 $TicketData['AttachmentPaths'] =   !empty($files)?unserialize($files):'';
				 
				 $logID =  SendTicketEmail('store',$TicketID,$TicketData);
				 TicketsTable::find($TicketID)->update(array("AccountEmailLogID"=>$logID));
				 
				 DB::commit();
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
		$post_data = Input::all();
	    if($id > 0)
		{	
			$data['TicketID'] 					=	 $id;
			$data['ticketdata']					=	 TicketsTable::find($id);
			$data['ticketdetaildata']			=	 TicketsDetails::where(["TicketID"=>$id])->get();								
			
			
			if($post_data['LoginType']=='customer'){		
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
			
			
			//Log::info(".....................................");
			$DetailPage 		=   isset($data['Page'])?$data['Page']:'all';
			
			//$RulesMessages      = 	TicketsTable::GetAgentSubmitRules($DetailPage);       
			if($data['LoginType']=='customer'){
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
				
			
				if($data['LoginType']=='user')
				{	
					$RequesterData 	   =  explode(" <",$Ticketfields['default_requester']);
					$RequesterName	   =  $RequesterData[0];
					$RequesterEmail	   =  substr($RequesterData[1],0,strlen($RequesterData[1])-1);		
					$email_from		   =  TicketGroups::where(["GroupID"=>$Ticketfields['default_group']])->pluck('GroupReplyAddress'); 
					$email_from_name   =  TicketGroups::where(["GroupID"=>$Ticketfields['default_group']])->pluck('GroupName'); 
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
						if(!in_array($key,Ticketfields::$staticfields))
						{
							$TicketFieldsID =  Ticketfields::where(["FieldType"=>$key])->pluck('TicketFieldsID');
							TicketsDetails::insert(array("TicketID"=>$id,"FieldID"=>$TicketFieldsID,"FieldValue"=>$TicketfieldsData));
						}
					}	
					
					 $TicketData['email_from']  	= 	$email_from;
					 $TicketData['email_from_name'] = 	$email_from_name;				
					 SendTicketEmail('update',$ticketdata,$TicketData);
					 DB::commit();
					 return generateResponse('Ticket Successfully Updated');
				 }catch (Exception $ex){ 	
					  DB::rollback();
					  return generateResponse($ex->getMessage(), true, true);
				 } 
		  }else{
		  	return generateResponse("invalid Ticket",true,true);
		  }
	  }
	
	
	
	 function UpdateDetailPage($id){
	  
	    $this->IsValidLicense();
		$data 			= 	Input::all();  
		$ticketdata		=	 TicketsTable::find($id);
	    if($ticketdata)
		{
			if(!isset($data['Ticket']))
			{
				return generateResponse("Please submit required fields.",true);
			}
			Log::info(print_r($data,true));
			//Log::info(".....................................");
			$DetailPage 		=   isset($data['Page'])?$data['Page']:'all'; Log::info($DetailPage);
			if($data['LoginType']=='customer'){
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
				//TicketsConversation::where(array('TicketID'=>$id))->delete();
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
		   $CompanyID 					 = 		User::get_companyID(); 
		   $data['status']	 			 =   	TicketsTable::getTicketStatus();
		   $data['Priority']		 	 =	 	TicketPriority::getTicketPriority();
		   $data['Groups']			 	 =	 	TicketGroups::getTicketGroups(); 
		   $Agents			 			 = 	 	User::getUserIDListAll(0);
		   $data['Agents']				 = 	 	$row =  array("0"=> "Select")+json_decode(json_encode($Agents),true);   
		   $data['CloseStatus'] 		 =  	TicketsTable::getClosedTicketStatus();  //close status id for ticket 
		   $data['ticketdata']			 =	    TicketsTable::find($postdata['id']);
		   $data['ticketdetaildata']	 =	    TicketsDetails::where(["TicketID"=>$postdata['id']])->get();	
		   							
		   if($postdata['LoginType']=='customer'){		
				$data['Ticketfields']	=	DB::table('tblTicketfields')->Where(['CustomerDisplay'=>1])->orderBy('FieldOrder', 'asc')->get(); 
			}else{
				$data['Ticketfields']	=	DB::table('tblTicketfields')->orderBy('FieldOrder', 'asc')->get();
			}
		   $data['ticketSavedData'] 	 = 		TicketsTable::SetUpdateValues($data['ticketdata'],$data['ticketdetaildata'],$data['Ticketfields']);
		  
		   $data['agentsAll'] = DB::table('tblTicketGroupAgents')
            ->join('tblUser', 'tblUser.UserID', '=', 'tblTicketGroupAgents.UserID')->distinct()          
            ->select('tblUser.UserID', 'tblUser.FirstName', 'tblUser.LastName')
            ->get();
		   
			if($postdata['id'])
			{
				//$data['TicketConversation']	 =		TicketsConversation::where(array('TicketID'=>$postdata['id']))->get();
				if($data['ticketdata']->AccountEmailLogID>0){
				$data['TicketConversation'] 	 = 		AccountEmailLog::where(['EmailParent'=>$data['ticketdata']->AccountEmailLogID,'CompanyID'=>$CompanyID])->get();
				}else{
					$data['TicketConversation'] 	 = array();
				}
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
				$postdata['AccountEmail'] 		= 	  $postdata['response_data']->Requester;	
				$postdata['parent_id']			=	  0;
			}else{
				$postdata['response_data']      =     AccountEmailLog::find($ticket_number);
				$postdata['AccountEmail'] 		= 	  '';
				$postdata['parent_id']			=	  '';
				$postdata['response_data']->Description		=	  $postdata['response_data']->Message;
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
					
					$email_from_data   =  TicketGroups::where(["GroupID"=>$ticketdata->Group])->select('GroupReplyAddress','GroupName')->get(); 
					//$email_from_name   =  TicketGroups::where(["GroupID"=>$ticketdata->Group])->pluck('GroupName'); 
					Log::info('email_from_data');
					Log::info(print_r($email_from_data[0],true));
					 $files = '';
					 $FilesArray = array();
					 if (isset($data['file']) && !empty($data['file'])) {
						 $FilesArray = json_decode($data['file'],true);
						$files = serialize(json_decode($data['file'],true));
					}
					 
					 $data['EmailFrom']  		=   $email_from_data[0]->GroupReplyAddress;
					 $data['CompanyName'] 	    =   $email_from_data[0]->GroupName;
					 $data['EmailTo']  		  	= 	$data['email-to'];
					 $data['AttachmentPaths'] 	= 	$FilesArray;
					 $data['cc'] 				= 	trim($data['cc']);
					 $data['bcc'] 				= 	trim($data['bcc']);					 
					 $status 					= 	sendMail('emails.tickets.ticket', $data);
					if($status['status'] == 1)
					{	
						$message_id = isset($status['message_id'])?$status['message_id']:'';
						
						$logData = ['EmailFrom'=>$email_from_data[0]->GroupReplyAddress,
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
						
						/*if(!empty($files_array) && count($files_array)>0){	
							foreach($files_array as $key=> $array_file_data){
							@unlink($array_file_data['filepath']);	
							}
						}*/
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
	
	
	function SendMailTicket(){
		
	    $this->IsValidLicense();
		$data 			= 	Input::all();  

		if(!isset($data['Ticket'])){
			return generateResponse("Please submit required fields.",true);
		}
		
		
		//Log::info(".....................................");
		//$RulesMessages      = 	TicketsTable::GetAgentSubmitRules();       
		if($data['LoginType']=='customer'){
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
			$email_from_data   				= 	TicketGroups::where(["GroupReplyAddress"=>$data['email-from']])->select('GroupReplyAddress','GroupName','GroupID')->get(); 
			$Ticketfields      				= 	$data['Ticket'];
			$Ticketfields['default_group']  = 	$email_from_data[0]->GroupID;
			$RequesterEmail	  			 	=  	trim($data['email-to']);					
			Log::info("ticket group: ".$data['email-from']);
			Log::info(print_r($email_from_data,true));
		
			if($data['LoginType']=='user')
			{
				$TicketData = array(
					"CompanyID"=>User::get_companyID(),
					"Requester"=>$RequesterEmail,
					//"RequesterName"=>$RequesterName,
					"Subject"=>$data['Subject'],
					"Type"=>$Ticketfields['default_ticket_type'],
					"Status"=>$Ticketfields['default_status'],
					"Priority"=>$Ticketfields['default_priority'],
					"Group"=>$Ticketfields['default_group'],
					//"Agent"=>$Ticketfields['default_agent'],
					"Description"=>$data['Message'],	
					"AttachmentPaths"=>$files,
					"created_at"=>date("Y-m-d H:i:s"),
					"created_by"=>User::get_user_full_name()
				);
			}else{
				$TicketData = array(
					"CompanyID"=>User::get_companyID(),
					"Requester"=>$RequesterEmail,
					//"RequesterName"=>$RequesterName,
					"Subject"=>isset($data['Subject'])?$data['Subject']:'',
					"Type"=>isset($Ticketfields['default_ticket_type'])?$Ticketfields['default_ticket_type']:0,
					"Status"=>isset($Ticketfields['default_status'])?$Ticketfields['default_status']:TicketsTable::getDefaultStatus(),
					"Priority"=>isset($Ticketfields['default_priority'])?$Ticketfields['default_priority']:TicketPriority::getDefaultPriorityStatus(),					
					"Description"=>isset($data['Message'])?$data['Message']:'',	 
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
				
				//create contact if email not found in system
				$AllEmails  =   Messages::GetAllSystemEmails();
				if(!in_array($RequesterEmail,$AllEmails))
				{
					$ContactData = array("Email"=>$RequesterEmail,"CompanyId"=>User::get_companyID());
					Contact::create($ContactData);
				}
				 $TicketData['email_from']  	= 	$email_from_data[0]->GroupReplyAddress;
				 $TicketData['email_from_name'] = 	$email_from_data[0]->GroupName;			  
				 $logID 						= 	SendComposeTicketEmail($TicketData); Log::info('logID'); Log::info(print_r($logID,true));
				 if(!isset($logID['status'])){
				  	TicketsTable::find($TicketID)->update(array("AccountEmailLogID"=>$logID));
				 }else{
				 	return generateResponse($logID['message'], true, true);
				 }
				 DB::commit();		
				 return generateResponse('Ticket Successfully Created');
      		 }catch (Exception $ex){ 	
			      DB::rollback();
				  return generateResponse($ex->getMessage(), true, true);
       		 }    
	  		
	}
}