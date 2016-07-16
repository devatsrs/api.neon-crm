<?php
namespace Api\Controllers;
use Api\Model\Company;
use Dingo\Api\Http\Request;
use Api\Model\Opportunity;
use Api\Model\Task;
use Api\Model\User;
use App\Http\Requests;
use Dingo\Api\Facade\API;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Faker\Provider\Uuid;

class DashboardController extends BaseController {

    public function __construct(Request $request)
    {
        $this->middleware('jwt.auth');
        Parent::__Construct($request);
    }
    

	public function GetUsersTasks(){
	    $data 					= 	Input::all();		
		$companyID			 	= 	User::get_companyID();
		$SearchDate				=	'';		
		$where['taskClosed']	=	0;
		$task 					= 	Task::where($where)->select(['tblTask.Subject','tblTask.DueDate','tblCRMBoardColumn.BoardColumnName as Status','tblAccount.AccountName as Company','tblTask.Priority']);
		
		
		$UserID			=	(isset($data['UsersID']) && is_array($data['UsersID']))?implode(",",array_filter($data['UsersID'])):$data['UsersID'];
		if(!empty($UserID)){
			$task->whereRaw('find_in_set(tblTask.UsersIDs,"'.$UserID.'")');
		}		

		if(isset($data['TaskTypeData']) && $data['TaskTypeData']!=''){
		 if($data['TaskTypeData'] == 'duetoday'){
             $task->whereRaw("DATE(tblTask.DueDate) =".date('Y-m-d'));
		 }
		 else if($data['TaskTypeData'] == 'duesoon'){
			 $task->whereBetween('tblTask.DueDate',array(date("Y-m-d"),date("Y-m-d",strtotime(''.date('Y-m-d').' +1 months'))));						
		 }
		 else if($data['TaskTypeData'] == 'overdue'){
			$task->where("tblTask.DueDate","<",DB::raw(''.date('Y-m-d')).'');			
		 }
		 if($data['TaskTypeData'] != 'All'){
			$task->where("tblTask.DueDate","!=",DB::raw("'0000-00-00 00:00:00'")); 			 
		 }		 
		}
				
		$task->join('tblCRMBoardColumn', 'tblTask.BoardColumnID', '=', 'tblCRMBoardColumn.BoardColumnID');
		
		$task->join('tblAccount', 'tblTask.AccountIDs', '=', 'tblAccount.AccountID');
		
        $UserTasks 		 	 	= 	$task->orderBy('tblTask.DueDate', 'desc')->get();
	    $jsondata['UserTasks']	=	$UserTasks;
		return generateResponse('',false,false,json_encode($jsondata));
	}
	
	function GetPipleLineData(){
        $companyID 			= 	User::get_companyID();
        $userID 			= 	'';
        $data 				= 	Input::all();
		$UserID				=	(isset($data['UsersID']) && is_array($data['UsersID']))?implode(",",array_filter($data['UsersID'])):'';
		$CurrencyID			=	(isset($data['CurrencyID']) && !empty($data['CurrencyID']))?$data['CurrencyID']:0;
		$array_return 		= 	array("TotalOpportunites"=>0,"TotalWorth"=>0);
		$array_status 		= 	array();
		$statusarray 		=	implode(",", array(Opportunity::Open,Opportunity::Won,Opportunity::Lost,Opportunity::Abandoned));
		$query  			= 	"call prc_GetCrmDashboardPipeLine (".$companyID.",'".$UserID."', '".$statusarray."','".$CurrencyID."')";
		$result 			= 	DB::select($query);
		
			foreach($result as $result_data){
				$array_status[$result_data->Status] = array("Worth"=>$result_data->TotalWorth,"Opportunites"=>$result_data->TotalOpportunites);
			}
			foreach(Opportunity::$status as $index => $status_text){		
				$array_return['CurrencyCode'] 	= 	isset($result_data->v_CurrencyCode_)?$result_data->v_CurrencyCode_:'';		
				$array_return['data'][$index] = isset($array_status[$index])?array("status"=>$status_text,"Worth"=>$array_status[$index]["Worth"],"Opportunites"=>$array_status[$index]["Opportunites"],"CurrencyCode"=>$array_return['CurrencyCode']): array("status"=>$status_text,"Worth"=>0,"Opportunites"=>0,"CurrencyCode"=>$array_return['CurrencyCode']);
				
				$array_return['TotalOpportunites'] 			=   $array_return['TotalOpportunites']+(isset($array_status[$index]["Opportunites"])?$array_status[$index]["Opportunites"]:0);
				
				$array_return['TotalWorth'] 	= 	$array_return['TotalWorth']+(isset($array_status[$index]['Worth'])?$array_status[$index]['Worth']:0);	
			}
		return generateResponse('',false,false,json_encode($array_return));
	}
	
