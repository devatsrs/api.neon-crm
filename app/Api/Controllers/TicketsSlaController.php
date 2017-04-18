<?php
namespace Api\Controllers;

use Dingo\Api\Http\Request;
use Api\Model\DataTableSql;
use Api\Model\User;
use Api\Model\TicketSla;
use Api\Model\TicketSlaTarget;
use Api\Model\TicketSlaPolicyApplyTo;
use Api\Model\TicketSlaPolicyViolation;
use App\Http\Requests;
use Dingo\Api\Facade\API;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Api\Model\Account;
use Api\Model\TicketGroupAgents;
use Api\Model\TicketPriority;
use Api\Model\TicketGroups;

class TicketsSlaController extends BaseController {

	public function __construct(Request $request)
    {
        $this->middleware('jwt.auth');
        Parent::__Construct($request);
    }

	 public function ajax_datagrid() {      
		try
		{
		   $CompanyID 				= 	User::get_companyID();       
		   $data 					= 	Input::all(); 
		   $data['iDisplayStart'] 	+=	1;
		   $columns 	 			= 	array('Name','Description');
		   $sort_column 			= 	$columns[$data['iSortCol_0']];
			
			$query 	= 	"call prc_GetTicketSLA (".$CompanyID.",".( ceil($data['iDisplayStart']/$data['iDisplayLength']) )." ,".$data['iDisplayLength'].",'".$sort_column."','".$data['sSortDir_0']."'"; 
	
			if(isset($data['Export']) && $data['Export'] == 1) {
				$result = DB::select($query . ',1)');
			}else{
				$query .=',0)';  
				$result =  DataTableSql::of($query)->make(); 
			} 
			return generateResponse('',false,false,$result);
		} catch (\Exception $e) {
            Log::info($e);
             return generateResponse($ex->getMessage(),true,true);
        }		
    }
		
