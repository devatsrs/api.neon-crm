<?php

namespace Api\Controllers;


use Api\Model\User;
use Api\Model\Company;
use Dingo\Api\Facade\API;
use Illuminate\Http\Request;
use Api\Requests\UserRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Auth;

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
        try {
            if(!empty($UserID['LoggedUserID'])){
                $user = User::find($UserID['LoggedUserID']);
            }else {
                $user = User::where(['EmailAddress'=>$credentials['LoggedEmailAddress']])->first();
                if(!Hash::check($credentials['password'], $user->password)){
                    return response()->json(['error' => 'invalid_credentials'], 401);
                }
            }
            $token = JWTAuth::fromUser($user);
        } catch (JWTException $e) {
            // something went wrong whilst attempting to encode the token
            return response()->json(['error' => 'could_not_create_token'], 500);
        }
        site_configration_cache();

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