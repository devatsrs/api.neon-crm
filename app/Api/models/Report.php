<?php
namespace Api\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class Report extends Model {
    protected $guarded = array("ReportID");
    protected $table = "tblReport";
    protected $primaryKey = "ReportID";
    protected $connection = 'neon_report';
    protected $fillable = array(
        'CompanyID','Name','Settings','created_at','UpdatedBy','updated_at','CreatedBy','Schedule','ScheduleSettings'
    );


    public static $cube = array(
        'summary'=>'CDR',
    );

    public static $dimension = array(
        'summary'=>array(
            'year' => 'Year',
            'quarter_of_year' => 'Quarter' ,
            'month' => 'Month',
            'week_of_year' => 'Week',
            'date' => 'Day',
            'AccountID' =>'Account',
            'CompanyGatewayID' =>'Gateway',
            'Trunk' => 'Trunk',
            'CountryID' => 'Country',
            'AreaPrefix' => 'Prefix',
            'GatewayAccountID' => 'IP/CLI'
        ),
    );

    public static $measures = array(
        'summary'=>array(
            'TotalCharges' => 'Cost',
            'TotalBilledDuration' => 'Duration',
            'NoOfCalls' => 'No Of Calls',
            'NoOfFailCalls' => 'No Of Failed Calls'
        ),
    );

    public static $aggregator = array(
        'SUM' => 'Sum',
        'AVG' => 'Average',
        'COUNT' => 'Count',
        'COUNT_DISTINCT' => 'Count(Distinct)',
        'MAX' => 'Maximum',
        'MIN' => 'Minimum',
    );

    public static $condition = array(
        '=' => '=',
        '<>' => '<>',
        '<' => '<',
        '<=' => '<=',
        '>' => '>',
        '>=' => '>=',
    );

    public static $top = array(
        'top' => 'Top',
        'bottom' => 'Bottom',

    );

    public static $date_fields = ['date'];


}