<?php
namespace Api\Model;
use Api\Model\TicketGroupAgents;


use Illuminate\Database\Eloquent\Model;
class TicketGroups extends \Eloquent{
    protected $table 		= 	"tblTicketGroups";
    protected $primaryKey 	= 	"GroupID";
	protected $guarded 		=	 array("GroupID");
   // public    $timestamps 	= 	false; // no created_at and updated_at	
  // protected $fillable = ['GroupName','GroupDescription','GroupEmailAddress','GroupAssignTime','GroupAssignEmail','GroupAuomatedReply'];
	protected $fillable = [];
	
   public static $EscalationTimes = array(
	   "1800"=>"30 Minutes",
	   "3600"=>"1 Hour",
	   "7200"=>"2 Hours",
	   "14400"=>"4 Hours",
	   "28800"=>"8 Hours",   
	   "43200"=>"12 Hours",
	   "86400"=>"1 Day",
	   "172800"=>"2 Days",
	   "259200"=>"3 Days",
   );
   
   
   static function getTicketGroups(){
		//TicketfieldsValues::WHERE
	   	   $CompanyID = User::get_companyID();
		   $row =  TicketGroups::where(["CompanyID"=>$CompanyID])->orderBy('GroupID', 'asc')->lists('GroupName','GroupID');
		   $row =  array("0"=> "Select")+json_decode(json_encode($row),true);
		   return $row;
	}
	
	
    static function get_support_email_by_remember_token($remember_token) {
        if (empty($remember_token)) {
            return FALSE;
        }
		$CompanyID = User::get_companyID();
		$result = TicketGroups::where(["CompanyID"=>$CompanyID])->where(["remember_token"=>$remember_token])->first();
        if (!empty($result)) {
            return $result;
        } else {
            return FALSE;
        }
    }
	
	
	static function Get_User_Groups($id)
	{
		$groupsArray	=	array();
		$Groupagentsdb	=	TicketGroupAgents::where(["UserID"=>$id])->get(); 
		foreach($Groupagentsdb as $Groupagentsdata){
			$groupsArray[]	= $Groupagentsdata->GroupID;	
		} 
		return implode(",",$groupsArray);
	}
}