	function store(){
		    $data 			= 	Input::all(); 	Log::info(print_r($data,true));
		    $companyID 		=   User::get_companyID();    
			
			$rules = array(
				'Name' =>  'required|unique:tblTicketSla,Name,NULL,TicketSlaID,CompanyID,'. $companyID,
			);
			
			$messages		=	array();
			if(!isset($data['Apply'])){
				$messages 	=  	array("Apply.required"=>"You need to add at least 1 condition in 'Apply this to' section");
				$rules 		= 	array_merge($rules,array("Apply"=>"required"));					
			}	
		
			$validator 	= 	Validator::make($data,$rules);
			
			if ($validator->fails()) {
				   return generateResponse($validator->errors(),true);
			}	
			
		   try{
			
			 DB::beginTransaction();
					 
			 $SaveData = array(
			 	 "CompanyID"=>$companyID,
				 "Name"=>$data['Name'],
				 "Description"=>$data['Description'],
				 "created_at"=>date('Y-m-d H:i:s'),
				 "created_by"=>User::get_user_full_name()
			 );
			 
			 $ID 	=	 TicketSla::insertGetId($SaveData);
			 
			 if($ID)
			 {		
			 		//saving Targets
					
					$Targets = $data['Target'];
					
					foreach($Targets as $key => $TargetsData)
					{ 
						 $SaveTargetsData = array(
							 "TicketSlaID"=>$ID,
							 "PritiryID"=>TicketPriority::getPriorityIDByStatus($key),
							 "RespondWithinTimeValue"=>$TargetsData["RespondTime"],
							 "RespondWithinTimeType"=>$TargetsData["RespondType"],
							 "ResolveWithinTimeValue"=>$TargetsData["ResolveTime"],
							 "ResolveWithinTimeType"=>$TargetsData["ResolveType"],
							 "OperationalHrs"=>$TargetsData["SlaOperationalHours"],
							 "EscalationEmail"=>isset($TargetsData["Escalationemail"])?1:0,
						 );
						 
						TicketSlaTarget::create($SaveTargetsData);
						
					}
					
					
					//saving Applys
					if(isset($data["Apply"]) && count($data["Apply"])>0)
					{
						
							$Groups	 		=	 isset($data["Apply"]['Groups'])?implode(",",$data["Apply"]['Groups']):"";	
							$TicketTypes	=	 isset($data["Apply"]['TicketTypes'])?implode(",",$data["Apply"]['TicketTypes']):"";								
							$Accounts 		=	 isset($data["Apply"]['Accounts'])?implode(",",$data["Apply"]['Accounts']):"";	
						
							$ApplyDataSave = array(
								 "TicketSlaID"=>$ID,
								 "GroupFilter"=>$Groups,
								 "TypeFilter"=>$TicketTypes,
								 "CompanyFilter"=>$Accounts,							 
							 );
							 
							TicketSlaPolicyApplyTo::create($ApplyDataSave);									
					}
					
					//saving violations
					if(isset($data["violated"]) && count($data["violated"])>0)
					{	
							$violations		=	 $data['violated'];	
							
							foreach($violations as $key => $violationsData)
							{
								if($key == 'NotResponded'){
									
								$NotRespondedSave = array(
									 "TicketSlaID"=>$ID,
									 "Time"=>$violationsData['EscalateTime'],
									 "Value"=>implode(",",$violationsData['Agents']),
									 "VoilationType"=>TicketSlaPolicyViolation::$RespondedVoilationType
								 );
								 
								 TicketSlaPolicyViolation::create($NotRespondedSave);			
								 continue;
								}
								
								if($key == 'NotResolved')
								{	
									foreach($violationsData as $violationsDataLoop)
									{
										if($violationsDataLoop['Enabled'])
										{
											$NotResolveSave = array(
												 "TicketSlaID"=>$ID,
												 "Time"=>$violationsDataLoop['EscalateTime'],
												 "Value"=>implode(",",$violationsDataLoop['Agents']),
												 "VoilationType"=>TicketSlaPolicyViolation::$ResolvedVoilationType
											 );			
											TicketSlaPolicyViolation::create($NotResolveSave);	 				
										}
									}								
								}
							}
					}				
			 }
			 
			 DB::commit();
			return generateResponse('Successfully Created'); 		 
		}catch (Exception $ex){
			 DB::rollback();
             return generateResponse($ex->getMessage(),true,true);
        }	
	}
	

