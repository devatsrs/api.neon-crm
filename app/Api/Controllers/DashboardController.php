<?php
namespace Api\Controllers;
use Api\Model\Company;
use Dingo\Api\Http\Request;
use Api\Model\Opportunity;
use Api\Model\Task;
use Api\Model\User;
use Api\Model\DataTableSql;
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
		
        $companyID = User::get_companyID();
        $data = Input::all(); 
        if(!isset($data['fetchType'])){
            $data['fetchType'] = 'Grid';
        }
        $data['AccountOwner'] 	= 	isset($data['AccountOwner'])?empty($data['AccountOwner'])?'':$data['AccountOwner']:'';
        $data['taskClosed'] 	= 	isset($data['taskClosed'])?empty($data['taskClosed']) || $data['taskClosed']=='false'?0:$data['taskClosed']:0;
		
		
		if(isset($data['DueDateFilter']) && !empty($data['DueDateFilter'])){
			if($data['DueDateFilter']=='duetoday'){
					$data['DueDateFrom'] = date('Y-m-d')." 00:00:00";
					$data['DueDateTo']   = date('Y-m-d')." 23:59:59";
			}else if($data['DueDateFilter']=='duesoon'){
					$data['DueDateFrom'] = Task::DueSoon;
					$data['DueDateTo']   = Task::DueSoon;
			}else if($data['DueDateFilter']=='overdue'){
					$data['DueDateFrom'] = Task::Overdue;
					$data['DueDateTo']   = Task::Overdue;
			}else{
					$data['DueDateFrom'] = Task::All;
					$data['DueDateTo']   = Task::All;
			}
			
		}
       
            $rules['iDisplayStart'] 	= 	'required|Min:1';
            $rules['iDisplayLength'] 	= 	'required';
            $rules['sSortDir_0'] 		= 	'required';
			
            $validator = Validator::make($data, $rules);
            if ($validator->fails()) {
                return generateResponse($validator->errors(),true);
            }

            $columns 		= 	['Subject', 'DueDate', 'Status','UserID','RelatedTo'];
            $sort_column 	= 	$columns[$data['iSortCol_0']];
            $query = "call prc_GetTasksGrid (" . $companyID . ",".$data['id'].",'','" . $data['AccountOwner']. "', '0', '0','".$data['DueDateFrom']."','".$data['DueDateTo']."','0','0',".(ceil($data['iDisplayStart'] / $data['iDisplayLength'])) . " ," . $data['iDisplayLength'] . ",'" . $sort_column . "','" . $data['sSortDir_0'] . "')"; 
            try {
                $result = DataTableSql::of($query)->make();
                return generateResponse('',false,false,$result);
            }catch (\Exception $ex){
                Log::info($ex);
                return $this->response->errorInternal($ex->getMessage());
            }       
	}
	
	function GetPipleLineData(){
		
        $companyID 			= 	User::get_companyID();
        $userID 			= 	'';
        $data 				= 	Input::all();
		if(isset($data['UsersID'])){
			if(is_array($data['UsersID'])){
				$UserID = 	implode(",",array_filter($data['UsersID']));
			}else{
				$UserID = 	$data['UsersID'];
			}			
		}else{
			$UserID = 	$data['UsersID'];
		}
		$CurrencyID			=	(isset($data['CurrencyID']) && !empty($data['CurrencyID']))?$data['CurrencyID']:0;
		$array_return 		= 	array();
		$array_users		=	array();
		$array_worth		=	array();
		$TotalOpportunites  =   0;
		$TotalWorth			=	0;
		$query  			= 	"call prc_GetCrmDashboardPipeLine (".$companyID.",'".$UserID."','".$CurrencyID."')";  Log::info($query);
		$result 			= 	DB::select($query);
		
		foreach($result as $result_data){
			$array_return['data'][] = array("Worth"=>$result_data->TotalWorth,"Opportunites"=>$result_data->TotalOpportunites,'User'=>$result_data->AssignedUserText,'CurrencyCode'=>$result_data->v_CurrencyCode_);
			$TotalOpportunites 			=   $result_data->TotalOpportunites+$TotalOpportunites;	
			$TotalWorth					=	$TotalWorth+$result_data->TotalWorth;	
		}
		
		foreach($result as $result_data){
			$array_users[]		=	$result_data->AssignedUserText;
			$array_worth[]		=	$result_data->TotalWorth;
		}
		
		if(!isset($array_return['data'])){
			$array_return['data']				= 	array('Worth'=>0,"Opportunites"=>0,'CurrencyCode'=>'');
		}
		$round								=	isset($result_data->RoundVal)?$result_data->RoundVal:0;
		$array_return['CurrencyCode'] 		= 	isset($result_data->v_CurrencyCode_)?$result_data->v_CurrencyCode_:'';
		$array_return['TotalOpportunites']	=	$TotalOpportunites;
		$array_return['users']				=	implode(",",$array_users);
		$array_return['worth']				=	implode(",",$array_worth);
		$array_return['TotalWorth']			=	number_format((int)$TotalWorth,(int)$round);
		return generateResponse('',false,false,json_encode($array_return));
	}
	
	public function GetSalesdata(){ //crm dashboard
			
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
		$array_return1 		= 	array();
		$array_date			=	array();
		$worth				=	0;
		$array_dates		=	array();	
		$array_users		=	array();
		$array_worth		=	array();				
		$total_opp			=	0;
		$array_final 		= 	array("count"=>0,"status"=>"success");
		$Closingdate		=	explode(' - ',$data['Closingdate']);
		$StartDate			=   $Closingdate[0]." 00:00:00";
		$EndDate			=	$Closingdate[1]." 23:59:59";		
		$statusarray		=	(isset($data['Status']))?implode(",",$data['Status']):'';
		$query  			= 	"call prc_GetCrmDashboardSales (".$companyID.",'".$UserID."', '".$statusarray."','".$CurrencyID."','".$StartDate."','".$EndDate."')";  Log::info($query);
		$result 			= 	DB::select($query);
		$TotalWorth			=	0;
		
		foreach($result as $result_data){
			if(!in_array($result_data->AssignedUserText,$array_users)){			
				$array_users[]   = $result_data->AssignedUserText;
			}
			$total_opp = $total_opp+$result_data->Opportunitescount;
			$array_worth[] = $result_data->TotalWorth;
		}
		
		foreach($result as $result_data){			
			if(!in_array($result_data->MonthName,$array_dates)){			
				$array_dates[]   = $result_data->MonthName;
			}
		}
		
		foreach($result as $result_data){
			if(isset($array_date[$result_data->MonthName][$result_data->AssignedUserText])){
				$current_data = $array_date[$result_data->MonthName][$result_data->AssignedUserText];	
				$array_date[$result_data->MonthName][$result_data->AssignedUserText] 	 = 	$result_data->TotalWorth+$current_data;
			}else{
				$array_date[$result_data->MonthName][$result_data->AssignedUserText] 	 = 	$result_data->TotalWorth;
			}			
			$worth = $worth+$result_data->TotalWorth;
		}
		
		$array_data = array();
		
		foreach($array_users as $array_users_data){
			foreach($array_dates as $array_dates_data){
				if(isset($array_date[$array_dates_data][$array_users_data])){
					$array_data[$array_users_data][] = $array_date[$array_dates_data][$array_users_data];
				}else{
					$array_data[$array_users_data][] = 0;
				}
			}
		}

		
		foreach($array_data as $key => $array_data_loop){
			$array_return1[] = array("user"=>$key,"worth"=>implode(",",$array_data_loop));			
		}
		
		if(count($array_users)>0){
			$worth = number_format($worth,$result_data->round_number);
			$array_final = array("data"=>$array_return1,"dates"=>implode(",",$array_dates),'TotalWorth'=>$worth,"count"=>count($array_users),"CurrencyCode"=>$result_data->v_CurrencyCode_,"TotalOpportunites"=>$total_opp,"worth"=>implode(",",$array_worth),"users"=>implode(",",$array_users),"status"=>"success");
		}		
		
		return generateResponse('',false,false,json_encode($array_final));
	}
	
	function GetForecastData(){	 //crm dashboard
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
		$array_return1 		= 	array();
		$array_date			=	array();
		$worth				=	0;
		$total_opp			=	0;
		$array_dates		=	array();	
		$array_users		=	array();				
		$array_final 		= 	array("count"=>0,"status"=>"success");
		$Closingdate		=	explode(' - ',$data['Closingdate']);
		$StartDate			=   $Closingdate[0]." 00:00:00";
		$EndDate			=	$Closingdate[1]." 23:59:59";		
		$statusarray		=	Opportunity::Open;
		$query  			= 	"call prc_GetCrmDashboardForecast (".$companyID.",'".$UserID."', '".$statusarray."','".$CurrencyID."','".$StartDate."','".$EndDate."')";   Log::info($query);
		$result 			= 	DB::select($query);
		$TotalWorth			=	0;
		
		foreach($result as $result_data){
			if(!in_array($result_data->AssignedUserText,$array_users)){			
				$array_users[]   = $result_data->AssignedUserText;
			}
			$total_opp			=	$total_opp+$result_data->Opportunitescount;
		}
		
		foreach($result as $result_data){			
			if(!in_array($result_data->MonthName,$array_dates)){			
				$array_dates[]   = $result_data->MonthName;
			}
		}
		
		foreach($result as $result_data){
			if(isset($array_date[$result_data->MonthName][$result_data->AssignedUserText])){
				$current_data = $array_date[$result_data->MonthName][$result_data->AssignedUserText];	
				$array_date[$result_data->MonthName][$result_data->AssignedUserText] 	 = 	$result_data->TotalWorth+$current_data;
			}else{
				$array_date[$result_data->MonthName][$result_data->AssignedUserText] 	 = 	$result_data->TotalWorth;
			}			
			$worth = $worth+$result_data->TotalWorth;
		}
		
		$array_data = array();
		
		foreach($array_users as $array_users_data){
			foreach($array_dates as $array_dates_data){
				if(isset($array_date[$array_dates_data][$array_users_data])){
					$array_data[$array_users_data][] = $array_date[$array_dates_data][$array_users_data];
				}else{
					$array_data[$array_users_data][] = 0;
				}
				
			}
		}

		
		foreach($array_data as $key => $array_data_loop){
			$array_return1[] = array("user"=>$key,"worth"=>implode(",",$array_data_loop));
		}
		
		if(count($array_users)>0){
			$worth = number_format($worth,$result_data->round_number);
			$array_final = array("data"=>$array_return1,"dates"=>implode(",",$array_dates),'TotalWorth'=>$worth,"count"=>count($array_users),"CurrencyCode"=>$result_data->CurrencyCode,"TotalOpportunites"=>$total_opp,"status"=>"success");
		}		
		
		return generateResponse('',false,false,json_encode($array_final));
	}
	
	
	
	 public function getOpportunitiesGrid(){       
        $companyID 					= 	User::get_companyID();
        $data 						= 	Input::all();
		if(isset($data['AccountOwner'])){
			if(is_array($data['AccountOwner'])){
				$UserID = 	implode(",",array_filter($data['AccountOwner']));
			}else{
				$UserID =	$data['AccountOwner'];
				}			
		}else{
			$UserID = 	'';
		}
		
        $data['CurrencyID'] 		= 	isset($data['CurrencyID'])?empty($data['CurrencyID'])?0:$data['CurrencyID']:0;
	    $rules['iDisplayStart'] 	= 	'required|Min:1';
        $rules['iDisplayLength'] 	= 	'required';
        $rules['sSortDir_0'] 		= 	'required';
		
        $validator 					= 	Validator::make($data, $rules);
		if ($validator->fails()) {
			return generateResponse($validator->errors(),true);
		}

         $columns 				= 	['OpportunityName', 'Status','UserID','RelatedTo','ExpectedClosing','Value','Rating'];
         $sort_column 			= 	$columns[$data['iSortCol_0']];
		 
         $query = "call prc_GetOpportunityGrid (" . $companyID . ",'0', '','', '" . $UserID . "', 0,'1', ".$data['CurrencyID'].", '0',".(ceil($data['iDisplayStart'] / $data['iDisplayLength'])) . "," . $data['iDisplayLength'] . ",'" . $sort_column . "','" . $data['sSortDir_0'] . "')"; 
		    try {
                $result = DataTableSql::of($query)->make();
                return generateResponse('',false,false,$result);
            }catch (\Exception $ex){
                Log::info($ex);
                return $this->response->errorInternal($ex->getMessage());
            }
    }
}
