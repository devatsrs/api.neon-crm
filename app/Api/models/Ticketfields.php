<?php
namespace Api\Model;

use Illuminate\Database\Eloquent\Model;
class Ticketfields extends \Eloquent {

    protected $table 		= 	"tblTicketfields";
    protected $primaryKey 	= 	"TicketFieldsID";
	protected $guarded 		=	 array("TicketFieldsID");
   // public    $timestamps 	= 	false; // no created_at and updated_at	
  // protected $fillable = ['GroupName','GroupDescription','GroupEmailAddress','GroupAssignTime','GroupAssignEmail','GroupAuomatedReply'];
	protected $fillable = [];
	
	
	static  $FIELD_TYPE_STATIC  		= 	0;
    static  $FIELD_TYPE_DYNAMIC 		= 	1;
	
	const  FIELD_HTML_TEXT    		= 	1;
	const  FIELD_HTML_TEXTAREA    	= 	2;
	const  FIELD_HTML_CHECKBOX    	= 	3;
	const  FIELD_HTML_TEXTNUMBER    = 	4;
	const  FIELD_HTML_DROPDOWN    	= 	5;
	const  FIELD_HTML_DATE    		= 	6;
	const  FIELD_HTML_DECIMAL    	= 	7;
	
	const  TICKET_SYSTEM    		= 	0;
	const  TICKET_FRESHDESK    		= 	1;
	
	const  TICKET_SYSTEM_STATUS_FLD  = 	'default_status';
	const  TICKET_SYSTEM_TYPE_FLD    =  'default_ticket_type';
	
	
	public static $field_html_type = array();
	
	
	public static $Checkboxfields = array("AgentReqSubmit","AgentReqClose","CustomerDisplay","CustomerEdit","CustomerReqSubmit","AgentCcDisplay","CustomerCcDisplay");
	
			public static $type = array(
				self::FIELD_HTML_TEXT => 'text',
				self::FIELD_HTML_TEXTAREA => 'paragraph',
				self::FIELD_HTML_CHECKBOX => 'checkbox',
				self::FIELD_HTML_TEXTNUMBER => 'number',
				self::FIELD_HTML_DROPDOWN => 'dropdown',
				self::FIELD_HTML_DATE => 'date',
				self::FIELD_HTML_DECIMAL => 'decimal',
			);
			
			public static $TypeSave = array(
				'text' => self::FIELD_HTML_TEXT,
				'paragraph' => self::FIELD_HTML_TEXTAREA,
				'checkbox' => self::FIELD_HTML_CHECKBOX,
				 'number'=> self::FIELD_HTML_TEXTNUMBER,
				'dropdown' => self::FIELD_HTML_DROPDOWN,
				'date' => self::FIELD_HTML_DATE,
				'decimal' => self::FIELD_HTML_DECIMAL,
			);
			
	public static 	$staticfields = array("default_requester","default_subject" ,"default_ticket_type" ,"default_status" ,"default_priority" ,"default_group","default_agent","default_description");
	

}