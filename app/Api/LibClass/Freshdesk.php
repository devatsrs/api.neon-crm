<?php 
namespace App;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Curl\Curl;
use \Exception;

class Freshdesk{

protected $domain;
protected $email;
protected $password;
protected $key;
protected $url;
protected $per_page;
protected $page;


	 public function __construct($data = array()){
		 foreach($data as $key => $value){
			 $this->$key = $value;
		 }		 		 
		 set_exception_handler(array($this, 'handleException'));	
		 $this->MakeUrl();
	 }
	 
	 protected function MakeUrl(){
		 if(empty($this->domain)){
			   throw new RuntimeException("Mention the domain");
		 }
	 	$this->url = 'https://'.$this->domain.'.freshdesk.com';
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
		return $this->Call();
	 }
	 
	 public function GetTicketConversations($id){
		$this->MakeUrl();
		$this->url  	= 	$this->url."/api/v2/tickets/".$id."/conversations";		
		return $this->Call();
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
				$returndata 	= 	curl_exec($ch); 
				$httpCode 		= 	(int) curl_getinfo($ch,\CURLINFO_HTTP_CODE); 
			    $json_data 		= 	json_decode($returndata);
				
				if ($httpCode < 200 || $httpCode > 299)
			 	{ 
					$array_return  = array("StatusCode"=>$httpCode,"description"=>$json_data['description'],"errors"=>$json_data['errors'],"data"=>"");
					  //throw new Exception( sprintf('%s returned unexpected HTTP code (%d), repsonse: %s',$this->url,$httpCode,$returndata));                
			    }
				if($httpCode == 400){ 
					$array_return  = array("StatusCode"=>$httpCode,"description"=>$json_data['description'],"errors"=>$json_data['errors'],"data"=>"");
					
				}
				if($httpCode == 200){  Log::info($json_data);
					$array_return	=	array("StatusCode"=>$httpCode,"data"=>$json_data,"description"=>"","errors"=>"");
				}
				
		} catch (Exception $e) {
  			return $e->getMessage(); 
		}
        return $array_return;
	 }
	 
	 function handleException(RuntimeException $e){
		 echo $e->getMessage();		
	}
	
	function MakeResult($data =array()){
		
	}
}
?>