	function update($id)
	{	
		$data 			= 	Input::all(); 	 Log::info(print_r($data,true));
		$companyID 		=   User::get_companyID();    
		$TicketSla  	= 	TicketSla::find($id);// Log::info(print_r($data,true));  exit;
		
		$rules = array(
				'Name' =>  'required|unique:tblTicketSla,Name,'.$id.',TicketSlaID,CompanyID,'. $companyID,
		); 
		
		$messages		=	array();
		
		if(!isset($data['Apply'])){
			$messages 	=  	array("Apply.required"=>"You need to add at least 1 condition in 'Apply this to' section");
			$rules 		= 	array_merge($rules,array("Apply"=>"required"));					
		}	
	
		$validator 	= 	Validator::make($data,$rules);
		
		if ($validator->fails()) {
			 return generateResponse($validator->errors(),true);
		}	
		
	   try{
		
		 DB::beginTransaction();
				 
		 $UpdateData = array(
			 "Name"=>$data['Name'],
			 "Description"=>$data['Description'],
			 "updated_at"=>date('Y-m-d H:i:s'),
			 "updated_by"=>User::get_user_full_name()
		 );
		 
		 $update =   $TicketSla->update($UpdateData);
		 
		 if($update)
		 {		
				//saving Targets
				
				$Targets = $data['Target'];
				TicketSlaTarget::where(['TicketSlaID'=>$id])->delete();
				
				foreach($Targets as $key => $TargetsData)
				{ 
					
					 $SaveTargetsData = array(
					 	 "TicketSlaID"=>$id,
						 "PritiryID"=>TicketPriority::getPriorityIDByStatus($key),
						 "RespondWithinTimeValue"=>$TargetsData["RespondTime"],
						 "RespondWithinTimeType"=>$TargetsData["RespondType"],
						 "ResolveWithinTimeValue"=>$TargetsData["ResolveTime"],
						 "ResolveWithinTimeType"=>$TargetsData["ResolveType"],
						 "OperationalHrs"=>$TargetsData["SlaOperationalHours"],
						 "EscalationEmail"=>isset($TargetsData["Escalationemail"])?1:0,
					 );
					 
					TicketSlaTarget::create($SaveTargetsData);
					
				}
				
				
				//saving Applys
				if(isset($data["Apply"]) && count($data["Apply"])>0)
				{
						TicketSlaPolicyApplyTo::where(['TicketSlaID'=>$id])->delete(); //delete old
						
						$Groups	 		=	 isset($data["Apply"]['Groups'])?implode(",",$data["Apply"]['Groups']):"";	
						$TicketTypes	=	 isset($data["Apply"]['TicketTypes'])?implode(",",$data["Apply"]['TicketTypes']):"";								
						$Accounts 		=	 isset($data["Apply"]['Accounts'])?implode(",",$data["Apply"]['Accounts']):"";	
					
						$ApplyDataSave = array(
							 "TicketSlaID"=>$id,
							 "GroupFilter"=>$Groups,
							 "TypeFilter"=>$TicketTypes,
							 "CompanyFilter"=>$Accounts,							 
						 );
						 
						TicketSlaPolicyApplyTo::create($ApplyDataSave);									
				}
				
				//saving violations
				if(isset($data["violated"]) && count($data["violated"])>0)
				{	
						$violations		=	 $data['violated'];	
						
						TicketSlaPolicyViolation::where(['TicketSlaID'=>$id,"VoilationType"=>TicketSlaPolicyViolation::$ResolvedVoilationType])->delete(); //delete old
						TicketSlaPolicyViolation::where(['TicketSlaID'=>$id,"VoilationType"=>TicketSlaPolicyViolation::$RespondedVoilationType])->delete(); //delete old
						foreach($violations as $key => $violationsData)
						{
							
							if($key == 'NotResponded')
							{							
								$NotRespondedSave = array(
									 "TicketSlaID"=>$id,
									 "Time"=>$violationsData['EscalateTime'],
									 "Value"=>implode(",",$violationsData['Agents']),
									 "VoilationType"=>TicketSlaPolicyViolation::$RespondedVoilationType
								 );
								 
								 TicketSlaPolicyViolation::create($NotRespondedSave);			
								 continue;
							}

							if($key == 'NotResolved')
							{	
								foreach($violationsData as $violationsDataLoop)
								{
									if($violationsDataLoop['Enabled'])
									{
										$NotResolveSave = array(
											 "TicketSlaID"=>$id,
											 "Time"=>$violationsDataLoop['EscalateTime'],
											 "Value"=>implode(",",$violationsDataLoop['Agents']),
											 "VoilationType"=>TicketSlaPolicyViolation::$ResolvedVoilationType
										 );			
										TicketSlaPolicyViolation::create($NotResolveSave);	 				
									}
								}								
							}
						}
				}				
		 }
			 
			 DB::commit();
			 return generateResponse('Successfully updated');	
		}catch (Exception $ex){
			 DB::rollback();
             return generateResponse($ex->getMessage(),true,true);
        }	
	}
	
	public function delete($id) {		
		try{
			DB::beginTransaction();
			
			$IsDefault = TicketSla::find($id)->IsDefault;
			if($IsDefault==0){
				TicketSla::destroy($id);
				TicketSlaTarget::where(['TicketSlaID'=>$id])->delete();
				TicketSlaPolicyApplyTo::where(['TicketSlaID'=>$id])->delete(); 
				TicketSlaPolicyViolation::where(['TicketSlaID'=>$id])->delete(); 
				DB::commit();
				return generateResponse('Successfully Deleted');		 
			}
			else{
				return generateResponse("Cannot delete default data",true,true);
			}
		}catch (Exception $ex){
		 	DB::rollback();
              return generateResponse($ex->getMessage(),true,true);
		}       
    }
}