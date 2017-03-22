<?php
namespace Api\Model;
use Illuminate\Support\Facades\Log;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Input;

class TicketsTable extends \Eloquent 
{
    protected $guarded = array("TicketID");

    protected $table = 'tblTickets';

    protected $primaryKey = "TicketID";
	
    static  $FreshdeskTicket  		= 	2;
    static  $SystemTicket 			= 	1;
	const   TICKET					=	0;
	const   EMAIL					=	1;
	const   TICKETGLOBALACCESS		=	1;
	const   TICKETGROUPACCESS		=	2;
	const   TICKETRESTRICTEDACCESS	=	3;
	static  $defaultSortField 		= 	'created_at';
	static  $defaultSortType 		= 	'desc';
	static  $Sortcolumns			=	array("created_at"=>"Date Created","subject"=>"Subject","status"=>"Status","group"=>"Group","updated_at"=>"Last Modified");
    static $currentObj = '';

    public static function boot(){
        parent::boot();

        static::created(function($obj)
        {
            Log::info('i am here');
            Log::info($obj);
        });


        static::updated(function($obj) {
            $differ = array_diff($obj->attributes,$obj->original);
            unset($differ['updated_at']);
            if(count($differ) > 0) {
                $UserID = User::get_userID();
                $CompanyID = User::get_userID();
                foreach ($differ as $index => $key) {
                    if(array_key_exists($index,Ticketfields::$defaultTicketFields)) {
                        $data = ['UserID' => $UserID,
                            'CompanyID' => $CompanyID,
                            'TicketID' => $obj->TicketID,
                            'TicketFieldID' => Ticketfields::$defaultTicketFields[$index],
                            'TicketFieldValueFromID' => $obj->original[$index],
                            'TicketFieldValueToID' => $obj->attributes[$index],
                            "created_at" => date("Y-m-d H:i:s")];
                        TicketLog::insert($data);
                    }
                    //Log::info('change ' . $obj->original[$index] . ' to ' . $obj->attributes[$index] . ' ' . PHP_EOL);
                }
            }
        });
    }


    static function GetAgentSubmitRules($page='all'){
		 $rules 	 =  array();
		 $messages	 =  array();
		 $fields 	 = 	Ticketfields::where(['AgentReqSubmit'=>1])->get();
		 
		foreach($fields as $fieldsdata)	 
		{	
			if($page=='DetailPage' && ($fieldsdata->FieldType=='default_requester' || $fieldsdata->FieldType=='default_subject' || $fieldsdata->FieldType=='default_description')){continue;}
			
			$rules[$fieldsdata->FieldType] = 'required';
			$messages[$fieldsdata->FieldType.".required"] = "The ".$fieldsdata->AgentLabel." field is required";
		}
		
		return array("rules"=>$rules,"messages"=>$messages);
	}
	
	static function GetCustomerSubmitRules($page='all'){
		 $rules 	 =  array();
		 $messages	 =  array();
		 $fields 	 = 	Ticketfields::where(['CustomerReqSubmit'=>1])->get();
		 
		foreach($fields as $fieldsdata)	 
		{	
			if($page=='DetailPage' && ($fieldsdata->FieldType=='default_requester' || $fieldsdata->FieldType=='default_subject' || $fieldsdata->FieldType=='default_description' || $fieldsdata->FieldType=='default_group')){continue;}
			
			if(($fieldsdata->FieldType=='default_requester'  || $fieldsdata->FieldType=='default_group' || $fieldsdata->FieldType=='default_agent' )){continue;}
			
			$rules[$fieldsdata->FieldType] = 'required';
			$messages[$fieldsdata->FieldType.".required"] = "The ".$fieldsdata->AgentLabel." field is required";
		}
		
		return array("rules"=>$rules,"messages"=>$messages);
	}
	
