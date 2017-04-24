<?php
namespace Api\Controllers;

use Api\Model\TicketGroups;
use Api\Model\TicketsTable;
use Api\Model\User;
use Dingo\Api\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class TicketDashboard extends BaseController {

    public function __construct(Request $request){
        $this->middleware('jwt.auth');
        Parent::__Construct($request);
    }

    public function ticketSummaryWidget(){
        $data 					= 	Input::all();
        $CompanyID 				= 	User::get_companyID();
        $AccessPermission		=	isset($data['AccessPermission'])?$data['AccessPermission']:0;
        $Group = $agent = '';

        if($AccessPermission == TicketsTable::TICKETGROUPACCESS){ //group access
            $Group = TicketGroups::Get_User_Groups(User::get_userID());
        }else if($AccessPermission == TicketsTable::TICKETRESTRICTEDACCESS){ //assigned ticket access
            $agent = User::get_userID();
        }

        $query 		= 	"call prc_GetTicketDashboardSummary ('".$CompanyID."','".$Group."','".$agent."')";
        Log::info("query:".$query);
        $result1 = DB::select($query);
        $query 		= 	"call prc_CheckDueTickets (".$CompanyID.",'".date('Y-m-d H:i:s')."','".$Group."','".$agent."')";
        Log::info("query:".$query);
        $result2 = DB::select($query);
        $result = (object) array_merge((array) $result1, (array) $result2);
        return generateResponse('',false,false,$result);
    }

    public function ticketTimeLineWidget(){
        $data 					= 	Input::all();
        $CompanyID 				= 	User::get_companyID();
        $AccessPermission		=	isset($data['AccessPermission'])?$data['AccessPermission']:0;
        $rules['iDisplayStart']     =   'required|numeric|Min:0';
        $rules['iDisplayLength']    =   'required|numeric';
        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            return generateResponse($validator->errors(),true);
        }
        $Group = $agent = 0;

        if($AccessPermission == TicketsTable::TICKETGROUPACCESS){ //group access
            $Group = TicketGroups::Get_User_Groups(User::get_userID());
        }else if($AccessPermission == TicketsTable::TICKETRESTRICTEDACCESS){ //assigned ticket access
            $agent = User::get_userID();
        }

        $query 		= 	"call prc_GetTicketDashboardTimeline ('".$CompanyID."',".$Group.",".$agent.",'".date('Y-m-d H:i:s')."',".$data['iDisplayStart'].",".$data['iDisplayLength'].")";
        Log::info("query:".$query);
        $result = DB::select($query);
        return generateResponse('',false,false,$result);
    }
}