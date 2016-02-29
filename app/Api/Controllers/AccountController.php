<?php

namespace Api\Controllers;



use Api\Model\Account;
use App\Http\Requests;

class AccountController extends BaseController
{

    public function __construct() 
    {
        $this->middleware('jwt.auth');
    }

    /**
     * Show all dogs
     *
     * Get a JSON representation of all the dogs
     * 
     * @Get('/')
     */
    public function balance($id)
    {
        $Account = Account::find($id);
        return $Account->AccountName;
    }

}
