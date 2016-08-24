<?php 

namespace App;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Api\Model\Company;

class MandrilIntegration{ 

	public function __construct(){
	 } 
	
	static function SendMail($view,$data,$config,$companyID)
	{
		$result = Company::select('CompanyName','EmailFrom')->where("CompanyID", '=', $companyID)->first();
		$config_array =(object)array(
			"SMTPServer"=>$config->MandrilSmtpServer,
			"Port"=>$config->MandrilPort,
			"EmailFrom"=>$result->EmailFrom,
			"CompanyName"=>$result->CompanyName,
			"IsSSL"=>$config->MandrilSSL,
			"SMTPUsername"=>$config->MandrilUserName,
			"SMTPPassword"=>$config->MandrilPassword
		);
		
		return PHPMAILERIntegtration::SendMail($view,$data,$config_array,$companyID);
	}
}
?>