	static function GetAgentSubmitComposeRules(){
		
		 $rules 	 =  array();
		 $messages	 =  array();
		 $fields 	 = 	Ticketfields::where(['AgentReqSubmit'=>1])->get();
		
		 
		foreach($fields as $fieldsdata)	 
		{
			if(($fieldsdata->FieldType=='default_requester'  || $fieldsdata->FieldType=='default_group' || $fieldsdata->FieldType=='default_agent' || $fieldsdata->FieldType=='default_subject' || $fieldsdata->FieldType=='default_description' )){continue;}
		
			$rules[$fieldsdata->FieldType] = 'required';
			$messages[$fieldsdata->FieldType.".required"] = "The ".$fieldsdata->AgentLabel." field is required";
		}
		
		return array("rules"=>$rules,"messages"=>$messages);
	
	}
	
	static function getClosedTicketStatus(){
		//TicketfieldsValues::WHERE
		 $ValuesID =  TicketfieldsValues::join('tblTicketfields','tblTicketfields.TicketFieldsID','=','tblTicketfieldsValues.FieldsID')
            ->where(['tblTicketfields.FieldType'=>Ticketfields::TICKET_SYSTEM_STATUS_FLD])->where(['tblTicketfieldsValues.FieldValueAgent'=>TicketfieldsValues::$Status_Closed])->pluck('ValuesID');			
			return $ValuesID;
	}
	
	static function getResolvedTicketStatus(){
		//TicketfieldsValues::WHERE
		 $ValuesID =  TicketfieldsValues::join('tblTicketfields','tblTicketfields.TicketFieldsID','=','tblTicketfieldsValues.FieldsID')
            ->where(['tblTicketfields.FieldType'=>Ticketfields::TICKET_SYSTEM_STATUS_FLD])->where(['tblTicketfieldsValues.FieldValueAgent'=>TicketfieldsValues::$Status_Resolved])->pluck('ValuesID');			
			return $ValuesID;
	}
	
	static function getTicketStatusByID($id,$fld='FieldValueAgent'){
		//TicketfieldsValues::WHERE
		 $ValuesID =  TicketfieldsValues::join('tblTicketfields','tblTicketfields.TicketFieldsID','=','tblTicketfieldsValues.FieldsID')
            ->where(['tblTicketfields.FieldType'=>Ticketfields::TICKET_SYSTEM_STATUS_FLD])->where(['tblTicketfieldsValues.ValuesID'=>$id])->pluck($fld);			
			return $ValuesID;
	}
	
	
	static function getTicketStatus(){
		//TicketfieldsValues::WHERE
		 $row =  TicketfieldsValues::join('tblTicketfields','tblTicketfields.TicketFieldsID','=','tblTicketfieldsValues.FieldsID')->select(array('FieldValueAgent', 'ValuesID'))->where(['tblTicketfields.FieldType'=>Ticketfields::TICKET_SYSTEM_STATUS_FLD])->lists('FieldValueAgent','ValuesID');		
			 if(!empty($row)){
				$row =  array("0"=> "Select")+json_decode(json_encode($row),true);
			}	
			return $row;
	}
	
	
	static function getTicketType(){
		//TicketfieldsValues::WHERE
		 $row =  TicketfieldsValues::join('tblTicketfields','tblTicketfields.TicketFieldsID','=','tblTicketfieldsValues.FieldsID')
            ->where(['tblTicketfields.FieldType'=>Ticketfields::TICKET_SYSTEM_TYPE_FLD])->lists('FieldValueAgent','ValuesID');
			$row = array("0"=> "Select")+$row;
			return $row;
	}
	
