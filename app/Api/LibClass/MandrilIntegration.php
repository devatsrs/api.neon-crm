<?php 

namespace App;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Api\Model\Company;

class MandrilIntegration{ 

	public function __construct(){
	 } 
	
	static function SendMail($view,$data,$config,$companyID,$body)
	{ 
		$mandril = json_decode($config['Settings']);
		$result  = Company::select('CompanyName','EmailFrom')->where("CompanyID", '=', $companyID)->first();
		$config_array =(object)array(
			"SMTPServer"=>$mandril->MandrilSmtpServer,
			"Port"=>$mandril->MandrilPort,
			"EmailFrom"=>$result->EmailFrom,
			"CompanyName"=>$result->CompanyName,
			"IsSSL"=>$mandril->MandrilSSL,
			"SMTPUsername"=>$mandril->MandrilUserName,
			"SMTPPassword"=>$mandril->MandrilPassword
		);
		return PHPMAILERIntegtration::SendMail($view,$data,$config_array,$companyID,$body);
	}
}
?>