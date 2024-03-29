<?php
namespace App\Lib;

use Illuminate\Database\Eloquent\Model;

class Alert extends Model {
    protected $guarded = array("AlertID");
    protected $table = "tblAlert";
    protected $primaryKey = "AlertID";

    const GROUP_QOS = 'qos';
    const GROUP_CALL = 'call';

    public static $qos_alert_type = array(''=>'Select','ACD'=>'ACD','ASR'=>'ASR');
    public static $call_monitor_alert_type = array(''=>'Select','block_destination'=>'Blacklisted Destination','call_duration'=>'Longest Call','call_cost'=>'Expensive Calls','call_after_office'=>'Call After Business Hour');
    public static $call_monitor_customer_alert_type = array(''=>'Select','call_duration'=>'Longest Call','call_cost'=>'Expensive Calls','call_after_office'=>'Call After Business Hour');
    public static $call_blacklist_alert_type = array(''=>'Select','block_destination'=>'Blacklisted Destination');

    protected $fillable = array(
        'CompanyID','Name','AlertType','Status','LowValue','HighValue','AlertGroup',
        'Settings','created_at','updated_at','UpdatedBy','CreatedBy'
    );

    public static $rules = array(
        'AlertType'=>'required',
        'Name'=>'required',
    );
    public static $messages = array(
        'RoundChargesAmount.required' =>'The currency field is required',
        'InvoiceTemplateID.required' =>'Invoice Template  field is required',

    );
    public static function checkForeignKeyById($id) {


        return false;
    }
}