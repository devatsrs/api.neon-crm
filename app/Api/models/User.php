<?php

namespace Api\Model;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

class User extends Model implements AuthenticatableContract,
                                    AuthorizableContract,
                                    CanResetPasswordContract

{
    use Authenticatable, Authorizable, CanResetPassword;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'tblUser';
    protected  $primaryKey = "UserID";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['FirstName', 'EmailAddress', 'password'];

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
        return $this->attributes['UserID'];
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
        return Auth::user()->CompanyID;
    }

    public static  function get_user_full_name(){
        return Auth::user()->FirstName.' '. Auth::user()->LastName;
    }
    public  static  function get_user_email(){
        return Auth::user()->EmailAddress;
    }
    public  static  function get_userID(){
        return Auth::user()->UserID;
    }
}
