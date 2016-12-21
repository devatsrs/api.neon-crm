<?php

namespace Api\Controllers;

use Dingo\Api\Http\Request;
use Api\Model\AccountBalance;
use Api\Model\AccountBalanceHistory;
use Api\Model\DataTableSql;
use Api\Model\User;
use Api\Model\Account;
use Api\Model\TicketsTable;
use Api\Model\Ticket;
use Api\Model\Company;
use App\Http\Requests;
use Dingo\Api\Facade\API;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class TicketsFieldsController extends BaseController
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
	
	function GetFields(){ Log::info("here");
		$this->IsValidLicense();
		try
		{
			$Ticketfields	=	DB::table('tblTicketfields')->orderBy('FieldOrder', 'asc')->get(); 
			return generateResponse('success', false, false, $Ticketfields);
		}catch (\Exception $e) {
         	Log::info($e);
            return generateResponse('Some problem occurred.',true,true);
        }		
	}
	
	function iframe(){
		$this->IsValidLicense();
		$data 			 = 	 array();	
		$Ticketfields	 =	 DB::table('tblTicketfields')->orderBy('FieldOrder', 'asc')->get(); 
		$Checkboxfields  =   json_encode(Ticketfields::$Checkboxfields);
		$final		 	 =   $this->OptimizeDbFields($Ticketfields);
		$finaljson		 =   json_encode($final);
		
		//echo Ticketfields::$FIELD_HTML_DROPDOWN; exit;
		//echo "<pre>"; print_r($final); exit;
		return View::make('ticketsfields.iframe', compact('data','Ticketfields',"Checkboxfields","finaljson"));   
	}
	
	function iframeSubmit(){
	 	$data 						 = 		Input::all();
		
		//echo "<pre>"; print_r(json_decode($data['jsonData'])); echo "</pre>";exit;
		$ticket_type = 0; $else_type = 0;
		foreach(json_decode($data['jsonData']) as $jsonData)
		{	 
			$data		=	array();		
			if(isset($jsonData->action) && $jsonData->action=='create')
			{	
				$data['CustomerLabel']       			   = 		$jsonData->label_in_portal;
				$data['FieldDesc']       			  	   = 		$jsonData->description;
				$data['FieldHtmlType']        			   = 	 	Ticketfields::$TypeSave[$jsonData->type];				
				$data['FieldType']  		  			   = 		$jsonData->field_type;
				$data['AgentLabel']        			   	   = 		$jsonData->label;
				$data['FieldName']        			   	   = 		$jsonData->label;				
				$data['FieldDomType']       	  		   = 		$jsonData->type;				
				$data['AgentReqSubmit']       			   = 		isset($jsonData->required)?$jsonData->required:0;
				$data['AgentReqClose']       			   = 		isset($jsonData->required_for_closure)?$jsonData->required_for_closure:0;
				$data['CustomerDisplay']       			   = 		isset($jsonData->visible_in_portal)?$jsonData->visible_in_portal:0;
				$data['CustomerEdit']       			   = 		isset($jsonData->editable_in_portal)?$jsonData->editable_in_portal:0;
				$data['CustomerReqSubmit']       		   = 		isset($jsonData->required_in_portal)?$jsonData->required_in_portal:0;
				$data['FieldOrder']       		   		   = 		$jsonData->position;				
				$data['created_at']       		   		   = 		date("Y-m-d H:i:s");
				$data['created_by']       		   		   = 		User::get_user_full_name();			
				$TicketFieldsID 						   = 		Ticketfields::insertGetId($data);		
				
				foreach($jsonData->choices as $choices){							
					$choicesdata 							= 		array();
					$choicesdata['FieldsID']	     		= 		$TicketFieldsID;					
					$choicesdata['FieldType']	     		= 		1;					
					$choicesdata['FieldValueAgent']	     	= 		$choices->value;
					$choicesdata['FieldValueCustomer']	 	= 		$choices->value;
					$choicesdata['FieldOrder']			 	= 		isset($choices->position)?$choices->position:0;
					$choicesdata['created_at']       		= 		date("Y-m-d H:i:s");
					$choicesdata['created_by']       		= 		User::get_user_full_name();		
					 $id	=	TicketfieldsValues::insertGetId($choicesdata);				
					 Log::info("jsonData create choices id".$id);
				}	
			}
			
			if(isset($jsonData->action) && $jsonData->action=='edit')
			{	
				//if(!isset($jsonData->required)){Log::info("isset data"); Log::info(print_r($jsonData,true));}
				//$data['TicketFieldsID']       			   = 		$jsonData->id;
				$data['CustomerLabel']       			   = 		$jsonData->label_in_portal;
				$data['FieldDesc']       			  	   = 		$jsonData->description;
				$data['FieldHtmlType']        			   = 	 	Ticketfields::$TypeSave[$jsonData->type];				
				$data['FieldType']  		  			   = 		$jsonData->field_type;
				$data['AgentLabel']        			   	   = 		$jsonData->label;				
				$data['AgentReqSubmit']       			   = 		isset($jsonData->required)?$jsonData->required:0;
				$data['AgentReqClose']       			   = 		isset($jsonData->required_for_closure)?$jsonData->required_for_closure:0;
				$data['CustomerDisplay']       			   = 		isset($jsonData->visible_in_portal)?$jsonData->visible_in_portal:0;
				$data['CustomerEdit']       			   = 		isset($jsonData->editable_in_portal)?$jsonData->editable_in_portal:0;
				$data['CustomerReqSubmit']       		   = 		isset($jsonData->required_in_portal)?$jsonData->required_in_portal:0;
				$data['FieldOrder']       		   		   = 		isset($jsonData->position)?$jsonData->position:0;				
				$data['updated_at']       		   		   = 		date("Y-m-d H:i:s");
				$data['updated_by']       		   		   = 		User::get_user_full_name();			
				
	
				Ticketfields::find($jsonData->id)->update($data);	
				
				if(count($jsonData->choices)>0)
				{
					foreach($jsonData->choices as $key => $choices)
					{ Log::info(print_r($choices,true));
						$choicesdata 	  = 	array();
						
						if($data['FieldType']=='default_status')
						{
							//'status_id'=>$TicketfieldsValuesData->ValuesID
							if($choices->deleted==1)
							{
								TicketfieldsValues::find($choices->status_id)->delete();  continue;
							}
							else
							{
								if(!isset($choices->status_id)){
								$choicesdata =  array('FieldValueAgent'=>$choices->name,'FieldValueCustomer'=>$choices->customer_display_name,"FieldSlaTime"=>$choices->stop_sla_timer,'FieldsID'=>$jsonData->id,"FieldType"=>1,"FieldOrder"=>$choices->position);	
									TicketfieldsValues::insert($choicesdata); continue;
								}
								else
								{
									if(isset($choices->position)){
									$choicesdata =  array('FieldValueAgent'=>$choices->name,'FieldValueCustomer'=>$choices->customer_display_name,"FieldSlaTime"=>$choices->stop_sla_timer,"FieldOrder"=>$choices->position);	
									}else{
									$choicesdata =  array('FieldValueAgent'=>$choices->name,'FieldValueCustomer'=>$choices->customer_display_name,"FieldSlaTime"=>$choices->stop_sla_timer);	
									}
									TicketfieldsValues::find($choices->status_id)->update($choicesdata); continue;							
								}
							}
						
						}
						else if($data['FieldType']=='default_ticket_type')
						{
							if($choices->_destroy==1)
							{
									TicketfieldsValues::find($choices->id)->delete(); continue;	
							}
							else
							{								
								if(!isset($choices->id)){
									$choicesdata =  array('FieldValueAgent'=>$choices->value,'FieldValueCustomer'=>$choices->value,'FieldOrder'=>$choices->position,'FieldsID'=>$jsonData->id,"FieldType"=>1);						
									TicketfieldsValues::insert($choicesdata); continue;
								}else{
									if(isset($choices->position)){
									$choicesdata =  array('FieldValueAgent'=>$choices->value,'FieldValueCustomer'=>$choices->value,'FieldOrder'=>$choices->position);						
									}else{
									$choicesdata =  array('FieldValueAgent'=>$choices->value,'FieldValueCustomer'=>$choices->value);					}
									TicketfieldsValues::find($choices->id)->update($choicesdata);  continue;	
								}
							}
						}
						else if($data['FieldType']=='default_priority')
						{
							continue;								
						}
						else if($data['FieldType']=='default_group')
						{
							continue;								
						}						
						else
						{							
							if($choices->_destroy==1)
							{
									TicketfieldsValues::find($choices->id)->delete(); continue;	
							}
							else
							{
								
								if(!isset($choices->id)){
									$choicesdata =  array('FieldValueAgent'=>$choices->value,'FieldValueCustomer'=>$choices->value,'FieldOrder'=>$choices->position,'FieldsID'=>$jsonData->id,"FieldType"=>1);						
									TicketfieldsValues::insert($choicesdata); continue;
								}
								else
								{ 
								   if(isset($choices->position)){
										$choicesdata =  array('FieldValueAgent'=>$choices->value,'FieldValueCustomer'=>$choices->value,'FieldOrder'=>$ticket_position);					}
									else{
										$choicesdata =  array('FieldValueAgent'=>$choices->value,'FieldValueCustomer'=>$choices->value);				
									}
									TicketfieldsValues::find($TicketfieldsValuesData->id)->update($choicesdata);  continue;	
								}
							}
							
						}						
						
					}
				}				
			}
			
			if(isset($jsonData->action) && $jsonData->action=='delete')
			{
				Ticketfields::find($jsonData->id)->delete();	
				TicketfieldsValues::where(["FieldsID"=>$jsonData->id])->delete();
			}
		}
		
		return	Redirect::to('/ticketsfields/iframe'); 	
	}
	
	function OptimizeDbFields($Ticketfields){
		//$clas = (object) array();
		$result 	=  	 array();
		
		foreach($Ticketfields as $key =>  $TicketFieldsData){
				$data						   =		array();
				$TicketFieldsID 			   = 		$TicketFieldsData->TicketFieldsID;
				$TicketfieldsValues 	 	   = 		TicketfieldsValues::where(["FieldsID"=>$TicketFieldsID])->orderBy('FieldOrder', 'asc')->get();
				$data['id']       			   = 		$TicketFieldsData->TicketFieldsID;
				$data['type']        		   = 		Ticketfields::$type[$TicketFieldsData->FieldHtmlType];
				$data['name']       		   = 		$TicketFieldsData->FieldName;
				$data['label']       		   = 		$TicketFieldsData->AgentLabel;
				$data['dom_type']       	   = 		$TicketFieldsData->FieldDomType;
				$data['field_type']  		   = 		$TicketFieldsData->FieldType;
				$data['label_in_portal']  	   = 		$TicketFieldsData->CustomerLabel;
				$data['description']  		   = 		$TicketFieldsData->FieldDesc;
				$data['has_section']  		   = 		'';				
				$data['position']  			   = 		$TicketFieldsData->FieldOrder;
				$data['active']  			   = 		1;
				$data['required']  			   = 		$TicketFieldsData->AgentReqSubmit;
				$data['required_for_closure']  = 		$TicketFieldsData->AgentReqClose;
				$data['visible_in_portal']     = 		$TicketFieldsData->CustomerDisplay;
				$data['editable_in_portal']    = 		$TicketFieldsData->CustomerEdit;
				$data['required_in_portal']    = 		$TicketFieldsData->CustomerReqSubmit;
				$data['field_options']  	   = 		(object) array();				
				$choices 					   = 		array();	
				
				if(count($TicketfieldsValues)>0 &&  $data['field_type']!='default_priority' &&  $data['field_type']!='default_group'){
					foreach($TicketfieldsValues as $key => $TicketfieldsValuesData){
						if($data['field_type']=='default_status')
						{
						$choices[] = (object) array('status_id'=>$TicketfieldsValuesData->ValuesID,'name'=>$TicketfieldsValuesData->FieldValueAgent,'customer_display_name'=>$TicketfieldsValuesData->FieldValueCustomer,"stop_sla_timer"=>$TicketfieldsValuesData->FieldSlaTime,"deleted"=>'');
						}
						else if($data['field_type']=='default_ticket_type'){
						$choices[] =  array('0'=>$TicketfieldsValuesData->FieldValueAgent,'1'=>$TicketfieldsValuesData->FieldValueAgent,"2"=>$TicketfieldsValuesData->ValuesID);
						}else{
						$choices[] =  array('0'=>$TicketfieldsValuesData->FieldValueAgent,"1"=>$TicketfieldsValuesData->ValuesID);
						}
					}
				}else{									
					if($data['field_type']=='default_priority'){						
						$TicketPriority = DB::table('tblTicketPriority')->orderBy('PriorityID', 'asc')->get(); 								
						foreach($TicketPriority as $TicketPriorityData){						
							$choices[] =  array("0"=>$TicketPriorityData->PriorityValue,'1'=>$TicketPriorityData->PriorityID);
						}
					}
					
					if($data['field_type']=='default_group'){						
						$TicketGroups = DB::table('tblTicketGroups')->orderBy('GroupID', 'asc')->get(); 						
						foreach($TicketGroups as $TicketGroupsData){
							$choices[] =  array("0"=>$TicketGroupsData->GroupName,'1'=>$TicketGroupsData->GroupID);
						}
					}
				}
				
				$data['choices']	=  $choices;			
				$result[] 			=  (object) $data;	
		}		
		//echo "<pre>"; print_r($result); echo "<pre>"; exit;
		return $result;
	}	
}