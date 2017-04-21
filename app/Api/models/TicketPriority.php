<?php
namespace Api\Model;

use Illuminate\Database\Eloquent\Model;
class TicketPriority extends \Eloquent {
	
    protected $table 		= 	"tblTicketPriority";
    protected $primaryKey 	= 	"PriorityID";
	protected $guarded 		=	 array("PriorityID");
	
	
	static function getTicketPriority(){
		//TicketfieldsValues::WHERE
		 $row =  TicketPriority::orderBy('PriorityID')->lists('PriorityValue', 'PriorityID');
		 $row =  array("0"=> "Select")+json_decode(json_encode($row),true);
		 return $row;
	}
	
	static $DefaultPriority = 'Low';
	
	
	static function getDefaultPriorityStatus(){
			return TicketPriority::where(["PriorityValue"=>TicketPriority::$DefaultPriority])->pluck('PriorityID');
	}
	
	static function getPriorityStatusByID($id){
			return TicketPriority::where(["PriorityID"=>$id])->pluck('PriorityValue');
	}
	
	static function getPriorityIDByStatus($id){ 
			return TicketPriority::where(["PriorityValue"=>$id])->pluck('PriorityID');
	}
}