<?php
namespace Api\Controllers;
use Dingo\Api\Http\Request;
use Api\Model\User;
use Api\Model\TicketBusinessHours;
use Api\Model\TicketsWorkingDays;
use Api\Model\TicketBusinessHolidays;
use Api\Model\DataTableSql;
use App\Http\Requests;
use Dingo\Api\Facade\API;
use Faker\Provider\Uuid;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;



class TicketsBusinessHoursController extends BaseController {

private $validlicense;	

	public function __construct(Request $request)
    {
        $this->middleware('jwt.auth');
        Parent::__Construct($request);
    }
	 protected function IsValidLicense(){		
	 	//return $this->validlicense;		
	 }
	 
    public function ajax_datagrid() {		
        $companyID  =   User::get_companyID();
		
		try
		{
		   $CompanyID 				= 	User::get_companyID();       
		   $data 					= 	Input::all(); 
		   $data['iDisplayStart'] 	+=	1;
		   $columns 	 			= 	array('Name','Description');
		   $sort_column 			= 	$columns[$data['iSortCol_0']];
			
			$query 	= 	"call prc_GetTicketBusinessHours (".$CompanyID.",".( ceil($data['iDisplayStart']/$data['iDisplayLength']) )." ,".$data['iDisplayLength'].",'".$sort_column."','".$data['sSortDir_0']."'"; 
	
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
	
	 public function exports($type) { 
            $companyID  =  User::get_companyID();
            $Data 		=  TicketBusinessHours::where(["CompanyID"=>$companyID])->select(["Name","Description",DB::raw("IsDefault as DefaultData")])->get();		
		    $excel_data =  json_decode(json_encode($Data),true);

            if($type=='csv'){
                $file_path = CompanyConfiguration::get('UPLOAD_PATH') .'/businesshours.csv';
                $NeonExcel = new NeonExcelIO($file_path);
                $NeonExcel->download_csv($excel_data);
            }elseif($type=='xlsx'){
                $file_path = CompanyConfiguration::get('UPLOAD_PATH') .'/businesshours.xls';
                $NeonExcel = new NeonExcelIO($file_path);
                $NeonExcel->download_excel($excel_data);
            }
    }
	
	
	  
	public function create() {	
	} 	  
	
	public function store() {
		
	 	$data 		= 	Input::all();  
		$companyID 	=   User::get_companyID();       
		$messages 	=  	array("custom_hours_day.required"=>"Please select atleast one weekday.");

		$rules = array(
			'Title' =>  'required|unique:tblTicketBusinessHours,Name,NULL,ID,CompanyID,'. $companyID,
			'Description' => 'required',
			//'Timezone' => 'required',
    	);
		
		if($data['HelpdeskHours']==2 && !isset($data['custom_hours_day'])){
			$rules = array_merge($rules,array("custom_hours_day"=>"required"));					
		}	
		
		$validator 	= 	Validator::make($data,$rules, $messages);
		
		if ($validator->fails()) {
             return generateResponse($validator->errors(),true);
        }
		
		try{
			
			 DB::beginTransaction();
					 
			 $SaveData = array(
			 	 "CompanyID"=>$companyID,
				 "Name"=>$data['Title'],
				 "Description"=>$data['Description'],
				// "Timezone"=>$data['Timezone'],
				 "HoursType"=>$data["HelpdeskHours"],
				 "created_at"=>date('Y-m-d H:i:s'),
				 "created_by"=>User::get_user_full_name()
			 );
			 
			 $ID 	=	 TicketBusinessHours::insertGetId($SaveData);
			 
			 if($ID)
			 {		//saving wokring days
					if($data["HelpdeskHours"]==TicketBusinessHours::$HelpdeskHoursCustom)
					{
						$workingDays = $data['custom_hours_day'];
						
						foreach($workingDays as $key => $workingDaysData)
						{
							$day 	 = 	TicketBusinessHours::$CustomDays[$key];	
							$daystr1 =	$day."FromHour";
							$daystr2 =	$day."FromType";
							$daystr3 =	$day."ToHour";
							$daystr4 =	$day."ToType";
							
							 $WorkingDayData = array(
								 "BusinessHoursID"=>$ID,
								 "Day"=>$key,
								 "StartTime"=>date("H:i", strtotime("".$data[$daystr1]." ".strtoupper($data[$daystr2])."")),
								 "EndTime"=>date("H:i", strtotime("".$data[$daystr3]." ".strtoupper($data[$daystr4])."")),								
								 "Status"=>1
							 );
							 
							TicketsWorkingDays::create($WorkingDayData);
							
						}
					}
					
					//saving holidays
					if(isset($data["holidays"]) && count($data["holidays"])>0)
					{
						foreach($data["holidays"] as $key => $HolidaysData)
						{
							$HolidayDate 	=	 explode("_",$key);	
							$HolidayMonth	=	 array_search($HolidayDate[0], TicketBusinessHours::$HolidaysMonths);
							$HolidayDay		=	 $HolidayDate[1];
							
							$HolidaysDataSave = array(
								 "BusinessHoursID"=>$ID,
								 "HolidayMonth"=>$HolidayMonth,
								 "HolidayDay"=>$HolidayDay,
								 "HolidayName"=>$HolidaysData,							 
							 );
							 
							TicketBusinessHolidays::create($HolidaysDataSave);						
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
	
	public function delete($id) {		
		try{
			DB::beginTransaction();
			$IsDefault = TicketBusinessHours::find($id)->IsDefault;
			if($IsDefault==0){
				TicketBusinessHours::destroy($id);
				TicketsWorkingDays::where(["BusinessHoursID"=>$id])->delete();
				TicketBusinessHolidays::where(["BusinessHoursID"=>$id])->delete();
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
	
	 public function edit($id) {	
    }
	
	function update($id){
		$data 			= 	Input::all(); 		 
		$companyID 		=   User::get_companyID();       
		$BusinessHours  = 	TicketBusinessHours::find($id);
		$messages 		=  	array("custom_hours_day.required"=>"Please select atleast one weekday.");   
		
		$rules = array(
			'Title' =>  'required|unique:tblTicketBusinessHours,Name,' . $id . ',ID,CompanyID,'. $companyID,
			'Description' => 'required',
			//'Timezone' => 'required',
    	);
		
		if($data['HelpdeskHours']==2 && !isset($data['custom_hours_day'])){
			$rules = array_merge($rules,array("custom_hours_day"=>"required"));					
		}	
		
		$validator 	= 	Validator::make($data,$rules, $messages);
		
		if ($validator->fails()) {
            return generateResponse($validator->errors(),true);
        }
		
		try{
			
			 DB::beginTransaction();
					 
			 $SaveData = array(
				 "Name"=>$data['Title'],
				 "Description"=>$data['Description'],
				// "Timezone"=>$data['Timezone'],
				 "HoursType"=>$data["HelpdeskHours"],
				 "updated_at"=>date('Y-m-d H:i:s'),
				 "updated_by"=>User::get_user_full_name()
			 );
			 
			$update = $BusinessHours->update($SaveData);
			 
			 if($update)
			 {		//saving wokring days
					 TicketsWorkingDays::where(["BusinessHoursID"=>$id])->delete(); // delete old data
					if($data["HelpdeskHours"]==TicketBusinessHours::$HelpdeskHoursCustom)
					{	
						
						$workingDays = $data['custom_hours_day'];
						
						foreach($workingDays as $key => $workingDaysData)
						{
							$day 	 = 	TicketBusinessHours::$CustomDays[$key];	
							$daystr1 =	$day."FromHour";
							$daystr2 =	$day."FromType";
							$daystr3 =	$day."ToHour";
							$daystr4 =	$day."ToType";
							
							 $WorkingDayData = array(
								 "BusinessHoursID"=>$id,
								 "Day"=>$key,
								 "StartTime"=>date("H:i", strtotime("".$data[$daystr1]." ".strtoupper($data[$daystr2])."")),
								 "EndTime"=>date("H:i", strtotime("".$data[$daystr3]." ".strtoupper($data[$daystr4])."")),								
								 "Status"=>1
							 );
							 
							TicketsWorkingDays::create($WorkingDayData);
							
						}
					}
					
					//saving holidays
					TicketBusinessHolidays::where(["BusinessHoursID"=>$id])->delete();
					if(isset($data["holidays"]) && count($data["holidays"])>0)
					{							
						foreach($data["holidays"] as $key => $HolidaysData)
						{
							$HolidayDate 	=	 explode("_",$key);	
							$HolidayMonth	=	 array_search($HolidayDate[0], TicketBusinessHours::$HolidaysMonths);
							$HolidayDay		=	 $HolidayDate[1];
							
							$HolidaysDataSave = array(
								 "BusinessHoursID"=>$id,
								 "HolidayMonth"=>$HolidayMonth,
								 "HolidayDay"=>$HolidayDay,
								 "HolidayName"=>$HolidaysData,							 
							 );
							 
							TicketBusinessHolidays::create($HolidaysDataSave);						
						}	
					}				
			 }
			 
			 DB::commit();
			 return generateResponse('Successfully Updated');		 
		}catch (Exception $ex){
			 DB::rollback();
              return generateResponse($ex->getMessage(),true,true);
        }	
		
	}	
}