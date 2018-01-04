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
use Api\Model\Ticketfields;
use Api\Model\TicketfieldsValues;
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
    }
	 

	function GetFields(){

		$data 					= 	Input::all();   
		try
		{
			if($data['LoginType']=='customer'){		
				$Ticketfields	=	DB::table('tblTicketfields')->Where(['CustomerEdit'=>1])->orderBy('FieldOrder', 'asc')->get(); 
			}else{
				$Ticketfields	=	DB::table('tblTicketfields')->orderBy('FieldOrder', 'asc')->get();
			}
			$final		 	=   Ticketfields::OptimizeDbFields($Ticketfields); 
			return generateResponse('success', false, false, $Ticketfields);
		}catch (\Exception $e) {
         	Log::info($e);
            return generateResponse('Some problem occurred.',true,true);
        }		
	}
	
	function GetDynamicFields(){

		$data 					= 	Input::all();   
		try
		{	
			if($data['LoginType']=='customer'){		
				$Ticketfields  =	DB::table('tblTicketfields')->Where(['CustomerDisplay'=>1])->orderBy('FieldOrder', 'asc')->get(); 
			}else{
				$Ticketfields  =	DB::table('tblTicketfields')->orderBy('FieldOrder', 'asc')->get(); 
			}
			return generateResponse('success', false, false, $Ticketfields);
		}catch (\Exception $e) {
         	Log::info($e);
            return generateResponse(cus_lang("MESSAGE_SOME_PROBLEM_OCCURRED"),true,true);
        }		
	}
	
	
	function iframeSubmits(){ 
	
	 	$data 						 = 		Input::all();
		try {
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
					{ 
						$choicesdata 	  = 	array();
						
						if($data['FieldType']=='default_status')
						{
							//'status_id'=>$TicketfieldsValuesData->ValuesID
							if($choices->deleted==1)
							{
								TicketfieldsValues::find($choices->status_id)->delete();
								TicketLog::where(['TicketFieldValueFromID'=>$choices->status_id])->delete();
				  				TicketLog::where(['TicketFieldValueToID'=>$choices->status_id])->delete();
								continue;
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
									TicketfieldsValues::find($choices->id)->delete();
									TicketLog::where(['TicketFieldValueFromID'=>$choices->id])->delete();
				  					TicketLog::where(['TicketFieldValueToID'=>$choices->id])->delete();
									continue;	
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
		
		 return generateResponse('Successfully updated.');
		} catch (\Exception $e) {
            Log::info($e);
            return $this->response->errorInternal('Internal Server');
        }
	}
	
	
}