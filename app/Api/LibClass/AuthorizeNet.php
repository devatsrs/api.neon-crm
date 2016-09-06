<?php
/**
 * Created by PhpStorm.
 * User: CodeDesk
 * Date: 8/22/2015
 * Time: 12:57 PM
 */
/*$isSandbox = getenv('AUTHORIZENET_SANDBOX');
if($isSandbox == 1){
    define("AUTHORIZENET_SANDBOX", true);
}else{
    define("AUTHORIZENET_SANDBOX", false);
}
define("AUTHORIZENET_API_LOGIN_ID", getenv('AUTHORIZENET_API_LOGIN_ID'));
define("AUTHORIZENET_TRANSACTION_KEY", getenv('AUTHORIZENET_TRANSACTION_KEY'));*/

class AuthorizeNet {

    public $request;

    function __Construct(){
		
		//////
		$AuthorizeData 						= 	\App\SiteIntegration::CheckIntegrationConfiguration(true,\App\SiteIntegration::$AuthorizeSlug);
		if(count($AuthorizeData)>0){					
			$AUTHORIZENET_API_LOGIN_ID  	= 	isset($AuthorizeData->AuthorizeLoginID)?$AuthorizeData->AuthorizeLoginID:'';		
			$AUTHORIZENET_TRANSACTION_KEY  	= 	isset($AuthorizeData->AuthorizeTransactionKey)?$AuthorizeData->AuthorizeTransactionKey:'';
			$isSandbox						=	isset($AuthorizeDbData->AuthorizeTestAccount)?$AuthorizeDbData->AuthorizeTestAccount:'';
			
			define("AUTHORIZENET_API_LOGIN_ID", $AUTHORIZENET_API_LOGIN_ID);
			define("AUTHORIZENET_TRANSACTION_KEY", $AUTHORIZENET_TRANSACTION_KEY);
			
			if($isSandbox == 1){
				define("AUTHORIZENET_SANDBOX", true);
			}else{
				define("AUTHORIZENET_SANDBOX", false);
			}		
		}
		/////////
        $this->request = new AuthorizeNetCIM();
    }

    function CreateProfile($data){
        try{
            $customerProfile = new AuthorizeNetCustomer();
            $customerProfile->description = $data["description"];
            $customerProfile->merchantCustomerId = $data["CustomerId"];
            $customerProfile->email = $data["email"];
            $response = $this->request->createCustomerProfile($customerProfile);
            if (($response != null) && ($response->xml->messages->resultCode == "Ok") ) {
                $result["status"] = "success";
                $result["message"] = "Customer profile created on authorize.net";
                $result["ID"] = $response->xml->customerProfileId;
            }else{
                $result["status"] = "failed";
                $result["message"] = $response->xml->messages->message->text;
            }
            return $result;
        }catch(Exception $ex){
            $ex->getMessage();
            $result["status"] = "failed";
            $result["message"] = $ex->getMessage();
            return $result;
        }
    }

    function UpdateProfile($ProfileID,$data){
        try{
            $customerProfile = new AuthorizeNetCustomer();
            $customerProfile->description = $data["description"];
            $customerProfile->merchantCustomerId = $data["CustomerId"];
            $customerProfile->email = $data["email"];
            $response = $this->request->createCustomerProfile($customerProfile);
            if (($response != null) && ($response->xml->messages->resultCode == "Ok") ) {
                $result["status"] = "success";
                $result["message"] = "Customer profile created on authorize.net";
                $result["ID"] = $response->xml->customerProfileId;
            }else{
                $result["status"] = "failed";
                $result["message"] = $response->xml->messages->message->text;
            }
            return $result;
        }catch(Exception $ex){
            $ex->getMessage();
            $result["status"] = "failed";
            $result["message"] = $ex->getMessage();
            return $result;
        }
    }

