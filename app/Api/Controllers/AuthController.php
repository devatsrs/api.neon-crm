<?php

namespace Api\Controllers;


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
        try {
			 if(!empty($LoginType) && $LoginType['LoginType']=='customer') {
				$user = Account::where(['BillingEmail'=>$credentials['LoggedEmailAddress']])->first(); 
				$user->CompanyID = $user->CompanyId;
				Config::set('auth.providers.users.model', \Api\Model\Customer::class);			   
				if(!Hash::check($credentials['password'], $user->password)){
                    Log::info("class AuthController");
                    Log::info($credentials);
                    return response()->json(['error' => 'invalid_credentials'], 401);
				 } 
			 }
			 else{
				if(!empty($UserID['LoggedUserID'])){
					$user = User::find($UserID['LoggedUserID']);
				}else {
					$user = User::where(['EmailAddress'=>$credentials['LoggedEmailAddress']])->first();
					if(!Hash::check($credentials['password'], $user->password)){
                        Log::info("class AuthController");
                        Log::info($credentials);
                        Log::info("password " . $user->password);
                        return response()->json(['error' => 'invalid_credentials'], 401);
					}
				}
			 }
            $token = JWTAuth::fromUser($user);
        } catch (JWTException $e) {
            // something went wrong whilst attempting to encode the token
            return response()->json(['error' => 'could_not_create_token'], 500);
        }
        CompanyConfiguration::getConfiguration($user->CompanyID);
        site_configration_cache($request);

        // all good so return the token
        return response()->json(compact('token'));
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
        Session::flush();
        Auth::logout();
        //JWTAuth::invalidate(JWTAuth::getToken());
        return $this->response()->accepted()->header('Authorization', '');
    }
}