	static function SetUpdateValues($TicketData,$ticketdetaildata,$Ticketfields){
			//$TicketData  = '';
			$data = array();
			
			foreach($Ticketfields as $TicketfieldsData)
			{	 
				if(in_array($TicketfieldsData->FieldType,Ticketfields::$staticfields))
				{		
					if($TicketfieldsData->FieldType=='default_requester')
					{ 			
						$data[$TicketfieldsData->FieldType] = $TicketData->RequesterName." <".$TicketData->Requester.">";
					}
					
					if($TicketfieldsData->FieldType=='default_subject')
					{
						$data[$TicketfieldsData->FieldType] = $TicketData->Subject;
					}
					
					if($TicketfieldsData->FieldType=='default_ticket_type')
					{
						$data[$TicketfieldsData->FieldType] = $TicketData->Type;
					}
					
					if($TicketfieldsData->FieldType=='default_status')
					{
						$data[$TicketfieldsData->FieldType] = $TicketData->Status;
					}	
					
					if($TicketfieldsData->FieldType=='default_status')
					{
						$data[$TicketfieldsData->FieldType] = $TicketData->Status;
					}
					
					if($TicketfieldsData->FieldType=='default_priority')
					{
						$data[$TicketfieldsData->FieldType] = $TicketData->Priority;
					}
					
					if($TicketfieldsData->FieldType=='default_group')
					{
						$data[$TicketfieldsData->FieldType] = $TicketData->Group;
					}
					
					if($TicketfieldsData->FieldType=='default_agent')
					{
						$data[$TicketfieldsData->FieldType] = $TicketData->Agent;
					}
					
					if($TicketfieldsData->FieldType=='default_description')
					{
						$data[$TicketfieldsData->FieldType] = $TicketData->Description;
					}
				}else{
					$found = 0;
					foreach($ticketdetaildata as $ticketdetail){						
						if($TicketfieldsData->TicketFieldsID == $ticketdetail->FieldID){
							$data[$TicketfieldsData->FieldType] = $ticketdetail->FieldValue;
							$found=1;
							break;
						}
					}
					if($found==0){					
						if(($TicketfieldsData->FieldHtmlType == Ticketfields::FIELD_HTML_TEXT) || ($TicketfieldsData->FieldHtmlType == Ticketfields::FIELD_HTML_TEXTAREA) || ($TicketfieldsData->FieldHtmlType == Ticketfields::FIELD_HTML_DATE)){
							$data[$TicketfieldsData->FieldType] =  '';
						}else{
							$data[$TicketfieldsData->FieldType] =  0;
						}
					}
						
				}
				
			}
			
			$data['AttachmentPaths']  = 	 $TicketData->AttachmentPaths;	
			//Log::info(print_r($data,true));	
			return $data;
	}
	
	 
	static function CheckTicketLicense(){
		return true;
		//return false;
	}
	
		static function getDefaultStatus(){			
		 $ValuesID =  TicketfieldsValues::join('tblTicketfields','tblTicketfields.TicketFieldsID','=','tblTicketfieldsValues.FieldsID')
            ->where(['tblTicketfields.FieldType'=>Ticketfields::TICKET_SYSTEM_STATUS_FLD])->where(['tblTicketfieldsValues.FieldValueAgent'=>Ticketfields::TICKET_SYSTEM_STATUS_DEFAULT,'tblTicketfieldsValues.FieldType'=>Ticketfields::FIELD_TYPE_STATIC])->pluck('ValuesID');			
			return $ValuesID;	
	}
	
	static function CheckTicketAccount($id){
		$data 		 	= 	Input::all(); 
		$TicketsData 	=	TicketsTable::find($id);	
		$accountID 		= 	0;
		
		if($TicketsData)
		{
			$accountID =  Account::where(array("Email"=>$TicketsData->Requester))->orWhere(array("BillingEmail"=>$TicketsData->Requester))->pluck('AccountID');
			if(!$accountID)
			{
				if(isset($data['LoginType']) && $data['LoginType']=='customer')
				{
					$accountID = User::get_userID();		
				}
			}
			if(!$accountID)
			{
				$accountID =0;
			}
		}
		return $accountID;

	}
	
	static function getTicketTypeByID($id,$fld='FieldValueAgent'){
		//TicketfieldsValues::WHERE
			$ValuesID =  TicketfieldsValues::join('tblTicketfields','tblTicketfields.TicketFieldsID','=','tblTicketfieldsValues.FieldsID')
            ->where(['tblTicketfields.FieldType'=>Ticketfields::TICKET_SYSTEM_TYPE_FLD])->where(['tblTicketfieldsValues.ValuesID'=>$id])->pluck($fld);			
			return $ValuesID;
	}
}