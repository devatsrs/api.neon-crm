<?php
namespace Api\Controllers;

use Dingo\Api\Http\Request;
use Api\Model\DataTableSql;
use Api\Model\User;
use Api\Model\TicketImportRule;
use Api\Model\TicketImportRuleCondition;
use Api\Model\TicketImportRuleConditionType;
use Api\Model\TicketImportRuleAction;
use Api\Model\TicketImportRuleActionType;
use App\Http\Requests;
use Dingo\Api\Facade\API;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class TicketImportRulesController extends BaseController {

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
			
			$query 	= 	"call prc_GetTicketImportRules (".$CompanyID.",".( ceil($data['iDisplayStart']/$data['iDisplayLength']) )." ,".$data['iDisplayLength'].",'".$sort_column."','".$data['sSortDir_0']."'"; 
	
			if(isset($data['Export']) && $data['Export'] == 1) {
				$result = DB::select($query . ',1)');
			}else{
				$query .=',0)';  
				$result =  DataTableSql::of($query)->make(); 
			}  Log::info($query);
			return generateResponse('',false,false,$result);
		} catch (\Exception $e) {
            Log::info($e);
             return generateResponse($ex->getMessage(),true,true);
        }		
    }
		
	function store(){
		    $data 			= 	Input::all(); 	
		    $companyID 		=   User::get_companyID(); 
			$rules = array(
				'Title' =>   'required|unique:tblTicketImportRule,Title,NULL,TicketImportRuleID,CompanyID,'.$companyID,
			);			
		
			$validator 	= 	Validator::make($data,$rules);
			
			if ($validator->fails()) {
				   return generateResponse($validator->errors(),true);
			}	
			
		   try{
			
			 DB::beginTransaction();
					 
			 $SaveData = array(
			 	 "CompanyID"=>$companyID,
				 "Title"=>$data['Title'],
				 "Description"=>$data['Description'],
				 "Match"=>$data['Match'],
				 "Status"=>isset($data['Status'])?1:0, 
				 "created_at"=>date('Y-m-d H:i:s'),
				 "created_by"=>User::get_user_full_name()
			 );
			 
			 $ID 	=	 TicketImportRule::insertGetId($SaveData);
			 
			 if($ID)
			 {		
			 		//saving conditions
					

					 $condition = isset($data['condition'])?$data['condition'] : array();
					
					foreach($condition as $key => $ConditionData)
					{
						$operandCondition = $operandValue = "";
						if($ConditionData['rule_condition'] > 0 ) {
							$conditionDbData = TicketImportRuleConditionType::find($ConditionData['rule_condition']);
							if (in_array($conditionDbData->Condition, TicketImportRuleConditionType::$DifferentCondtionsArray)) {
								$operandCondition = $ConditionData['rule_match_sp'];
								$operandValue = implode(",", $ConditionData['condition_value']);
							} else {
								$operandCondition = $ConditionData['rule_match_all'];
								$operandValue = $ConditionData['condition_value'];
							}

							$SaveConditionData = array(
								"TicketImportRuleID" => $ID,
								"TicketImportRuleConditionTypeID" => $ConditionData['rule_condition'],
								"Operand" => $operandCondition,
								"Value" => $operandValue,
								"Order" => $ConditionData['condition_order']
							);


							$rules = array(
								'TicketImportRuleID' => 'required',
								'TicketImportRuleConditionTypeID' => 'required',
								'Operand' => 'required',
								'Order' => 'required',
							);
							if (Validator::make($SaveConditionData, $rules)->fails()) {
								return generateResponse($validator->errors(), true);
							}

							TicketImportRuleCondition::create($SaveConditionData);
						}
					}
					
					//saving rules
				    $rule = isset($data['rule']) ? $data['rule'] : array();
					foreach($rule as $key => $RuleData)
					{
						$Value = "";

						if($RuleData['rule_action'] > 0) {
							$RuleDbData = TicketImportRuleActionType::find($RuleData['rule_action']);
							if (TicketImportRuleActionType::$ActionArrayValue[$RuleDbData->Action] == 'skip') {
								$Value = '';
							} else {
								$Value = implode(",", $RuleData['action_value']);
							}

							$SaveRuleData = array(
								"TicketImportRuleID" => $ID,
								"TicketImportRuleActionTypeID" => $RuleData['rule_action'],
								"Value" => $Value,
								"Order" => $RuleData['action_order']
							);


							$rules = array(
								'TicketImportRuleID' => 'required',
								'TicketImportRuleActionTypeID' => 'required',
								'Value' => 'required',
								'Order' => 'required',
							);
							if (Validator::make($SaveRuleData, $rules)->fails()) {
								return generateResponse($validator->errors(), true);
							}
							TicketImportRuleAction::create($SaveRuleData);
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
		$data 				= 	Input::all();
		$companyID 			=   User::get_companyID();     
		$TicketImportRule  	= 	TicketImportRule::find($id);
		
		$rules = array(
				'Title' =>  'required|unique:tblTicketImportRule,Title,'.$id.',TicketImportRuleID,CompanyID,'. $companyID,
		); 	
	
		$validator 	= 	Validator::make($data,$rules);
		
		if ($validator->fails()) {
			 return generateResponse($validator->errors(),true);
		}	
		
	   try{
		
		 DB::beginTransaction();
		 		
		 $UpdateData = array(
				 "Title"=>$data['Title'],
				 "Description"=>$data['Description'],
				 "Match"=>$data['Match'],
				 "Status"=>isset($data['Status'])?1:0, 
				 "updated_at"=>date('Y-m-d H:i:s'),
				 "updated_by"=>User::get_user_full_name()
			 );
		 
		 $update =   $TicketImportRule->update($UpdateData);
		 
		 if($update)
		 {		
				
				TicketImportRuleCondition::where(['TicketImportRuleID'=>$id])->delete(); //deleting old				
				//saving conditions

			 	$condition = isset($data['condition'])?$data['condition'] : array();

				 foreach($condition as $key => $ConditionData)
				 {
					 $operandCondition = $operandValue = "";
					 if($ConditionData['rule_condition'] > 0 ) {
						 $conditionDbData = TicketImportRuleConditionType::find($ConditionData['rule_condition']);
						 if (in_array($conditionDbData->Condition, TicketImportRuleConditionType::$DifferentCondtionsArray)) {
							 $operandCondition = $ConditionData['rule_match_sp'];
							 $operandValue = implode(",", $ConditionData['condition_value']);
						 } else {
							 $operandCondition = $ConditionData['rule_match_all'];
							 $operandValue = $ConditionData['condition_value'];
						 }

					 }
					 $SaveConditionData = array(
						 "TicketImportRuleID"=>$id,
						 "TicketImportRuleConditionTypeID"=>$ConditionData['rule_condition'],
						 "Operand"=>$operandCondition,
						 "Value"=>$operandValue,
						 "Order"=>$ConditionData['condition_order']
					 );


					 $rules = array(
						 'TicketImportRuleID' =>   'required',
						 'TicketImportRuleConditionTypeID' =>   'required',
						 'Operand' =>   'required',
						 'Order' =>   'required',
					 );
					 if (Validator::make($SaveConditionData,$rules)->fails()) {
						 return generateResponse($validator->errors(),true);
					 }

					 TicketImportRuleCondition::create($SaveConditionData);
				 }
				
				TicketImportRuleAction::where(['TicketImportRuleID'=>$id])->delete(); //delete old
				
				//saving rules
			    $rule = isset($data['rule']) ? $data['rule'] : array();
				 foreach($rule as $key => $RuleData)
				 {
					 $Value = "";

					 if($RuleData['rule_action'] > 0) {
						 $RuleDbData = TicketImportRuleActionType::find($RuleData['rule_action']);
						 if (TicketImportRuleActionType::$ActionArrayValue[$RuleDbData->Action] == 'skip') {
							 $Value = '';
						 } else {
							 $Value = implode(",", $RuleData['action_value']);
						 }
					 }
					 $SaveRuleData = array(
						 "TicketImportRuleID"=>$id,
						 "TicketImportRuleActionTypeID"=>$RuleData['rule_action'],
						 "Value"=>$Value,
						 "Order"=>$RuleData['action_order']
					 );


					 $rules = array(
						 'TicketImportRuleID' =>   'required',
						 'TicketImportRuleActionTypeID' =>   'required',
						 'Value' =>   'required',
						 'Order' =>   'required',
					 );
					 if (Validator::make($SaveRuleData,$rules)->fails()) {
						 return generateResponse($validator->errors(),true);
					 }
					 TicketImportRuleAction::create($SaveRuleData);
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
			TicketImportRule::destroy($id);
			TicketImportRuleCondition::where(['TicketImportRuleID'=>$id])->delete();
			TicketImportRuleAction::where(['TicketImportRuleID'=>$id])->delete();
			DB::commit();
			return generateResponse('Successfully Deleted');			
		}catch (Exception $ex){
		 	DB::rollback();
              return generateResponse($ex->getMessage(),true,true);
		}       
    }
}