    function deleteProfile($ProfileID){
        try{
            $response = $this->request->deleteCustomerProfile($ProfileID);
            if (($response != null) && ($response->xml->messages->resultCode == "Ok") ) {
                $result["status"] = "success";
                $result["message"] = "Customer profile deleted on authorize.net";
                $result["ID"] = $response->xml->customerProfileId;
            }else{
                $result["status"] = "failed";
                $result["message"] = $response->xml->messages->message->text;
            }
            return $result;
        }catch(Exception $ex){
            $ex->getMessage();
            $result["status"] = "failed";
            $result["message"] = $ex->getMessage();
            return $result;
        }
    }

    function CreatePaymentProfile($customerProfileId,$data){
        try{
            $data["ExpirationDate"] = $data["ExpirationYear"]."-".$data["ExpirationMonth"];
            $paymentProfile = new AuthorizeNetPaymentProfile;
            $paymentProfile->customerType = "individual";
            $paymentProfile->payment->creditCard->cardNumber = $data["CardNumber"];
            $paymentProfile->payment->creditCard->expirationDate = $data["ExpirationDate"];
            $response = $this->request->createCustomerPaymentProfile($customerProfileId, $paymentProfile);
            if (($response != null) && ($response->xml->messages->resultCode == "Ok") ) {
                $result["status"] = "success";
                $result["message"] = "Payment profile created on authorize.net";
                $result["ID"] = (int) $response->xml->customerPaymentProfileId;
            }
            else {
                $result["status"] = "failed";
                $result["message"] = $response->xml->messages->message->text;
            }
            return $result;
        }catch(Exception $ex){
            $ex->getMessage();
            $result["status"] = "failed";
            $result["message"] = $ex->getMessage();
            return $result;
        }
    }

    function UpdatePaymentProfile($customerProfileId,$paymentProfileId,$data){
        try{
            $data["ExpirationDate"] = $data["ExpirationYear"]."-".$data["ExpirationMonth"];
            $paymentProfile = new AuthorizeNetPaymentProfile;
            $paymentProfile->customerType = "individual";
            $paymentProfile->payment->creditCard->cardNumber = $data["CardNumber"];
            $paymentProfile->payment->creditCard->expirationDate = $data["ExpirationDate"];
            $response = $this->request->updateCustomerPaymentProfile($customerProfileId,$paymentProfileId,$paymentProfile);
            if (($response != null) && ($response->xml->messages->resultCode == "Ok") ) {
                $result["status"] = "success";
                $result["message"] = "Payment profile created on authorize.net";
                $result["ID"] = (int) $response->xml->customerPaymentProfileId;
            }
            else {
                $result["status"] = "failed";
                $result["message"] = $response->xml->messages->message->text;
            }
            return $result;
        }catch(Exception $ex){
            $ex->getMessage();
            $result["status"] = "failed";
            $result["message"] = $ex->getMessage();
            return $result;
        }
    }

    function deletePaymentProfile($customerProfileId,$paymentProfileId){
        try{
            $response = $this->request->deleteCustomerPaymentProfile($customerProfileId,$paymentProfileId);
            if (($response != null) && ($response->xml->messages->resultCode == "Ok") ) {
                $result["status"] = "success";
                $result["message"] = "Payment profile deleted on authorize.net";
                $result["code"] = $response->xml->messages->message->code;
                $result["ID"] = (int) $response->xml->customerPaymentProfileId;
            }
            else {
                $result["status"] = "failed";
                $result["code"] = $response->xml->messages->message->code;
                $result["message"] = $response->xml->messages->message->text;
            }
            return $result;
        }catch(Exception $ex){
            $ex->getMessage();
            $result["status"] = "failed";
            $result["message"] = $ex->getMessage();
            return $result;
        }
    }

