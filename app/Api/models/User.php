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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;

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
		if(isset(Auth::user()->CompanyID)){
       	  return Auth::user()->CompanyID;
		}else{
		  return Auth::user()->CompanyId;
		}
    }

    public static  function get_user_full_name(){
        return Auth::user()->FirstName.' '. Auth::user()->LastName;
    }
	
	   public static function get_user_email(){     
	   if(isset(Auth::user()->EmailAddress)){
       	  return Auth::user()->EmailAddress;
		}else{
		  return Auth::user()->Email;
		}  	    
    }
	
	   public static function get_userID(){    
	   if(isset(Auth::user()->UserID)){
       	  return Auth::user()->UserID;
		}else{
		  return Auth::user()->AccountID;
		}   
        
    }
	
		public static function getUserIDListAll($select = 1){
        $where = array('Status'=>1,'CompanyID'=>User::get_companyID());
        $user = User::where($where);
        
        $row = json_decode(json_encode($user->select(array(DB::raw("concat(tblUser.FirstName,' ',tblUser.LastName) as FullName"), 'UserID'))->orderBy('FullName')->lists('FullName', 'UserID')),true);
        if(!empty($row) & $select==1){
			$row =  array("0"=> "Select")+$row;
        }
        return $row;
    }
	
	
	 public static function getUserIDListOnly($select = 1){
        $where = array('Status'=>1,'CompanyID'=>User::get_companyID());
        $user = User::where($where);
        if($select==0){
            $user->where('AdminUser','!=',1);
        }
        $row = $user->select(array(DB::raw("concat(tblUser.FirstName,' ',tblUser.LastName) as FullName"),'EmailAddress'))->orderBy('FullName')->lists('FullName', 'EmailAddress');
        return $row;
    }

    public static function checkPassword($LoginPassword,$Password){
        $result=false;
        try{
            if(Hash::check($LoginPassword, $Password) || $LoginPassword==Crypt::decrypt($Password)){
                $result=true;
            }
        }catch(Exception $e){

        }

        return $result;
    }

}
