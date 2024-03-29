<?php

namespace Api\Controllers;
use Illuminate\Support\Facades\DB; 
use Api\Model\CompanyConfiguration;
use Api\Model\User;
use Api\Model\Account;
use Dingo\Api\Facade\API;
use Illuminate\Http\Request;
use Api\Requests\UserRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

class AuthController extends BaseController
{
    public function me(Request $request){
        return JWTAuth::parseToken()->authenticate();
    }

    public function authenticate(Request $request){
        // grab credentials from the request
        $credentials = $request->only('LoggedEmailAddress', 'password');
        $license  = $request->only('LicenceKey','CompanyName');
        $license['LicenceHost'] = $request->getHttpHost();
        $license['LicenceIP'] = $request->getClientIp();
        $UserID = $request->only('LoggedUserID');
		$LoginType	=	$request->only('LoginType'); 
			
        Log::info("Authenticate");
        Log::info(print_r($license,true));
        Log::info("UserID ". print_r($UserID,true));
        Log::info("credentials ". print_r($credentials,true));
        Log::info("license ". print_r($license,true));
        Log::info("LoginType ".print_r($LoginType,true));
        try { 
			 if(!empty($LoginType) && $LoginType['LoginType']=='customer') {
				//$user = Account::where(['BillingEmail'=>$credentials['LoggedEmailAddress']])->first(); 
				$user = Account::whereRaw(" Status=1 AND FIND_IN_SET('".$credentials['LoggedEmailAddress']."',BillingEmail) !=0")->first();
				$user->CompanyID = $user->CompanyId;
				Config::set('auth.providers.users.model', \Api\Model\Customer::class);
                 Log::info("Customer Login");
                 // if(!Hash::check($credentials['password'], $user->password)){
				if(!User::checkPassword($credentials['password'],$user->password)){
                     Log::info("class AuthController");
                    Log::info($credentials);
					return response()->json(['error' => 'invalid_credentials'], 401);
				 } 
			 }elseif(!empty($LoginType) && $LoginType['LoginType']=='reseller') {
                 $user = User::where(['EmailAddress'=>$credentials['LoggedEmailAddress'],'Status'=>1])->first();
                 //if(!Hash::check($credentials['password'], $user->password)){
                 if(!User::checkPassword($credentials['password'],$user->password)){
                     Log::info("class AuthController");
                     Log::info($credentials);
                     Log::info("password " . $user->password);
                     return response()->json(['error' => 'invalid_credentials'], 401);
                 }
             }else{
				if(!empty($UserID['LoggedUserID'])){
					$user = User::find($UserID['LoggedUserID']);
                    Log::info(print_r($user,true));

                }else {
					$user = User::where(['EmailAddress'=>$credentials['LoggedEmailAddress'],'Status'=>1])->first();
					//if(!Hash::check($credentials['password'], $user->password)){
                    if(!User::checkPassword($credentials['password'],$user->password)){
                        Log::info("class AuthController");
                        Log::info($credentials);
                        Log::info("password " . $user->password);
                        return response()->json(['error' => 'invalid_credentials'], 401);
					}
				}
			 }
            Log::info(print_r($user,true));
            $token = JWTAuth::fromUser($user);
            Log::info("Token is here " . $token);
        } catch (JWTException $e) {
            // something went wrong whilst attempting to encode the token
            Log::info("could_not_create_token ");
            return response()->json(['error' => 'could_not_create_token'], 500);
        }
        Log::info("here");
        CompanyConfiguration::getConfiguration($user->CompanyID);
        site_configration_cache($request);

        // all good so return the token
        return \Dingo\Api\Facade\API::response()->array(compact('token'))->statusCode(200);

    }

    public function validateToken(){
        // Our routes file should have already authenticated this token, so we just return success here
        return API::response()->array(['status' => 'success'])->statusCode(200);
    }

    public function register(UserRequest $request){
        $newUser = [
            'name' => $request->get('name'),
            'email' => $request->get('email'),
            'password' => bcrypt($request->get('password')),
        ];
        $user = User::create($newUser);
        $token = JWTAuth::fromUser($user);

        return response()->json(compact('token'));
    }

    public function logout() {
        Log::info("Logout fn class AuthController");
        Session::flush();
        Auth::logout();
        //JWTAuth::invalidate(JWTAuth::getToken());
        return $this->response()->accepted()->header('Authorization', '');
    }
}