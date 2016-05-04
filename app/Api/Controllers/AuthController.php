<?php

namespace Api\Controllers;


use Api\Model\User;
use Api\Model\Company;
use Dingo\Api\Facade\API;
use Illuminate\Http\Request;
use Api\Requests\UserRequest;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;


class AuthController extends BaseController
{
    public function me(Request $request)
    {
        return JWTAuth::parseToken()->authenticate();
    }

    public function authenticate(Request $request)
    {
        // grab credentials from the request
        $credentials = $request->only('EmailAddress', 'password');
        $credentials_license  = $request->only("LicenceHost","LicenceIP","LicenceKey");
        try {
            // attempt to verify the credentials and create a token for the user
            if (! $token = JWTAuth::attempt($credentials)) {
                return response()->json(['error' => 'invalid_credentials'], 401);
            }
        } catch (JWTException $e) {
            // something went wrong whilst attempting to encode the token
            return response()->json(['error' => 'could_not_create_token'], 500);
        }

		 $license = 	Company::getLicenceResponse($credentials_license);
		 if($license['Status']!=1)
		 {
			$this->logout();	
		   return response()->json(['error' => $license['Message']], 401);	
		 }
        create_site_configration_cache();

        // all good so return the token
        return response()->json(compact('token'));
    }

    public function validateToken() 
    {
        // Our routes file should have already authenticated this token, so we just return success here
        return API::response()->array(['status' => 'success'])->statusCode(200);
    }

    public function register(UserRequest $request)
    {
        $newUser = [
            'name' => $request->get('name'),
            'email' => $request->get('email'),
            'password' => bcrypt($request->get('password')),
        ];
        $user = User::create($newUser);
        $token = JWTAuth::fromUser($user);

        return response()->json(compact('token'));
    }

    public function byId($id){
        $user = User::find($id);
        $token = JWTAuth::fromUser($user);
        create_site_configration_cache();
        return response()->json(compact('token'));
    }

    public function logout() {
        Session::flush();
        Auth::logout();
        //JWTAuth::invalidate(JWTAuth::getToken());
        return $this->response()->accepted()->header('Authorization', '');
    }
}