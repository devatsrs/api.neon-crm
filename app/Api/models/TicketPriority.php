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
}