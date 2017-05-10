<?php

namespace App\Http\Middleware;

use Api\Model\TicketsTable;
use Api\Model\User;
use Api\Model\Company;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckTicketLicence
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     *
     */


	 
    public function handle($request, Closure $next){
        $CompanyID = User::get_companyID();
        if($CompanyID>0) {
            if(!TicketsTable::validateTicketingLicence()){
                return generateResponse("Invalid Ticket Licence",true,true);
            }
        }
		 $response = $next($request);
        return $response;

	}
}
