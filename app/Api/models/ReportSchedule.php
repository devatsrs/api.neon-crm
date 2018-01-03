<?php
namespace Api\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;


class ReportSchedule extends Model {
    protected $guarded = array("ReportScheduleID");
    protected $table = "tblReportSchedule";
    protected $primaryKey = "ReportScheduleID";
    protected $connection = 'neon_report';
    protected $fillable = array(
        'CompanyID','Name','Settings','ReportID','created_at','UpdatedBy','updated_at','CreatedBy','Status'
    );
    public static function checkForeignKeyById($ReportScheduleID){
        return false;
    }
}