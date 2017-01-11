<?php
namespace Api\Model;

use Illuminate\Database\Eloquent\Model;
class TicketsConversation extends \Eloquent 
 {
    protected $table 		= 	 "tblTicketsConversation";
    protected $primaryKey 	= 	 "TicketConversationID";
	protected $guarded 		=	 array("TicketConversationID");
	protected $fillable		= 	 [];
	
	
	const ConversationIncoming	=  '1';
	const ConversationOutgoing	=  '0';
}