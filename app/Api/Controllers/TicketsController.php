<?php

namespace Api\Controllers;

use Dingo\Api\Http\Request;
use Api\Model\AccountBalance;
use Api\Model\AccountBalanceHistory;
use Api\Model\DataTableSql;
use Api\Model\User;
use Api\Model\Account;
use Api\Model\Note;
use Api\Model\Invoice;
use Api\Model\Ticket;
use Api\Model\Company;
use Api\Model\CompanySetting;
use Api\Model\CompanyConfiguration;
use Api\Model\AccountEmailLog;
use App\Http\Requests;
use Dingo\Api\Facade\API;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Api\Model\Tags;
use Api\Model\PaymentGateway;
use Api\Model\AccountPaymentProfile;
use App\Freshdesk;
use App\Imap;

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
	
	 public function index(){
		 
			$this->IsValidLicense();
			$CompanyID 		 			= 	 User::get_companyID(); 
			$data 			 			= 	 array();	
			$status			 			=    TicketsTable::getTicketStatus();
			$Priority		 			=	 TicketPriority::getTicketPriority();
			$Groups			 			=	 TicketGroups::getTicketGroups(); 
			$Agents			 			= 	 User::getUserIDListAll(0);
			$Agents			 			= 	 array("0"=> "Select")+$Agents;
			$Type			 			=    TicketsTable::getTicketType();
			/////////
			$Sortcolumns				=	 TicketsTable::$Sortcolumns;
			$data['iSortCol_0']			=	 TicketsTable::$defaultSortField;
			$data['sSortDir_0']			=	 TicketsTable::$defaultSortType;
			
			$data['iDisplayStart']  	= 	 0;
			$data['iDisplayLength'] 	= 	 Config::get('app.pageSize');
			$companyID 					= 	 User::get_companyID();
			$array						= 	 $this->GetResult($data);
			$resultdata   				= 	 $array['resultdata'];	
			$resultpage  				= 	 $array['resultpage'];		 
			$result 					= 	 $resultdata->data['ResultCurrentPage'];
			$totalResults 				= 	 $resultdata->data['TotalResults'][0]->totalcount; 
			$iTotalDisplayRecords 		= 	 $resultpage['iTotalDisplayRecords'];
			$iDisplayLength 			= 	 $data['iDisplayLength'];
			$data['currentpage'] 		= 	 0;
			
		
		//echo "<pre>";		print_r($result);			exit;
        return View::make('tickets.index', compact('PageResult','result','iDisplayLength','iTotalDisplayRecords','totalResults','data','EscalationTimes_json','status','Priority','Groups','Agents','Type',"Sortcolumns"));  
			/////////
	  }	
	  
	  public function ajex_result() {
		
	    $data 						= 	Input::all();
		$data['currentpages']		=	$data['currentpage'];
		if($data['clicktype']=='next'){
			$data['iDisplayStart']  	= 	($data['currentpage']+1)*$data['per_page'];
			$data['currentpage']++;
		}
		elseif($data['clicktype']=='back'){
			$data['iDisplayStart']  	= 	($data['currentpage']-1)*$data['per_page'];
			$data['currentpage']--;
		}else
		{
			$data['iDisplayStart'] = 0;
		}	
		
		$data['Search'] 			= 	 $data['formData']['Search'];
		$data['status'] 			= 	 isset($data['formData']['status'])?$data['formData']['status']:'';		
		$data['priority']	 		= 	 isset($data['formData']['priority'])?$data['formData']['priority']:'';
		$data['group'] 				= 	 isset($data['formData']['group'])?$data['formData']['group']:'';		
		$data['agent']				= 	 isset($data['formData']['agent'])?$data['formData']['agent']:'';
		$data['iSortCol_0']			= 	 $data['sort_fld'];
		$data['sSortDir_0']			= 	 $data['sort_type'];
		$data['iDisplayLength'] 	= 	 $data['per_page'];
		$companyID					= 	 User::get_companyID();
		$array						=  	 $this->GetResult($data);
		$resultdata   				=  	 $array['resultdata'];	
		$resultpage  				=  	 $array['resultpage'];			
		$result 					= 	 $resultdata->data['ResultCurrentPage'];
		$totalResults 				=    $resultdata->data['TotalResults'][0]->totalcount; 
		$iTotalDisplayRecords 		= 	 $resultpage['iTotalDisplayRecords'];
		$iDisplayLength 			= 	 $data['iDisplayLength'];
		$Sortcolumns				=	 TicketsTable::$Sortcolumns;
		//echo "<pre>";		print_r($resultpage);			exit;
		if(count($result)<1)
		{
			if(isset($data['SearchStr']) && $data['SearchStr']!='' && $data['currentpage']==0){
				
				return json_encode(array("result"=>"No Result found for ".$data['SearchStr']));
			}else{			
				return '';
			}
		} 
       return   View::make('tickets.ajaxresults', compact('PageResult','result','iDisplayLength','iTotalDisplayRecords','totalResults','data','boxtype','TotalDraft','TotalUnreads','Sortcolumns'));     
	   
	   //return array('currentpage'=>$data['currentpage'],"Body"=>$body,"result"=>count($result));
    
	}
	  
	  
	  function GetResult($data){
		
		   $CompanyID 				= 	User::get_companyID();
		   $search		 			=	isset($data['Search'])?$data['Search']:'';	   		   
		   $status					=	isset($data['status'])?is_array($data['status'])?implode(",",$data['status']):'':'';		   
		   $priority				=	isset($data['priority'])?is_array($data['priority'])?implode(",",$data['priority']):'':'';
		   $Group					=	isset($data['group'])?is_array($data['group'])?implode(",",$data['group']):'':'';
		   if(User::is_admin())	{		
		   	$agent					=	isset($data['agent'])?is_array($data['agent'])?implode(",",$data['agent']):'':'';
		   }else{
			 $agent					=	user::get_userID();
		   }
		   $columns 	 			= 	array('TicketID','Subject','Requester','Type','Status','Priority','Group','Agent','created_at');
		  // $data['iSortCol_0']		=	'0';
		   $sort_column 			= 	$data['iSortCol_0'];
	
			$query 	= 	"call prc_GetSystemTicket (".$CompanyID.",'".$search."','".$status."','".$priority."','".$Group."','".$agent."',".( ceil($data['iDisplayStart']/$data['iDisplayLength']) )." ,".$data['iDisplayLength'].",'".$sort_column."','".$data['sSortDir_0']."',0)";  
		
		$resultdata   	=  DataTableSql::of($query)->getProcResult(array('ResultCurrentPage','TotalResults'));	
		$resultpage  	=  DataTableSql::of($query)->make(false);
		//echo "<pre>";		print_r($resultdata);			exit;
		return array("resultdata"=>$resultdata,"resultpage"=>$resultpage);
	}
	  
	 /* function ajax_datagrid($type)
	  {	
		   $CompanyID 				= 	User::get_companyID();       
		   $data 					= 	Input::all();
		   $data['iDisplayStart'] 	+=	1;
		   $search		 			=	isset($data['Search'])?$data['Search']:'';	   		   
		   $status					=	isset($data['status'])?$data['status']:0;		   
		   $priority				=	isset($data['priority'])?$data['priority']:0;
		   $Group					=	isset($data['group'])?$data['group']:0;
		   $agent					=	isset($data['agent'])?$data['agent']:0;
		   $columns 	 			= 	array('TicketID','Subject','Requester','Type','Status','Priority','Group','Agent');		  
		   $sort_column 			= 	$data['iSortCol_0'];
			
			$query 	= 	"call prc_GetSystemTicket (".$CompanyID.",'".$search."','".$status."','".$priority."','".$Group."','".$agent."',".( ceil($data['iDisplayStart']/$data['iDisplayLength']) )." ,".$data['iDisplayLength'].",'".$sort_column."','".$data['sSortDir_0']."'";  
	
			if(isset($data['Export']) && $data['Export'] == 1) {
				$excel_data  = DB::select($query.',1)');
				$excel_data = json_decode(json_encode($excel_data),true);
	
				if($type=='csv'){
					$file_path = getenv('UPLOAD_PATH') .'/Tickets.csv';
					$NeonExcel = new NeonExcelIO($file_path);
					$NeonExcel->download_csv($excel_data);
				}elseif($type=='xlsx'){
					$file_path = getenv('UPLOAD_PATH') .'/Tickets.xls';
					$NeonExcel = new NeonExcelIO($file_path);
					$NeonExcel->download_excel($excel_data);
				}          
			}
			$query .=',0)';  Log::info($query);
	
			return DataTableSql::of($query)->make();
	 	}  
	*/	
		function add()
		{	
			$this->IsValidLicense();				
			$Ticketfields	   			=	 DB::table('tblTicketfields')->orderBy('FieldOrder', 'asc')->get(); 
			$Agents			   			= 	 User::getUserIDListAll(0);
			$AllUsers		   			= 	 User::getUserIDListAll(0); 
			$AllUsers[0] 	   			= 	 'None';	
			ksort($AllUsers);			
			$CompanyID 		   			= 	 User::get_companyID();	
			$htmlgroupID 	   			= 	 '';
			$htmlagentID       			= 	 '';
			$random_token	  			=	 get_random_number();
			$response_api_extensions 	=    Get_Api_file_extentsions();
		   if(isset($response_api_extensions->headers)){ return	Redirect::to('/logout'); 	}	
		    $response_extensions		=	json_encode($response_api_extensions['allowed_extensions']); 
			$max_file_size				=	get_max_file_size();	
			$AllEmails 					= 	implode(",",(Messages::GetAllSystemEmailsWithName(0))); 
			
		   $agentsAll = DB::table('tblTicketGroupAgents')
            ->join('tblUser', 'tblUser.UserID', '=', 'tblTicketGroupAgents.UserID')->distinct()          
            ->select('tblUser.UserID', 'tblUser.FirstName', 'tblUser.LastName')
            ->get();
			
			
		   
			//echo "<pre>";			print_r($agentsAll);			echo "</pre>";					exit;
			return View::make('tickets.create', compact('data','AllUsers','Agents','Ticketfields','CompanyID','agentsAll','htmlgroupID','htmlagentID','random_token','response_extensions','max_file_size','AllEmails'));  
	  }	
	  
	public function edit($id)
	{
		$this->IsValidLicense();
		
        if($id > 0)
		{	$TicketID 					=	$id;
			$ticketdata					=	 TicketsTable::find($id);
			$ticketdetaildata			=	 TicketsDetails::where(["TicketID"=>$id])->get();								
			$Ticketfields	   			=	 DB::table('tblTicketfields')->orderBy('FieldOrder', 'asc')->get(); 
			$Agents			   			= 	 User::getUserIDListAll(0);
			$AllUsers		   			= 	 User::getUserIDListAll(0); 
			$AllUsers[0] 	   			= 	 'None';	
			ksort($AllUsers);			
			$CompanyID 		   			= 	 User::get_companyID();	
			$htmlgroupID 	   			= 	 '';
			$htmlagentID       			= 	 '';
			$random_token	  			=	 get_random_number();
			$response_api_extensions 	=    Get_Api_file_extentsions();
		   if(isset($response_api_extensions->headers)){ return	Redirect::to('/logout'); 	}	
		    $response_extensions		=	json_encode($response_api_extensions['allowed_extensions']);
			$max_file_size				=	get_max_file_size();	
			$AllEmails 					= 	implode(",",(Messages::GetAllSystemEmailsWithName(0))); 
			
		   $agentsAll = DB::table('tblTicketGroupAgents')
            ->join('tblUser', 'tblUser.UserID', '=', 'tblTicketGroupAgents.UserID')->distinct()          
            ->select('tblUser.UserID', 'tblUser.FirstName', 'tblUser.LastName')
            ->get();
		    $ticketSavedData = 	TicketsTable::SetUpdateValues($ticketdata,$ticketdetaildata,$Ticketfields);
			//echo "<pre>";			print_r($agentsAll);			echo "</pre>";					exit;
			return View::make('tickets.edit', compact('data','AllUsers','Agents','Ticketfields','CompanyID','agentsAll','htmlgroupID','htmlagentID','random_token','response_extensions','max_file_size','AllEmails','ticketSavedData','TicketID'));  
		}
	}
	  
	  function Store(){
	    $this->IsValidLicense();
		$data 			= 	Input::all();  

				
		if(!isset($data['Ticket']))
		{
			return Response::json(array("status" => "failed", "message" =>"Please submit required fields."));
		}
		
		//Log::info(print_r($data,true));
		//Log::info(".....................................");
		$RulesMessages      = 	TicketsTable::GetAgentSubmitRules();       
        $validator 			= 	Validator::make($data['Ticket'], $RulesMessages['rules'], $RulesMessages['messages']);
        if ($validator->fails()) {
            return json_validator_response($validator);
        }
		
		
		 $files					=	'';
		 $attachmentsinfo        =	$data['attachmentsinfo']; 
        if(!empty($attachmentsinfo) && count($attachmentsinfo)>0){
            $files_array = json_decode($attachmentsinfo,true);
        }

        if(!empty($files_array) && count($files_array)>0)
		{
            $FilesArray = array();
			
            foreach($files_array as $key=> $array_file_data)
			{
                $file_name  		= 	basename($array_file_data['filepath']); 
                $amazonPath 		= 	AmazonS3::generate_upload_path(AmazonS3::$dir['TICKET_ATTACHMENT']);
                $destinationPath 	= 	getenv("UPLOAD_PATH") . '/' . $amazonPath;

                if (!file_exists($destinationPath))
				{
                    mkdir($destinationPath, 0777, true);
                }
                
				copy($array_file_data['filepath'], $destinationPath . $file_name);
				
                if (!AmazonS3::upload($destinationPath . $file_name, $amazonPath))
				{
                    return Response::json(array("status" => "failed", "message" => "Failed to upload file." ));
                }
				
                $FilesArray[] = array ("filename"=>$array_file_data['filename'],"filepath"=>$amazonPath . $file_name);
                @unlink($array_file_data['filepath']);
            }
            $files		=	serialize($FilesArray);
		}
		
			$Ticketfields      =  $data['Ticket'];
			$RequesterData 	   =  explode(" <",$Ticketfields['default_requester']);
			$RequesterName	   =  $RequesterData[0];
			$RequesterEmail	   =  substr($RequesterData[1],0,strlen($RequesterData[1])-1);	
		
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
            	return Response::json(array("status" => "success", "message" => "Ticket Successfully Created",'LastID'=>$TicketID));
      		 }catch (Exception $ex){ 	
			      DB::rollback();
				 return Response::json(array("status" => "failed", "message" =>$ex->getMessage()));
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
				return Response::json(array("status" => "failed", "message" =>"Please submit required fields."));
			}
			
			//Log::info(print_r($data,true));
			//Log::info(".....................................");
			$RulesMessages      = 	TicketsTable::GetAgentSubmitRules();       
			$validator 			= 	Validator::make($data['Ticket'], $RulesMessages['rules'], $RulesMessages['messages']);
			if ($validator->fails()) {
				return json_validator_response($validator);
			}
			
			
			 $files					=	'';
			 $attachmentsinfo        =	$data['attachmentsinfo']; 
			if(!empty($attachmentsinfo) && count($attachmentsinfo)>0){
				$files_array = json_decode($attachmentsinfo,true);
			}
	
			if(!empty($files_array) && count($files_array)>0)
			{
				$FilesArray = array();
				
				foreach($files_array as $key=> $array_file_data)
				{
					$file_name  		= 	basename($array_file_data['filepath']); 
					$amazonPath 		= 	AmazonS3::generate_upload_path(AmazonS3::$dir['TICKET_ATTACHMENT']);
					$destinationPath 	= 	getenv("UPLOAD_PATH") . '/' . $amazonPath;
	
					if (!file_exists($destinationPath))
					{
						mkdir($destinationPath, 0777, true);
					}
					
					copy($array_file_data['filepath'], $destinationPath . $file_name);
					
					if (!AmazonS3::upload($destinationPath . $file_name, $amazonPath))
					{
						return Response::json(array("status" => "failed", "message" => "Failed to upload file." ));
					}
					
					$FilesArray[] = array ("filename"=>$array_file_data['filename'],"filepath"=>$amazonPath . $file_name);
					@unlink($array_file_data['filepath']);
				}
				$files		=	serialize($FilesArray);
			}
			
				$Ticketfields 	   =  $data['Ticket'];
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
					return Response::json(array("status" => "success", "message" => "Ticket Successfully Updated",'LastID'=>$id));
				 }catch (Exception $ex){ 	
					  DB::rollback();
					 return Response::json(array("status" => "failed", "message" =>$ex->getMessage()));
				 } 
		  }else{
		  	return Response::json(array("status" => "failed", "message" =>"invalid Ticket."));
		  }
	  }
	  
	  function uploadFile(){
        $data       =  Input::all();
        $attachment    =  Input::file('emailattachment');
        if(!empty($attachment)) {
            try { 
                $data['file'] = $attachment;
                $returnArray = UploadFile::UploadFileLocal($data);
                return Response::json(array("status" => "success", "message" => '','data'=>$returnArray));
            } catch (Exception $ex) {
                return Response::json(array("status" => "failed", "message" => $ex->getMessage()));
            }
        }

    }

    function deleteUploadFile(){
        $data    =  Input::all();
        try {
            UploadFile::DeleteUploadFileLocal($data);
            return Response::json(array("status" => "success", "message" => 'Attachments delete successfully'));
        } catch (Exception $ex) {
            return Response::json(array("status" => "failed", "message" => $ex->getMessage()));
        }
    }
	
	public function delete($id)
    {
        if( $id > 0){
            try{
                DB::beginTransaction();
                TicketsTable::where(["TicketID"=>$id])->delete();
                TicketsDetails::where(["TicketID"=>$id])->delete();
				TicketsConversation::where(array('TicketID'=>$id))->delete();
                DB::commit();
                return Response::json(array("status" => "success", "message" => "Ticket Successfully Deleted"));

            }catch (Exception $e){
                DB::rollback();
                return Response::json(array("status" => "failed", "message" =>$e->getMessage() ));
            }

        }
    }
	
	function Detail($id){
		 $this->IsValidLicense();
		 if($id)
		 {
  		   $ticketdata		=	 TicketsTable::find($id);
		   
		   if(!User::is_admin())
		   {
			  if($ticketdata->Agent!=user::get_userID())
			  {
			 	 	App::abort(403, 'You have not access to' . Request::url());		
			  }
		   }
		   
		   $status			 			 =   TicketsTable::getTicketStatus();
		   $Priority		 			 =	 TicketPriority::getTicketPriority();
		   $Groups			 			 =	 TicketGroups::getTicketGroups(); 
		   $Agents			 			 = 	 User::getUserIDListAll(0);
		   $Agents						 = 	 array("0"=> "Select")+$Agents;		   
		   $response_api_extensions 	 =   Get_Api_file_extentsions();
		   $max_file_size				 =	 get_max_file_size();	
		   
		   if(isset($response_api_extensions->headers)){ return	Redirect::to('/logout'); 	}	
		    $response_extensions		 =	json_encode($response_api_extensions['allowed_extensions']); 
	   		$TicketConversation			 =	TicketsConversation::where(array('TicketID'=>$id))->get();
			
			if(User::is_admin())
			{
				$NextTicket 				 =	TicketsTable::find(TicketsTable::WhereRaw("TicketID > ".$id)->pluck('TicketID'));
				$PrevTicket 				 =	TicketsTable::find(TicketsTable::WhereRaw("TicketID < ".$id)->pluck('TicketID'));
			}else{
				$NextTicket 				 =	TicketsTable::find(TicketsTable::WhereRaw("TicketID > ".$id)->where(array("Agent"=>user::get_userID()))->pluck('TicketID')); 
				$PrevTicket 				 =	TicketsTable::find(TicketsTable::WhereRaw("TicketID < ".$id)->where(array("Agent"=>user::get_userID()))->pluck('TicketID')); 
			}
			
		   return View::make('tickets.detail', compact('data','ticketdata','status','Priority','Groups','Agents','response_extensions','max_file_size','TicketConversation',"NextTicket","PrevTicket"));  		  
		 }
	}
	
	function TicketAction(){
		
		$data 		   		= 	  Input::all();
		$action_type   		=     $data['action_type'];
		$ticket_number  	=     $data['ticket_number'];
		$ticket_type		=	  $data['ticket_type'];
		
		
		if($ticket_type=='parent'){
			$response_data      =     TicketsTable::find($ticket_number);
			$AccountEmail 		= 	  $response_data->Requester;	
			$parent_id			=	  0;
		}else{
			$response_data      =     TicketsConversation::find($ticket_number);
			$AccountEmail 		= 	  TicketsConversation::where(array('TicketConversationID'=>$ticket_number))->pluck('TicketTo');
			$parent_id			=	  TicketsConversation::where(array('TicketConversationID'=>$ticket_number))->pluck('TicketConversationID');
		}
		//$parent_id          =  	  $response_data['EmailParent'];	
		
		if($action_type=='forward'){ //attach current email attachments
			$data['uploadtext']  = 	 UploadFile::DownloadFileLocal($response_data->AttachmentPaths);
			Log::info(print_r($data,true));
		}
		return View::make('tickets.ticketaction', compact('data','response_data','action_type','uploadtext','AccountEmail','parent_id'));  			
		
	}
	
	function UpdateTicketAttributes($id)
	{
		 $this->IsValidLicense();
		 if($id)
		 {
			   $ticketdata		=	 TicketsTable::find($id);
			   if($ticketdata)
			   {
				   if(!User::is_admin())
				   {
					  if($ticketdata->Agent!=user::get_userID())
					  {
						    return Response::json(array("status" => "failed", "message" =>"You have not access to update this ticket" ));
					  }
				   }
				   $data 	= 	Input::all();  
				   
				   $TicketData = array(
					"Status"=>$data['status'],
					"Priority"=>$data['priority'],
					"Group"=>$data['group'],
					"Agent"=>$data['agent'],				
					"updated_at"=>date("Y-m-d H:i:s"),
					"updated_by"=>User::get_user_full_name()
				);
				$ticketdata->update($TicketData);	
				return Response::json(array("status" => "success", "message" => "Ticket Successfully Updated")); 
			}			
		 }
		return Response::json(array("status" => "failed", "message" =>"invalid Ticket." ));
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
						return json_validator_response($validator);
					}
					$files					=	'';
					$attachmentsinfo        =	$data['attachmentsinfo']; 
					$FilesArray = array();
					if(!empty($attachmentsinfo) && count($attachmentsinfo)>0){
						$files_array = json_decode($attachmentsinfo,true);
					}
					
					
					if(!empty($files_array) && count($files_array)>0)
					{
						foreach($files_array as $key=> $array_file_data)
						{
							$file_name  		= 	basename($array_file_data['filepath']); 
							$amazonPath 		= 	AmazonS3::generate_upload_path(AmazonS3::$dir['TICKET_ATTACHMENT']);
							$destinationPath 	= 	getenv("UPLOAD_PATH") . '/' . $amazonPath;
			
							if (!file_exists($destinationPath))
							{
								mkdir($destinationPath, 0777, true);
							}
							
							copy($array_file_data['filepath'], $destinationPath . $file_name);
							
							if (!AmazonS3::upload($destinationPath . $file_name, $amazonPath))
							{
								return Response::json(array("status" => "failed", "message" => "Failed to upload file." ));
							}
							
							$FilesArray[] = array ("filename"=>$array_file_data['filename'],"filepath"=>$amazonPath . $file_name);
							//@unlink($array_file_data['filepath']);
						}
						$files		=	serialize($FilesArray);
					}
					
					$ticketCoversationData = array(
						"TicketID"=>$id,
						"TicketTo"=>trim($data['email-to']),
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
							return Response::json(array("status" => "success", "message" => "Successfully Updated")); 		
					}else{
						 return Response::json(array("status" => "failed", "message" => "Problem Sending Email."));
					}
				}
				catch (Exception $e){
					DB::rollback();
					return Response::json(array("status" => "failed", "message" =>$e->getMessage() ));
				}
			}	
			 return Response::json(array("status" => "failed", "message" =>"invalid Ticket." ));
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
		{
			 $CloseStatus =  TicketsTable::getClosedTicketStatus(); 
			 $Ticketdata->update(array("Status"=>$CloseStatus));	
			 return Response::json(array("status" => "success", "message" => "Ticket Successfully Closed.","close_id"=>$CloseStatus)); 	
		}
		return Response::json(array("status" => "failed", "message" =>"invalid Ticket." ));
	}
}