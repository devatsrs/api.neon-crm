<?php
namespace Api\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Messages extends \Eloquent {

    protected $fillable 	= 	['PID'];
    protected $table 		= 	"tblMessages";
    protected $primaryKey 	= 	"MsgID";
    public    $timestamps 	= 	false; // no created_at and updated_at
	
	const  UserTypeAccount	= 	0;
    const  UserTypeContact	=   1;
	
	const  Sent 			= 	0;
    const  Received			=   1;
    const  Draft 			= 	2;
	
	public static function GetAllSystemEmailsWithName($lead=1)
	{
		 $array 		 =  [];
		
		 if($lead==0)
		 {
			$AccountSearch   =  DB::table('tblAccount')->where(['AccountType'=>1])->whereRaw('Email !=""')->get(array("Email","BillingEmail","AccountName"));
		 }
		 else
		 {
			$AccountSearch   =  DB::table('tblAccount')->whereRaw('Email !=""')->get(array("Email","BillingEmail"));
		 }
		 
		 $ContactSearch 	 =  DB::table('tblContact')->get(array("Email","FirstName","LastName"));	
		
		if(count($AccountSearch)>0){
				foreach($AccountSearch as $AccountData){
					//if($AccountData->Email!='' && !in_array($AccountData->Email,$array))
					if($AccountData->Email!='')
					{
						if(!is_array($AccountData->Email))
						{				  
						  $email_addresses = explode(",",$AccountData->Email);				
						}
						else
						{
						  $email_addresses = $emails;
						}
						if(count($email_addresses)>0)
						{
							foreach($email_addresses as $email_addresses_data)
							{
								$txt = $AccountData->AccountName." <".$email_addresses_data.">";
								if(!in_array($txt,$array))
								{
									$array[] =  $txt;	
								}
							}
						}
						
					}			
					
					if($AccountData->BillingEmail!='')
					{
						if(!is_array($AccountData->BillingEmail))
						{				  
						  $email_addresses = explode(",",$AccountData->BillingEmail);				
						}
						else
						{
						  $email_addresses = $emails;
						}
						if(count($email_addresses)>0)
						{
							foreach($email_addresses as $email_addresses_data)
							{
								$txt = $AccountData->AccountName." <".$email_addresses_data.">";
								if(!in_array($txt,$array))
								{
									//$array[] =  $email_addresses_data;	
									$array[] =  $txt;	
								}
							}
						}
						
					}
				}
		}
		
		if(count($ContactSearch)>0){
				foreach($ContactSearch as $ContactData){
					$txt =  $ContactData->FirstName.' '.$ContactData->LastName." <".$ContactData->Email.">";
					if($ContactData->Email!=''  && !in_array($txt,$array))
					{
						$array[] =  $txt;
						//$array[] =  $ContactData->Email;
					}
				}
		}		
		//return  array_filter(array_unique($array));
		return $array;
    }
	
	
	public static function GetAllSystemEmails($lead=1)
	{
		 $array 		 =  [];
		
		 if($lead==0)
		 {
			$AccountSearch   =  DB::table('tblAccount')->where(['AccountType'=>1])->whereRaw('Email !=""')->get(array("Email","BillingEmail"));
		 }
		 else
		 {
			$AccountSearch   =  DB::table('tblAccount')->whereRaw('Email !=""')->get(array("Email","BillingEmail"));
		 }
		 
		 $ContactSearch 	 =  DB::table('tblContact')->get(array("Email"));	
		
		if(count($AccountSearch)>0){
				foreach($AccountSearch as $AccountData){
					//if($AccountData->Email!='' && !in_array($AccountData->Email,$array))
					if($AccountData->Email!='')
					{
						if(!is_array($AccountData->Email))
						{				  
						  $email_addresses = explode(",",$AccountData->Email);				
						}
						else
						{
						  $email_addresses = $emails;
						}
						if(count($email_addresses)>0)
						{
							foreach($email_addresses as $email_addresses_data)
							{
								if(!in_array($email_addresses_data,$array))
								{
									$array[] =  $email_addresses_data;	
								}
							}
						}
						
					}			
					
					if($AccountData->BillingEmail!='')
					{
						if(!is_array($AccountData->BillingEmail))
						{				  
						  $email_addresses = explode(",",$AccountData->BillingEmail);				
						}
						else
						{
						  $email_addresses = $emails;
						}
						if(count($email_addresses)>0)
						{
							foreach($email_addresses as $email_addresses_data)
							{
								if(!in_array($email_addresses_data,$array))
								{
									$array[] =  $email_addresses_data;	
								}
							}
						}
						
					}
				}
		}
		
		if(count($ContactSearch)>0){
				foreach($ContactSearch as $ContactData){
					if($ContactData->Email!=''  && !in_array($ContactData->Email,$array))
					{
						$array[] =  $ContactData->Email;
					}
				}
		}
		
		//return  array_filter(array_unique($array));
		return $array;
    }
}