<?php

namespace Api\Controllers;

use App\EmailClient;
use Illuminate\Http\Request;
use App\Http\Requests;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Input;


class AutoRateImportController extends BaseController
{

    function validatesmtp(){
        $data = Input::all();
        $data['IsSSL'] = isset($data['IsSSL']) ? 1 : 0 ;
        $rules = array(
            'host' => 'required',
            'port' => 'required',
            'username' => 'required',
            'password' => 'required',
            'IsSSL' => 'required',
        );

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            return generateResponse($validator->errors(),true);
        }
        try
        {
            $result =  new EmailClient(["host"=>$data['host'], "port"=>$data['port'], "IsSSL"=>$data['IsSSL'], "username"=>$data['username'], "password"=>$data['password'] ]);
            if($result->isValidConnection()){
                return generateResponse('Validated.');
            }else{
                return generateResponse("could not connect",true,true);
            }
        }catch (Exception $ex){
            return generateResponse($ex->getMessage(),true,true);
        }
    }

}