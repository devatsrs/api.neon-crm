<?php

namespace Api\Model;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TicketSla extends \Eloquent
{

    /**
     * Assign SLA policy to ticket
     */
    public static function assignSlaToTicket($CompanyID,$TicketID){

        $query 				=      	"call prc_AssignSlaToTicket (".$CompanyID.",".$TicketID.")";
        DB::select($query);

    }
}
