<?php
namespace Api\Controllers;

use Api\Model\Account;
use Api\Model\DataTableSql;
use Api\Model\TicketGroups;
use Api\Model\TicketsTable;
use Api\Model\User;
use Dingo\Api\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
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
        $result = DB::select($query);
        Log::info("query:".$query);
        return generateResponse('',false,false,$result);
    }

    public function ticketTimeLineWidget(){
        $data 					= 	Input::all();
        $CompanyID 				= 	User::get_companyID();
        $AccessPermission		=	isset($data['AccessPermission'])?$data['AccessPermission']:0;
        $p_PageNumber = 1;
        $Group = $agent = 0;
        $p_RowsPage = 10;

        if($AccessPermission == TicketsTable::TICKETGROUPACCESS){ //group access
            $Group = TicketGroups::Get_User_Groups(User::get_userID());
        }else if($AccessPermission == TicketsTable::TICKETRESTRICTEDACCESS){ //assigned ticket access
            $agent = User::get_userID();
        }

        $query 		= 	"call prc_GetTicketDashboardTimeline ('".$CompanyID."',".$Group.",".$agent.",".$p_PageNumber.",".$p_RowsPage.")";
        Log::info("query:".$query);
        $result = DB::select($query);
        return generateResponse('',false,false,$result);
    }
}