	public function GetForecastData(){ //crm dashboard
			
        $companyID 			= 	User::get_companyID();
        $userID 			= 	'';
        $data 				= 	Input::all();		
		$rules = array(
            'Closingdate' =>      'required',                 
        );
		$message	 = array("Closingdate.required"=> "Close Date field is required.");
        $validator   = Validator::make($data, $rules,$message);
		if ($validator->fails()) {
            return generateResponse($validator->errors(),true);
        }
		$UserID				=	(isset($data['UsersID']) && is_array($data['UsersID']))?implode(",",array_filter($data['UsersID'])):$data['UsersID'];
		$CurrencyID			=	(isset($data['CurrencyID']) && !empty($data['CurrencyID']))?$data['CurrencyID']:0;
		$array_return 		= 	array();
		$Closingdate		=	explode(' - ',$data['Closingdate']);
		$StartDate			=   $Closingdate[0]." 00:00:00";
		$EndDate			=	$Closingdate[1]." 23:59:59";		
		$statusarray		=	(isset($data['Status']))?$data['Status']:'';
		$query  			= 	"call prc_GetCrmDashboardForecast (".$companyID.",'".$UserID."', '".$statusarray."','".$CurrencyID."','".$StartDate."','".$EndDate."')"; 
		$result 			= 	DB::select($query);
		$TotalWorth			=	0;
		foreach($result as $result_data){
				$CurrencySign 			   = 	    isset($result_data->v_CurrencyCode_)?$result_data->v_CurrencyCode_:'';	
				if(isset($array_return['data'][$result_data->ClosingDate]))
				{
					$CcurrentDataWorth 			=	 $array_return['data'][$result_data->ClosingDate]['TotalWorth'];
					$CcurrentDataOpportunites 	=	 $array_return['data'][$result_data->ClosingDate]['Opportunites'];
					$CcurrentDataStatusStr 		=	 $array_return['data'][$result_data->ClosingDate]['StatusStr'];
					
					if(isset($CcurrentDataStatusStr[Opportunity::$status[$result_data->StatusSum]])){ 	
					
					 	$currentStatusdata = 	$CcurrentDataStatusStr[Opportunity::$status[$result_data->StatusSum]];
						$CcurrentDataStatusStr[Opportunity::$status[$result_data->StatusSum]] = array("Status"=>Opportunity::$status[$result_data->StatusSum],"worth"=>$currentStatusdata['worth']+$result_data->TotalWorth);						
					}else{
						$CcurrentDataStatusStr[Opportunity::$status[$result_data->StatusSum]]	=	array("Status"=>Opportunity::$status[$result_data->StatusSum],"worth"=>$result_data->TotalWorth);	
					}
					$array_return['data'][$result_data->ClosingDate]    = 		array("TotalWorth"=>$CcurrentDataWorth+$result_data->TotalWorth,"Opportunites"=>$CcurrentDataOpportunites+1,"ClosingDate"=>$result_data->ClosingDate,"CurrencyCode"=>$CurrencySign,'StatusStr'=>$CcurrentDataStatusStr );
				}
				else
				{ 	$StatusArray = array();
					$StatusArray[Opportunity::$status[$result_data->StatusSum]]  = array("Status"=>Opportunity::$status[$result_data->StatusSum],"worth"=>$result_data->TotalWorth);
					$array_return['data'][$result_data->ClosingDate]    = 		array("TotalWorth"=>$result_data->TotalWorth,"Opportunites"=>1,"ClosingDate"=>$result_data->ClosingDate,"CurrencyCode"=>$CurrencySign,'StatusStr'=>$StatusArray);			
				}				
				$TotalWorth 			   = 		$TotalWorth+$result_data->TotalWorth;
		}
		//Log::info($array_return);
		$array_final = array();
		if(isset($array_return['data'])){ 
			foreach($array_return['data'] as $key => $array_return_data){
				$ArrStatus			= 	$array_return_data['StatusStr'];
				$ArrChild			= 	array();
				foreach($ArrStatus as $ArrStatusData){
					$ArrChild[]	 = $ArrStatusData;
				}
				$array_return_data['StatusStr'] = 	$ArrChild;
				$array_final['data'][] 			= 	$array_return_data;
			}
		}
		
		if(count($array_final)>0){					
			$array_final['status'] 	   	   	   = 		'success';
			$array_final['CurrencyCode'] 	   = 		isset($result_data->v_CurrencyCode_)?$result_data->v_CurrencyCode_:'';
		}
		$array_final['TotalWorth'] 	  		   = 		$TotalWorth;
		return generateResponse('',false,false,json_encode($array_final));
	}
}