    function CreatShippingAddress($customerProfileId,$data){
        try{
            $address = new AuthorizeNetAddress;
            $address->firstName = $data['firstName'];
            $address->lastName = $data['lastName'];
            $address->address = $data['address'];
            $address->city = $data['city'];
            $address->state = $data['state'];
            $address->zip = $data['zip'];
            $address->country = $data['country'];
            $address->phoneNumber = $data['phoneNumber'];
            $response = $this->request->createCustomerShippingAddress($customerProfileId, $address);
            if (($response != null) && ($response->xml->messages->resultCode == "Ok") ) {
                $result["status"] = "success";
                $result["message"] = "Shipping Address created on authorize.net";
                $result["ID"] = (int) $response->xml->customerAddressId;
            }
            else {
                $result["status"] = "failed";
                $result["message"] = $response->xml->messages->message->text;
            }
            return $result;
        }catch(Exception $ex){
            $ex->getMessage();
            $result["status"] = "failed";
            $result["message"] = $ex->getMessage();
            return $result;
        }
    }

    function UpdateShippingAddress($customerProfileId,$customerShippingAddressId,$data){
        try{
            $address = new AuthorizeNetAddress;
            $address->firstName = $data['firstName'];
            $address->lastName = $data['lastName'];
            $address->address = $data['address'];
            $address->city = $data['city'];
            $address->state = $data['state'];
            $address->zip = $data['zip'];
            $address->country = $data['country'];
            $address->phoneNumber = $data['phoneNumber'];
            $response = $this->request->updateCustomerShippingAddress($customerProfileId,$customerShippingAddressId, $address);
            if (($response != null) && ($response->xml->messages->resultCode == "Ok") ) {
                $result["status"] = "success";
                $result["message"] = "Shipping Address updated on authorize.net";
                $result["ID"] = (int) $response->xml->customerAddressId;
            }
            else {
                $result["status"] = "failed";
                $result["message"] = $response->xml->messages->message->text;
            }
            return $result;
        }catch(Exception $ex){
            $ex->getMessage();
            $result["status"] = "failed";
            $result["message"] = $ex->getMessage();
            return $result;
        }
    }

    function deleteShippingAddress($customerProfileId,$customerShippingAddressId){
        try{
            $response = $this->request->deleteCustomerShippingAddress($customerProfileId,$customerShippingAddressId);
            if (($response != null) && ($response->xml->messages->resultCode == "Ok") ) {
                $result["status"] = "success";
                $result["message"] = "Shipping Address deleted on authorize.net";
                $result["ID"] = (int) $response->xml->customerAddressId;
            }
            else {
                $result["status"] = "failed";
                $result["message"] = $response->xml->messages->message->text;
            }
            return $result;
        }catch(Exception $ex){
            $ex->getMessage();
            $result["status"] = "failed";
            $result["message"] = $ex->getMessage();
            return $result;
        }
    }
	public static function addAuthorizeNetTransaction($amount, $options)
    {
        $transaction = new \AuthorizeNetTransaction();
        $request = new \AuthorizeNetCIM();
        $transaction->amount = $amount;
        $transaction->customerProfileId = $options->ProfileID;
        $transaction->customerPaymentProfileId = $options->PaymentProfileID;

        $response = $request->createCustomerProfileTransaction("AuthCapture", $transaction);
		$transactionResponse = $response->getTransactionResponse();
		$transactionResponse->real_response = $response;
		
        return $transactionResponse;
    }
    public static function pay_invoice($data){
        $sale = new AuthorizeNetAIM;
        $sale->setFields(
            array(
                'amount' => $data['GrandTotal'],
                'card_num' => $data['CardNumber'],
                'exp_date' => $data['ExpirationMonth'].'/'.$data['ExpirationYear'],
                'card_code' => $data['CVVNumber'],
                //'first_name' => $data['FirstName'],
                //'last_name' => $data['LastName'],
                //'address' => $data['Address'],
                //'city' => $data['City'],
                //'state' => $data['State'],
                //'country' => $data['Country'],
                //'zip' => $data['Zip'],
                //'email' => $data['Email'],

            )
        );
        $response = $sale->authorizeAndCapture();
        return $response;
    }
}