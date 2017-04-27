<?php
namespace Api\Model;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Ticketfields extends \Eloquent {

    protected $table 		= 	"tblTicketfields";
    protected $primaryKey 	= 	"TicketFieldsID";
	protected $guarded 		=	 array("TicketFieldsID");
   // public    $timestamps 	= 	false; // no created_at and updated_at	
  // protected $fillable = ['GroupName','GroupDescription','GroupEmailAddress','GroupAssignTime','GroupAssignEmail','GroupAuomatedReply'];
	protected $fillable = [];
	
	
	const  FIELD_TYPE_STATIC  		= 	0;
    const  FIELD_TYPE_DYNAMIC 		= 	1;
	
	const  FIELD_HTML_TEXT    		= 	1;
	const  FIELD_HTML_TEXTAREA    	= 	2;
	const  FIELD_HTML_CHECKBOX    	= 	3;
	const  FIELD_HTML_TEXTNUMBER    = 	4;
	const  FIELD_HTML_DROPDOWN    	= 	5;
	const  FIELD_HTML_DATE    		= 	6;
	const  FIELD_HTML_DECIMAL    	= 	7;
	
	const  TICKET_SYSTEM    		= 	0;
	const  TICKET_FRESHDESK    		= 	1;
	
	const  TICKET_SYSTEM_STATUS_FLD  	 	 = 	'default_status';
	const  TICKET_SYSTEM_STATUS_DEFAULT  	 = 	'Open';
	const  TICKET_SYSTEM_TYPE_FLD    	 	 =  'default_ticket_type';

    const   default_requester       =   1;
    const   default_subject         =   2;
    const   default_ticket_type     =   3;
    const   default_status          =   4;
    const   default_priority        =   5;
    const   default_group           =   6;
    const   default_description     =   7;
    const   default_agent           =   8;

    static  $defaultTicketFields = [
        'Search a requester'=>Ticketfields::default_requester,
        'Subject'=>Ticketfields::default_subject,
        'Type'=>Ticketfields::default_ticket_type,
        'Status'=>Ticketfields::default_status,
        'Priority'=>Ticketfields::default_priority,
        'Group'=>Ticketfields::default_group,
        'Description'=>Ticketfields::default_description,
        'Agent'=>Ticketfields::default_agent
    ];
	
	
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
	
	static	function OptimizeDbFields($Ticketfields){
		//$clas = (object) array();
		$result 	=  	 array();
		
		foreach($Ticketfields as $key =>  $TicketFieldsData){
				$data						   =		array();
				$TicketFieldsID 			   = 		$TicketFieldsData->TicketFieldsID;
				$TicketfieldsValues 	 	   = 		TicketfieldsValues::where(["FieldsID"=>$TicketFieldsID])->orderBy('FieldOrder', 'asc')->get();
				$data['id']       			   = 		$TicketFieldsData->TicketFieldsID;
				$data['type']        		   = 		Ticketfields::$type[$TicketFieldsData->FieldHtmlType];
				$data['name']       		   = 		$TicketFieldsData->FieldName;
				$data['label']       		   = 		$TicketFieldsData->AgentLabel;
				$data['dom_type']       	   = 		$TicketFieldsData->FieldDomType;
				$data['field_type']  		   = 		$TicketFieldsData->FieldType;
				$data['label_in_portal']  	   = 		$TicketFieldsData->CustomerLabel;
				$data['description']  		   = 		$TicketFieldsData->FieldDesc;
				$data['has_section']  		   = 		'';				
				$data['position']  			   = 		$TicketFieldsData->FieldOrder;
				$data['active']  			   = 		1;
				$data['required']  			   = 		$TicketFieldsData->AgentReqSubmit;
				$data['required_for_closure']  = 		$TicketFieldsData->AgentReqClose;
				$data['visible_in_portal']     = 		$TicketFieldsData->CustomerDisplay;
				$data['editable_in_portal']    = 		$TicketFieldsData->CustomerEdit;
				$data['required_in_portal']    = 		$TicketFieldsData->CustomerReqSubmit;
				$data['FieldStaticType']    	= 		$TicketFieldsData->FieldStaticType;				
				$data['field_options']  	   = 		(object) array();				
				$choices 					   = 		array();	
				
				if(count($TicketfieldsValues)>0 &&  $data['field_type']!='default_priority' &&  $data['field_type']!='default_group'){
					foreach($TicketfieldsValues as $key => $TicketfieldsValuesData){
						if($data['field_type']=='default_status')
						{
						$choices[] = (object) array('status_id'=>$TicketfieldsValuesData->ValuesID,'name'=>$TicketfieldsValuesData->FieldValueAgent,'customer_display_name'=>$TicketfieldsValuesData->FieldValueCustomer,"stop_sla_timer"=>$TicketfieldsValuesData->FieldSlaTime,"deleted"=>'');
						}
						else if($data['field_type']=='default_ticket_type'){
						$choices[] =  array('0'=>$TicketfieldsValuesData->FieldValueAgent,'1'=>$TicketfieldsValuesData->FieldValueAgent,"2"=>$TicketfieldsValuesData->ValuesID);
						}else{
						$choices[] =  array('0'=>$TicketfieldsValuesData->FieldValueAgent,"1"=>$TicketfieldsValuesData->ValuesID);
						}
					}
				}else{									
					if($data['field_type']=='default_priority'){						
						$TicketPriority = DB::table('tblTicketPriority')->orderBy('PriorityID', 'asc')->get(); 								
						foreach($TicketPriority as $TicketPriorityData){						
							$choices[] =  array("0"=>$TicketPriorityData->PriorityValue,'1'=>$TicketPriorityData->PriorityID);
						}
					}
					
					if($data['field_type']=='default_group'){						
						$TicketGroups = DB::table('tblTicketGroups')->orderBy('GroupID', 'asc')->get(); 						
						foreach($TicketGroups as $TicketGroupsData){
							$choices[] =  array("0"=>$TicketGroupsData->GroupName,'1'=>$TicketGroupsData->GroupID);
						}
					}
				}
				
				$data['choices']	=  $choices;			
				$result[] 			=  (object) $data;	
		}		
		//echo "<pre>"; print_r($result); echo "<pre>"; exit;
		return $result;
	}	
	
}