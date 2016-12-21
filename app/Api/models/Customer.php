<?php

namespace Api\Model;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Tymon\JWTAuth\Facades\JWTAuth;

class Customer extends Model implements AuthenticatableContract,
                                    AuthorizableContract,
                                    CanResetPasswordContract

{
    use Authenticatable, Authorizable, CanResetPassword;

    protected $table = 'tblAccount';
    protected $primaryKey = "AccountID";
	
	 /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['AccountName', 'BillingEmail', 'password'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['password', 'api_token'];

    /**
     * Get the identifier that will be stored in the subject claim of the JWT
     *
     * @return mixed
     */
    public function getJWTIdentifier() {
        return $this->attributes['AccountID'];
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT
     *
     * @return array
     */
    public function getJWTCustomClaims() {
        return [];
    }

    public static function get_companyID(){

        return Auth::user()->CompanyId;

    }

    public static function get_accountID(){
        return Auth::user()->AccountID;
    }

    public static function get_user_full_name(){
        return Auth::user()->FirstName.' '. Auth::user()->LastName;
    }

    public static function get_accountName(){
        return Auth::user()->AccountName;
    }

    public static function get_AuthorizeID(){
        return Auth::user()->AutorizeProfileID;
    }

    public static function get_Email(){
        return Auth::user()->Email;
    }

    public static function get_Billing_Email(){
        return Auth::user()->BillingEmail;
    }

    public static function get_currentUser(){
        return Auth::user();
    }

    public static function get_customer_picture_url($AccountID){

        $user_profile_img = Customer::where(["AccountID"=>$AccountID])->pluck('Picture');
        if(!empty($user_profile_img)) {
            return AmazonS3::unSignedImageUrl($user_profile_img);
        }
        return '';

    }
    public function getRememberToken()
    {
        return null; // not supported
    }

    public function setRememberToken($value)
    {
        // not supported
    }

    public function getRememberTokenName()
    {
        return null; // not supported
    }

    /**
     * Overrides the method to ignore the remember token.
     */
    public function setAttribute($key, $value)
    {
        $isRememberTokenAttribute = $key == $this->getRememberTokenName();
        if (!$isRememberTokenAttribute)
        {
            parent::setAttribute($key, $value);
        }
    }
}