<?php
namespace Api\Model;
use App\TicketEmails;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Input;
use Api\Model\CompanyConfiguration;
use \App\Imap;

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

        static::creating(function($obj)
        {
            //Log::info($obj);
        });


        static::updated(function($obj) {
            $UserID = User::get_userID();
            $CompanyID = User::get_companyID();
            foreach($obj->original as $index=>$value){
                if(array_key_exists($index,TicketLog::$defaultTicketLogFields)) {
                    if($obj->attributes[$index] != $value){


/*
[2018-01-05 12:54:03] Staging.INFO: index - Type
[2018-01-05 12:54:03] Staging.INFO: obj->original
[2018-01-05 12:54:03] Staging.INFO: Array
(
    [TicketID] => 269
    [CompanyID] => 1
    [Requester] => jobs.shriramsoft@gmail.com
    [RequesterName] => Deven Sitapara
    [RequesterCC] =>
    [RequesterBCC] =>
    [AccountID] => 0
    [ContactID] => 22
    [UserID] => 0
    [Subject] => New Log testing 05-1-2018
    [Type] => 0
    [Status] => 13
    [Priority] => 1
    [Group] => 2
    [Agent] => 0
    [Description] => <div dir="ltr"><br>New Log testing 05-1-2018<br><br></div>

    [AttachmentPaths] => a:0:{}
    [TicketType] => 0
    [AccountEmailLogID] => 7525
    [Read] => 1
    [EscalationEmail] => 0
    [TicketSlaID] => 4
    [RespondSlaPolicyVoilationEmailStatus] => 0
    [ResolveSlaPolicyVoilationEmailStatus] => 0
    [DueDate] => 2018-01-05 12:36:32
    [CustomDueDate] => 0
    [AgentRepliedDate] =>
    [CustomerRepliedDate] =>
    [created_at] => 2018-01-05 12:21:32
    [created_by] => RMScheduler
    [updated_at] => 2018-01-05 12:21:32
    [updated_by] =>
)

[2018-01-05 12:54:03] Staging.INFO: obj->attributes
[2018-01-05 12:54:03] Staging.INFO: Array
(
    [TicketID] => 269
    [CompanyID] => 1
    [Requester] => jobs.shriramsoft@gmail.com
    [RequesterName] => Deven Sitapara
    [RequesterCC] =>
    [RequesterBCC] =>
    [AccountID] => 0
    [ContactID] => 22
    [UserID] => 0
    [Subject] => New Log testing 05-1-2018
    [Type] => 11
    [Status] => 14
    [Priority] => 1
    [Group] => 2
    [Agent] => 66
    [Description] => <div dir="ltr"><br>New Log testing 05-1-2018<br><br></div>

    [AttachmentPaths] => a:0:{}
    [TicketType] => 0
    [AccountEmailLogID] => 7525
    [Read] => 1
    [EscalationEmail] => 0
    [TicketSlaID] => 4
    [RespondSlaPolicyVoilationEmailStatus] => 0
    [ResolveSlaPolicyVoilationEmailStatus] => 0
    [DueDate] => 2018-01-05 12:36:32
    [CustomDueDate] => 0
    [AgentRepliedDate] =>
    [CustomerRepliedDate] =>
    [created_at] => 2018-01-05 12:21:32
    [created_by] => RMScheduler
    [updated_at] => 2018-01-05 12:54:03
    [updated_by] => Sumera Khan
)


						 * */

						$FromValue = $ToValue = '' ;
						$fromID =$obj->original[$index];
						$toID =$obj->attributes[$index];

						if($index == 'Type' || $index == 'Status') {

							$FieldValuesArray = TicketfieldsValues::select(['FieldValueAgent','ValuesID'])->lists('FieldValueAgent','ValuesID')->toArray();
							$FieldValues = $FieldValuesArray ; // TicketfieldsValues::getFieldValueIDLIst();

							if (isset($FieldValues[$fromID])) {
								$FromValue = 'from ' . $FieldValues[$fromID];
							}
							$ToValue = 'to ' . $FieldValues[$toID];
						} else if($index == 'Agent' ) {

							$FromUser  = User::where(["UserID"=>$fromID])->select(["FirstName","LastName"])->first();
							$ToUser  = User::where(["UserID"=>$toID])->select(["FirstName","LastName"])->first();
							if(!empty($FromUser["FirstName"] . ' '. $FromUser["LastName"])) {
								$FromValue = 'from ' . $FromUser["FirstName"] . ' ' . $FromUser["LastName"];
							}
							$ToValue = 'to ' . $ToUser["FirstName"] . ' '. $ToUser["LastName"];

						} else if($index == 'Priority' ) {

							$FromPriorityValue  = TicketPriority::getPriorityStatusByID($fromID);
							$ToPriorityValue  = TicketPriority::getPriorityStatusByID($toID);

							if(!empty($FromPriorityValue)) {
								$FromValue = 'from ' . $FromPriorityValue;
							}
							$ToValue = 'to ' . $ToPriorityValue;

						} else if($index == 'Group' ) {

							$Groups = TicketGroups::getTicketGroups();

							if (isset($Groups[$fromID])) {
								$FromValue = 'from ' . $Groups[$fromID];
							}
							$ToValue = 'to ' . $Groups[$toID];

						}



						$FieldName = $index;

						$data = [
							'CompanyID' => $CompanyID,
							'TicketID' => $obj->TicketID,
							"ParentID" =>$UserID,
							"ParentType" =>TicketLog::TICKET_USER_TYPE_USER,
							"Action" =>TicketLog::TICKET_ACTION_FIELD_CHANGED,
							"ActionText" => $FieldName .' Changed  ' . $FromValue . ' ' . $ToValue ,
							'created_at' => date("Y-m-d H:i:s")
						];


                        TicketLog::insert($data);

						$TicketID = $obj->TicketID;
						$PrevStatusID = $obj->original[$index];
						$NewStatusID = $obj->attributes[$index];

						TicketSla::updateTicketSLADueDate($TicketID,$PrevStatusID,$NewStatusID);

                    }
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
	
	
	static function getTicketStatus($select=1){
		//TicketfieldsValues::WHERE
		 $row =  TicketfieldsValues::join('tblTicketfields','tblTicketfields.TicketFieldsID','=','tblTicketfieldsValues.FieldsID')->select(array('FieldValueAgent', 'ValuesID'))->where(['tblTicketfields.FieldType'=>Ticketfields::TICKET_SYSTEM_STATUS_FLD])->lists('FieldValueAgent','ValuesID');
			 if(!empty($row) && $select==1){
				$row =  array("0"=> "Select")+json_decode(json_encode($row),true);
			}else{
                 $row = json_decode(json_encode($row),true);
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
	
	static function getTicketLicense(){
		return CompanyConfiguration::get("TICKETING_SYSTEM");
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
	static function	SetEmailType($email)
	{
		$final	=	 array();
		$imap				  =		 new Imap();
		$MatchArray  		  =      $imap->findEmailAddress($email);
		
		if(count($MatchArray)>0){
			if($MatchArray['MatchType']=='Contact'){
				$final = array("ContactID"=>$MatchArray['MatchID'],"AccountID"=>0,"UserID"=>0);
			}
			
			if($MatchArray['MatchType']=='Account' || $MatchArray['MatchType']=='Lead'){
				$final = array("ContactID"=>0,"AccountID"=>$MatchArray['MatchID'],"UserID"=>0);
			}
			
			if($MatchArray['MatchType']=='User'){
				$final = array("ContactID"=>0,"AccountID"=>0,"UserID"=>$MatchArray['MatchID']);
			}
		}
		return $final;
	}
	
	static function GetConversation($ticket_number){
		$Ticketconversation = '';
		$ticketdata 	 =  TicketsTable::find($ticket_number);
		$allConversation = 	AccountEmailLog::WhereRaw("EmailParent >0")->where(['TicketID'=>$ticket_number])->orderBy('AccountEmailLogID', 'DESC')->get();
		
		if(count($allConversation)<1){
			$Ticketconversation = $ticketdata->Description."<br><hr><br>";
		}
		foreach($allConversation as $allConversationData){
			
			$Ticketconversation .= $allConversationData->Message."<br><hr><br>";	
		} 
		return $Ticketconversation;
	}
	
	static function filterEmailAddressFromName($emails){
		$final = array();
		if(!is_array($emails)){
			$emails = explode(",",$emails);
		}
		foreach($emails as $emailsData){
				
			if (strpos($emailsData, '<') !== false && strpos($emailsData, '>') !== false)
			{
				$RequesterData 	   =  explode(" <",$emailsData);
				if(isset($RequesterData[1])){
					$final[] =  substr($RequesterData[1],0,strlen($RequesterData[1])-1);
				}
			}else{
				$final[]	   =  trim($emailsData);					
			}
		}
		return implode(",",$final);
	}

   public static function CheckTicketStatus($OldStatus,$NewStatus,$id){
        if(($NewStatus == TicketsTable::getClosedTicketStatus()) && ($OldStatus!==TicketsTable::getClosedTicketStatus())) {
            $TicketEmails 	=  new TicketEmails(array("TicketID"=>$id,"TriggerType"=>"AgentClosestheTicket"));
        }

        if(($NewStatus == TicketsTable::getResolvedTicketStatus()) && ($OldStatus!==TicketsTable::getResolvedTicketStatus())) {
            $TicketEmails 	=  new TicketEmails(array("TicketID"=>$id,"TriggerType"=>"AgentSolvestheTicket"));
        }
    }

	public static function validateTicketingLicence() {

		if(self::getTicketLicense()==1) {
			return true;
		}
		return false;

	}

	static function deleteTicket($TicketID) {
		$Ticket = TicketsTable::find($TicketID);
		if(!empty($Ticket) && isset($Ticket->TicketID) && $Ticket->TicketID > 0 ) {

			self::MoveTicketToDeletedLog(["TicketID"=>$Ticket->TicketID]);


			return true;
		}
		return false;
	}

	static function setTicketFieldValue($TicketID,$Field,$Value) {
		$Ticket = TicketsTable::find($TicketID);
		if(!empty($Ticket) && isset($Ticket->TicketID) && $Ticket->TicketID > 0 ) {

			try{
				if($Ticket->update([$Field=>$Value])){
					return true;
				}
			} catch (\Exception $ex){
				Log::info("Error with setTicketValue " );
				Log::info(print_r($ex,true));
			}
		}
		return false;
	}

	static function MoveTicketToDeletedLog($opt = array()){

		$TicketsFieldsList = "`TicketID`,	`CompanyID`,	`Requester`,	`RequesterName`,	`RequesterCC`,	`RequesterBCC`,	`AccountID`,	`ContactID`,	`UserID`,	`Subject`,	`Type`,	`Status`,	`Priority`,	`Group`,	`Agent`,	`Description`,	`AttachmentPaths`,	`TicketType`,	`AccountEmailLogID`,	`Read`,	`EscalationEmail`,	`TicketSlaID`,	`RespondSlaPolicyVoilationEmailStatus`,	`ResolveSlaPolicyVoilationEmailStatus`,	`DueDate`,	`CustomDueDate`,	`AgentRepliedDate`,	`CustomerRepliedDate`,	`created_at`,	`created_by`,	`updated_at`,	`updated_by`";
		$AccountEmailLogFieldsList =  "`AccountEmailLogID`,	`CompanyID`,	`AccountID`,	`ContactID`,	`UserType`,	`UserID`,	`JobId`,	`ProcessID`,	`CreatedBy`,	`ModifiedBy`,	`created_at`,	`updated_at`,	`Emailfrom`,	`EmailTo`,	`Subject`,	`Message`,	`Cc`,	`Bcc`,	`AttachmentPaths`,	`EmailType`,	`EmailfromName`,	`MessageID`,	`EmailParent`,	`EmailID`,	`EmailCall`,	`TicketID`";

		if(isset($opt["TicketIDs"]) ) {


				$q1 = "INSERT INTO  tblTicketsDeletedLog (".$TicketsFieldsList.")  SELECT ".$TicketsFieldsList." FROM tblTickets WHERE TicketID IN (" . $opt["TicketIDs"] . ')';
				$q2 = "INSERT INTO AccountEmailLogDeletedLog (".$AccountEmailLogFieldsList.")  SELECT ".$AccountEmailLogFieldsList."  FROM AccountEmailLog WHERE TicketID IN (" . $opt["TicketIDs"] . ')';

				DB::insert($q1);
				DB::insert($q2);

				// Delete Ticket Logs
				TicketLog::whereIn('TicketID', explode(',',$opt["TicketIDs"]))->delete();
				//TicketDashboardTimeline::whereIn('TicketID', explode(',',$opt["TicketIDs"]))->delete();
				TicketsDetails::whereIn('TicketID', explode(',',$opt["TicketIDs"]))->delete();
				TicketsTable::whereIn('TicketID', explode(',',$opt["TicketIDs"]))->delete();
				AccountEmailLog::whereIn('TicketID', explode(',',$opt["TicketIDs"]))->delete();



		} else if(isset($opt["TicketID"]) && is_numeric($opt["TicketID"]) ) {

			DB::insert("INSERT INTO tblTicketsDeletedLog (".$TicketsFieldsList.")  SELECT ".$TicketsFieldsList." FROM tblTickets WHERE TicketID = " . $opt["TicketID"]);
			DB::insert("INSERT INTO AccountEmailLogDeletedLog (".$AccountEmailLogFieldsList.")  SELECT ".$AccountEmailLogFieldsList."  FROM AccountEmailLog WHERE TicketID = " . $opt["TicketID"]);

			// Delete Ticket Logs
			TicketLog::where('TicketID', $opt["TicketID"])->delete();
			//TicketDashboardTimeline::where('TicketID', $opt["TicketID"])->delete();
			TicketsDetails::where('TicketID', $opt["TicketID"])->delete();
			TicketsTable::where('TicketID', $opt["TicketID"])->delete();
			AccountEmailLog::where('TicketID', $opt["TicketID"])->delete();


		}

		Log::info( "Ticket Deleted" );
		Log::info( print_r($opt,true) );


	}

	/** Delete Group and Related Tickets
	 * @param $CompanyID
	 * @param $GroupID
	 */
	static function GroupDelete( $CompanyID, $GroupID ) {

		Log::info( "DELETING COMPLETE GROUP AND RELATED TICKETS AND LOGS" );

		$DeleteTicketGroup  =      	"call prc_DeleteTicketGroup (".$CompanyID.",".$GroupID.")";
		DB::query($DeleteTicketGroup);

		Log::info("
					-- 1
					-- delete tblTicketLog
					-- 2
					-- delete tblTicketDashboardTimeline
					-- 3
					-- delete tblTicketsDetails
					-- 4
					-- delete tblTicketsDeletedLog
					-- 5
					-- delete AccountEmailLogDeletedLog
					-- 6
					-- delete tblTicketsDetails
					-- 7
					-- delete tblTickets
					-- 8
					-- delete tblTicketGroups
		");

	}


}