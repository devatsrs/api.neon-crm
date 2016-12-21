<?php
namespace Api\Model;

use Illuminate\Database\Eloquent\Model;
class TicketfieldsValues extends \Eloquent {

    protected $table 		= 	"tblTicketfieldsValues";
    protected $primaryKey 	= 	"ValuesID";
	protected $guarded 		=	 array("ValuesID");
   // public    $timestamps 	= 	false; // no created_at and updated_at	
  // protected $fillable = ['GroupName','GroupDescription','GroupEmailAddress','GroupAssignTime','GroupAssignEmail','GroupAuomatedReply'];
	protected $fillable = [];
	
	static $Status_Closed = 'Closed';
	
}