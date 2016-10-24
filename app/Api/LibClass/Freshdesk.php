<?php 
namespace App;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Curl\Curl;
use \Exception;
use Api\Model\IntegrationConfiguration;

class Freshdesk{

protected $domain;
protected $email;
protected $password;
protected $key;
protected $url;
protected $per_page;
protected $page;
protected $status;
protected $priority;
protected $groups;
protected $Agent;



	 public function __construct($data = array()){
		 foreach($data as $key => $value){
			 $this->$key = $value;
		 }		 		 
		 set_exception_handler(array($this, 'handleException'));	
		 
		 $this->MakeUrl();
		 if($this->CheckConnection()){
			 $this->GetFields();
		 }
	 }
	 
	 protected function MakeUrl(){
		 if(empty($this->domain)){
			   throw new Exception("Mention the domain");
		 }
	 	$this->url = 'https://'.$this->domain.'.freshdesk.com';
	 }
	 
	public function CheckConnection(){
		$this->MakeUrl();
		$this->url = $this->url."/api/v2/groups";
		$result =  $this->Call(); 
		if(isset($result['StatusCode']) && $result['StatusCode']==200){
			return true;
		}else{
			return false;
		}
		
	}
	 
	 public function GetContacts($filter = array()){
		$this->MakeUrl();
	 	$concat_url 	=    ''; 
		
		foreach($filter as $key => $value){
			$concat_url .=$key.'='.$value."&";
		}
		if(!empty($concat_url)){
			$concat_url = "?".$concat_url;
		}
		$this->url  	= 	$this->url."/api/v2/contacts".$concat_url;	
		return $this->Call();
	 }

	 public function GetTickets($filter = array()){
		$this->MakeUrl();
		$concat_url 	=    ''; 
		foreach($filter as $key => $value){			
			$concat_url .=$key.'='.$value."&";
		}
		if(!empty($concat_url)){
			$concat_url = "?".$concat_url;
		}
		$this->url  	= 	$this->url."/api/v2/tickets".$concat_url; 
		/*if(!empty($this->per_page)){
			$this->url  	= 	$this->url."/api/v2/tickets".$concat_url;	
			$FullResult		=	$this->Call();
			$this->url  	= 	$this->url."/api/v2/tickets".$concat_url."&per_page=".$this->per_page."&page=".$this->page;
			$result			=	$this->Call();				
		}else
		{
			$this->url  	= 	$this->url."/api/v2/tickets".$concat_url;	
			$FullResult 	= 	$result 		=	$this->Call();
		}
					
		return $this->MakeResult(array("total"=>count($FullResult),"result"=>$result));*/
		$result =  $this->Call();
		if(isset($result['StatusCode']) && $result['StatusCode'] == 200 && count($result['data'])>0)
		{
			$FreshDeskDbData =  IntegrationConfiguration::GetIntegrationDataBySlug('freshdesk'); //db settings
			$FreshdeskData   = 	isset($FreshDeskDbData->Settings)?json_decode($FreshDeskDbData->Settings):"";
			
			if(isset($FreshdeskData->FreshdeskGroup) && $FreshdeskData->FreshdeskGroup!='')
			{
				$Filter_Groups = explode(",",$FreshdeskData->FreshdeskGroup);
			}
			
			$return_tickets = array();
			
			foreach($result['data'] as $GetTickets_data)
			{   
				if(count($Filter_Groups)>0){		//group filter
					if(!in_array($this->SetGroup($GetTickets_data->group_id),$Filter_Groups)){
						continue;
					}							
				}
				
				$return_tickets[] = $GetTickets_data;		
			}
			
			return array("StatusCode"=>$result['StatusCode'],"data"=>$return_tickets,"description"=>"","errors"=>"");
		}
		else
		{
			return $result;
		}
	 }
	 
	 public function GetTicketConversations($id){
		$this->MakeUrl();
		$this->url  	= 	$this->url."/api/v2/tickets/".$id."/conversations";		
		return $this->Call();
	 }
	 
	 public function GetFields(){
		$this->MakeUrl();
		$this->url  	= 	$this->url."/api/v2/ticket_fields/";		
		$data			=	$this->Call(); 
		if(isset($data['StatusCode']) && $data['StatusCode']==200 && count($data['data'])>0){
			foreach($data['data'] as $FieldsData){ 
			$array = json_decode(json_encode($FieldsData), True);
				if($FieldsData->description == 'Ticket status'){				
					$StatusArray = json_decode(json_encode($FieldsData->choices), True);
					foreach($StatusArray as $key => $StatusArrayData){
						$this->status[$key]	=	$StatusArrayData[0];		
					}					
				}				
				if($FieldsData->description == 'Ticket priority'){				
					$priorityArray = json_decode(json_encode($FieldsData->choices), True);
					foreach($priorityArray as $key => $priorityArrayData){
						$this->priority[$priorityArrayData]	=	$key;		
					}					
				}
				if($FieldsData->description == 'Ticket group'){
					$groupArray = json_decode(json_encode($FieldsData->choices), True);
					foreach($groupArray as $key => $groupArrayData){
						$this->groups[$groupArrayData]	=	$key;		
					}					
				}
				
				if($FieldsData->description == 'Agent'){
					$AgentArray = json_decode(json_encode($FieldsData->choices), True);
					foreach($AgentArray as $key => $AgentArrayData){
						$this->Agent[$AgentArrayData]	=	$key;		
					}					
				}
			}
		}	
	}
	 
	 
	 public function Call(){
		try {  
				$array_return  	= 	array("StatusCode"=>00);
				$header[] 	   	= 	"Content-type: application/json";
				$ch 			= 	curl_init ($this->url);   
				curl_setopt ($ch, CURLOPT_POST, false);
				curl_setopt($ch, CURLOPT_USERPWD, "$this->email:$this->password");
				curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
				curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
				curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
				curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
				curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_1);
				$returndata 	= 	curl_exec($ch); 
				$httpCode 		= 	(int) curl_getinfo($ch,\CURLINFO_HTTP_CODE); 
			    $json_data 		= 	json_decode($returndata);
				
				if($httpCode == 200){  
					return 	$array_return	=	array("StatusCode"=>$httpCode,"data"=>$json_data,"description"=>"","errors"=>"","url"=>$this->url);
				}else{
					return $array_return  = array("StatusCode"=>$httpCode,"description"=>isset($json_data->description)?$json_data->description:'',"errors"=>isset($json_data->errors)?$json_data->errors:'',"data"=>"","url"=>$this->url);
					  //throw new Exception( sprintf('%s returned unexpected HTTP code (%d), repsonse: %s',$this->url,$httpCode,$returndata));                
			    }
		} catch (Exception $e) {
  			return $e->getMessage(); 
		}
        
	 }
	 
	 function handleException(RuntimeException $e){
		 echo $e->getMessage();		
	}
	
	function MakeResult($data =array()){		
	}
	
	public function SetPriority($id){
		if($id){
			return $this->priority[$id];
		}else{
			return '';
		}
	}
	
	public function SetStatus($id){
		if($id){
			return $this->status[$id];
		}else{
			return '';
		}
	}
	
	public function SetGroup($id){
		if($id){
			return $this->groups[$id];
		}else{
			return '';
		}
	}
}
?>