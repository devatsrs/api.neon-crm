<?php
namespace Api\Model;

use Illuminate\Database\Eloquent\Model;

class BillingClass extends Model
{
    protected $guarded = array("BillingClassID");

    protected $table = 'tblBillingClass';

    protected $primaryKey = "BillingClassID";

    protected $fillable = array(
        'CompanyID','PaymentDueInDays','RoundChargesAmount','RoundChargesCDR','CDRType','InvoiceTemplateID',
        'BillingType','Name','Description','TaxRateID','BillingTimezone',
        'SendInvoiceSetting','PaymentReminderStatus','PaymentReminderSettings','LowBalanceReminderStatus','LowBalanceReminderSettings',
        'InvoiceReminderStatus','InvoiceReminderSettings','created_at','updated_at','UpdatedBy','CreatedBy'
    );

    public static $rules = array(
        'RoundChargesAmount'=>'required',
        'PaymentDueInDays'=>'required',
        'BillingTimezone' => 'required',
        'InvoiceTemplateID' => 'required',
        'SendInvoiceSetting'=>'required'
    );
    public static $messages = array(
        'RoundChargesAmount.required' =>'The currency field is required',
        'InvoiceTemplateID.required' =>'Invoice Template  field is required',
    );

    public static function checkForeignKeyById($id) {


        $hasInAccountBilling = AccountBilling::where("BillingClassID",$id)->count();
        if( intval($hasInAccountBilling) > 0){
            return true;
        }else{
            return false;
        }
    }

}