<?php
namespace Api\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;


class ReportScheduleLog extends Model {
    protected $guarded = array("ReportScheduleLogID");
    protected $table = "tblReportScheduleLog";
    protected $primaryKey = "ReportScheduleLogID";
    protected $connection = 'neon_report';

    public static function checkForeignKeyById($ReportScheduleLogID){
        return false;
    }
}