<?php
namespace Api\Model;
use Illuminate\Database\Eloquent\Model;
class AccountBilling extends \Eloquent {

    protected $guarded = array('AccountBillingID');

    protected $table = 'tblAccountBilling';

    protected  $primaryKey = "AccountBillingID";

}