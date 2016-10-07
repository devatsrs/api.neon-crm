<?php
namespace Api\Model;

use Illuminate\Database\Eloquent\Model;
class Messages extends \Eloquent {

    protected $fillable 	= 	['PID'];
    protected $table 		= 	"tblMessages";
    protected $primaryKey 	= 	"MsgID";
    public    $timestamps 	= 	false; // no created_at and updated_at
	
	const  Sent 			= 	0;
    const  Received			=   1;
    const  Draft 			= 	2;
}