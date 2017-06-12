<?php
namespace Api\Model;
use Illuminate\Support\Facades\Log;

class UserProfile extends \Eloquent {
    protected $fillable = [];
    protected $guarded= [];
    protected $table = "tblUserProfile";
    protected $primaryKey = "UserProfileID";

    public static function get_user_picture_url($user_id){

        $site_url = \Api\Model\CompanyConfiguration::get("WEB_URL");
        $user_profile_img = UserProfile::where(["UserID"=>$user_id])->pluck('Picture');
        if(empty($user_profile_img)){
            $user_profile_img =  combile_url_path($site_url,'assets/images/placeholder-male.gif');
        }else{
            $user_profile_img =  \App\AmazonS3::unSignedImageUrl($user_profile_img);
        } 
        return $user_profile_img;
    }
}