<?php
namespace Api\Model;

use Illuminate\Database\Eloquent\Model;
class TicketGroupAgents extends \Eloquent {

    protected $table 		= 	"tblTicketGroupAgents";
    protected $primaryKey 	= 	"GroupAgentsID";
    protected $fillable 	= 	['GroupID'];
   // public    $timestamps 	= 	false; // no created_at and updated_at	
}