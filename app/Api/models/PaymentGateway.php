<?php
namespace Api\Model;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PaymentGateway extends Model{
    protected $fillable = [];
    protected $table = "tblPaymentGateway";
    protected $primaryKey = "PaymentGatewayID";
    protected $guarded = array('PaymentGatewayID');
    public static $gateways = array('Authorize'=>'AuthorizeNet');

    public static function getName($PaymentGatewayID)
    {
        return PaymentGateway::where(array('PaymentGatewayID' => $PaymentGatewayID))->pluck('Title');
    }

    public static function addTransaction($PaymentGateway,$amount,$options,$account,$AccountPaymentProfileID,$CreatedBy)
    {
        switch($PaymentGateway) {
            case 'AuthorizeNet':
                $transaction = AuthorizeNet::addAuthorizeNetTransaction($amount,$options);
				$Notes = '';
                if($transaction->response_code == 1) {
                    $Notes = 'AuthorizeNet transaction_id ' . $transaction->transaction_id;
                    $Status = TransactionLog::SUCCESS;
                }else{
                    $Status = TransactionLog::FAILED;
                    $Notes = isset($transaction->real_response->xml->messages->message->text) && $transaction->real_response->xml->messages->message->text != '' ? $transaction->real_response->xml->messages->message->text : $transaction->error_message ;
                    AccountPaymentProfile::setProfileBlock($AccountPaymentProfileID);
                }
                $transactionResponse['transaction_notes'] =$Notes;
                $transactionResponse['response_code'] = $transaction->response_code;
                $transactionResponse['transaction_payment_method'] = 'CREDIT CARD';
                $transactionResponse['failed_reason'] =$transaction->response_reason_text!='' ? $transaction->response_reason_text : $Notes;
                $transactionResponse['transaction_id'] = $transaction->transaction_id;
                $transactiondata = array();
                $transactiondata['CompanyID'] = $account->CompanyId;
                $transactiondata['AccountID'] = $account->AccountID;
                $transactiondata['Transaction'] = $transaction->transaction_id;
                $transactiondata['Notes'] = $Notes;
                $transactiondata['Amount'] = floatval($transaction->amount);
                $transactiondata['Status'] = $Status;
                $transactiondata['created_at'] = date('Y-m-d H:i:s');
                $transactiondata['updated_at'] = date('Y-m-d H:i:s');
                $transactiondata['CreatedBy'] = $CreatedBy;
                $transactiondata['ModifyBy'] = $CreatedBy;
                $transactiondata['Reposnse'] = json_encode($transaction);
                TransactionLog::insert($transactiondata);
                return $transactionResponse;
            case '':
                return '';

        }

    }

    

}