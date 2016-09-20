<?php
namespace Api\Model;
use Api\Model\Account;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;


class AccountPaymentProfile extends Model{

    protected $fillable = [];
    protected $guarded = array('AccountPaymentProfileID');
    protected $table = 'tblAccountPaymentProfile';
    protected $primaryKey = "AccountPaymentProfileID";

    public static function getActiveProfile($AccountID)
    {
        $AccountPaymentProfile = array();
        if (Account::where(array('AccountID' => $AccountID))->pluck('Autopay') == 1) {
            $AccountPaymentProfile = AccountPaymentProfile::where(array('AccountID' => $AccountID, 'Status' => 1, 'Blocked' => 0, 'isDefault' => 1))->first();
        }
        return $AccountPaymentProfile;
    }

    public static function setProfileBlock($AccountPaymentProfileID)
    {
        AccountPaymentProfile::where(array('AccountPaymentProfileID' => $AccountPaymentProfileID))->update(array('Blocked' => 1));
    }

    public static function getProfile($AccountPaymentProfileID)
    {
        $AccountPaymentProfile = AccountPaymentProfile::where(array('AccountPaymentProfileID' => $AccountPaymentProfileID))->first();
        return $AccountPaymentProfile;
    }

    public static function createProfile($CompanyID, $CustomerID)
    {
        $data = Input::all();
		$isAuthorizedNet  = 	\App\SiteIntegration::CheckIntegrationConfiguration(false,\App\SiteIntegration::$AuthorizeSlug); 
		if(!$isAuthorizedNet){
			return Response::json(array("status" => "failed", "message" => "Payment Method Not Integrated"));
		}
		
        $AuthorizeNet = new AuthorizeNet();
        $ProfileID = "";
        $ShippingProfileID = "";
        $first = 0;
        $rules = array(
            'CardNumber' => 'required|digits_between:14,19',
            'ExpirationMonth' => 'required',
            'ExpirationYear' => 'required',
            'NameOnCard' => 'required',
            'CVVNumber' => 'required',
            //'Title' => 'required|unique:tblAutorizeCardDetail,NULL,CreditCardID,CompanyID,'.$CompanyID
        );

        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            return json_validator_response($validator);
        }
        if (date("Y") == $data['ExpirationYear'] && date("m") > $data['ExpirationMonth']) {
            return Response::json(array("status" => "failed", "message" => "Month must be after " . date("F")));
        }
        $card = CreditCard::validCreditCard($data['CardNumber']);
        if ($card['valid'] == 0) {
            return Response::json(array("status" => "failed", "message" => "Please enter valid card number"));
        }
        $PaymentGatewayID = PaymentGateway::where(['Title' => PaymentGateway::$gateways['Authorize']])
            ->where(['CompanyID' => $CompanyID])
            ->pluck('PaymentGatewayID');
        $PaymentProfile = AccountPaymentProfile::where(['AccountID' => $CustomerID])
            ->where(['CompanyID' => $CompanyID])
            ->where(['PaymentGatewayID' => $PaymentGatewayID])
            ->first();
        if (!empty($PaymentProfile)) {
            $options = json_decode($PaymentProfile->Options);
            $ProfileID = $options->ProfileID;
            $ShippingProfileID = $options->ShippingProfileID;
        }
        $account = Account::where(array('AccountID' => $CustomerID))->first();
        if (empty($ProfileID)) {
            $profile = array('CustomerId' => $CustomerID, 'email' => $account->BillingEmail, 'description' => $account->AccountName);
            $result = $AuthorizeNet->CreateProfile($profile);
            if ($result["status"] == "success") {
                $ProfileID = $result["ID"];
                $ProfileID = json_decode(json_encode($ProfileID), true)[0];
                $shipping = array('firstName' => $account->FirstName,
                    'lastName' => $account->LastName,
                    'address' => $account->Address1,
                    'city' => $account->City,
                    'state' => $account->state,
                    'zip' => $account->PostCode,
                    'country' => $account->Country,
                    'phoneNumber' => $account->Mobile);
                $result = $AuthorizeNet->CreatShippingAddress($ProfileID, $shipping);
                $ShippingProfileID = $result["ID"];
                $first = 1;
            } else {
                return Response::json(array("status" => "failed", "message" => (array)$result["message"]));
            }
        }
        $title = $data['Title'];
        $result = $AuthorizeNet->CreatePaymentProfile($ProfileID, $data);
        if ($result["status"] == "success") {
            $PaymentProfileID = $result["ID"];
            /**  @TODO save this field NameOnCard and CCV */
            $option = array(
                'ProfileID' => $ProfileID,
                'ShippingProfileID' => $ShippingProfileID,
                'PaymentProfileID' => $PaymentProfileID
            );
            $CardDetail = array('Title' => $title,
                'Options' => json_encode($option),
                'Status' => 1,
                'isDefault' => $first,
                'created_by' => Customer::get_accountName(),
                'CompanyID' => $CompanyID,
                'AccountID' => $CustomerID,
                'PaymentGatewayID' => $PaymentGatewayID);
            if (AccountPaymentProfile::create($CardDetail)) {
                return Response::json(array("status" => "success", "message" => "Payment Method Profile Successfully Created"));
            } else {
                return Response::json(array("status" => "failed", "message" => "Problem Saving Payment Method Profile."));
            }
        } else {
            return Response::json(array("status" => "failed", "message" => (array)$result["message"]));
        }
    }

    public static function paynow($CompanyID, $AccountID, $Invoiceids, $CreatedBy, $AccountPaymentProfileID)
    {
        $account = Account::find($AccountID);
        $outstanginamounttotal = Account::getOutstandingAmount($CompanyID,$account->AccountID,$account->RoundChargesAmount);
        $outstanginamount = Account::getOutstandingInvoiceAmount($CompanyID,$account->AccountID,$Invoiceids, $account->RoundChargesAmount);
        if ($outstanginamount > 0 && $outstanginamounttotal > 0 ) {
            $CustomerProfile = AccountPaymentProfile::getProfile($AccountPaymentProfileID);
            if (!empty($CustomerProfile)) {
                $PaymentGateway = PaymentGateway::getName($CustomerProfile->PaymentGatewayID);
                $AccountPaymentProfileID = $CustomerProfile->AccountPaymentProfileID;
                $options = json_decode($CustomerProfile->Options);
                $transactionResponse = PaymentGateway::addTransaction($PaymentGateway, $outstanginamount, $options, $account, $AccountPaymentProfileID,$CreatedBy);
                /**  Get All UnPaid  Invoice */
                $unPaidInvoices = DB::connection('sqlsrv2')->select('call prc_getPaymentPendingInvoice (' . $CompanyID . ',' . $account->AccountID.',0)');
                if (isset($transactionResponse['response_code']) && $transactionResponse['response_code'] == 1) {
                    foreach ($unPaidInvoices as $Invoiceid) {
                        /**  Update Invoice as Paid */
                        if (in_array($Invoiceid->InvoiceID, explode(',', $Invoiceids))) {
                            $Invoice = Invoice::find($Invoiceid->InvoiceID);
                            $paymentdata = array();
                            $paymentdata['CompanyID'] = $Invoice->CompanyID;
                            $paymentdata['AccountID'] = $Invoice->AccountID;
                            $paymentdata['InvoiceNo'] = Invoice::getFullInvoiceNumber($Invoice,$account);
                            $paymentdata['PaymentDate'] = date('Y-m-d');
                            $paymentdata['PaymentMethod'] = $transactionResponse['transaction_payment_method'];
                            $paymentdata['CurrencyID'] = $account->CurrencyId;
                            $paymentdata['PaymentType'] = 'Payment In';
                            $paymentdata['Notes'] = $transactionResponse['transaction_notes'];
                            $paymentdata['Amount'] = floatval($Invoiceid->RemaingAmount);
                            $paymentdata['Status'] = 'Approved';
                            $paymentdata['created_at'] = date('Y-m-d H:i:s');
                            $paymentdata['updated_at'] = date('Y-m-d H:i:s');
                            $paymentdata['CreatedBy'] = $CreatedBy;
                            $paymentdata['ModifyBy'] = $CreatedBy;
                            Payment::insert($paymentdata);
                            $transactiondata = array();
                            $transactiondata['CompanyID'] = $account->CompanyId;
                            $transactiondata['AccountID'] = $account->AccountID;
                            $transactiondata['InvoiceID'] = $Invoice->InvoiceID;
                            $transactiondata['Transaction'] = $transactionResponse['transaction_id'];
                            $transactiondata['Notes'] = $transactionResponse['transaction_notes'];
                            $transactiondata['Amount'] = floatval($Invoiceid->RemaingAmount);
                            $transactiondata['Status'] = TransactionLog::SUCCESS;
                            $transactiondata['created_at'] = date('Y-m-d H:i:s');
                            $transactiondata['updated_at'] = date('Y-m-d H:i:s');
                            $transactiondata['CreatedBy'] = $CreatedBy;
                            $transactiondata['ModifyBy'] = $CreatedBy;
                            TransactionLog::insert($transactiondata);
                            $Invoice->update(array('InvoiceStatus' => Invoice::PAID));
                        }
                    }
                    return json_encode(array("status" => "success", "message" => "All Invoice Paid Successfully"));
                } else {
                    foreach ($unPaidInvoices as $Invoiceid) {
                        if (in_array($Invoiceid->InvoiceID, explode(',', $Invoiceids))) {
                            $Invoice = Invoice::find($Invoiceid->InvoiceID);
                            $transactiondata = array();
                            $transactiondata['CompanyID'] = $account->CompanyId;
                            $transactiondata['AccountID'] = $account->AccountID;
                            $transactiondata['InvoiceID'] = $Invoice->InvoiceID;
                            $transactiondata['Transaction'] = $transactionResponse['transaction_id'];
                            $transactiondata['Notes'] = $transactionResponse['transaction_notes'];
                            $transactiondata['Amount'] = floatval($Invoiceid->RemaingAmount);
                            $transactiondata['Status'] = TransactionLog::FAILED;
                            $transactiondata['created_at'] = date('Y-m-d H:i:s');
                            $transactiondata['updated_at'] = date('Y-m-d H:i:s');
                            $transactiondata['CreatedBy'] = $CreatedBy;
                            $transactiondata['ModifyBy'] = $CreatedBy;
                            TransactionLog::insert($transactiondata);
                        }
                    }
                    return json_encode(array("status" => "failed", "message" => "Transaction Failed :" . $transactionResponse['failed_reason']));
                }
            } else {
                return json_encode(array("status" => "failed", "message" => "Account Profile not set"));
            }
        } else {
            return json_encode(array("status" => "failed", "message" => "Total outstanding is less or equal to zero"));
        }
    }
}