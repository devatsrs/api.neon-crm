<?php
namespace Api\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CRMBoardColumn extends \Eloquent {
    protected $fillable = [];
    protected $guarded = array('BoardColumnID');
    protected $table = 'tblCRMBoardColumn';
    public  $primaryKey = "BoardColumnID";
    public static $defaultColumnsOpportunity = ['To Do','In Progress','Done'];
    public static $defaultColumnsTask = ['Not Started','In Progress','Waiting','Completed','Deferred'];

    public static function addDefaultColumns($boardID,$boardType=CRMBoard::OpportunityBoard){
        $companyID = User::get_companyID();
        $created_by = User::get_user_full_name();
        if($boardType == CRMBoard::OpportunityBoard) {
            $DefaultColumns = CRMBoardColumn::$defaultColumnsOpportunity;
        }else{
            $DefaultColumns = CRMBoardColumn::$defaultColumnsTask;
        }
        foreach($DefaultColumns as $index=>$column){
            CRMBoardColumn::create(['BoardID'=>$boardID,
                                            'CompanyID'=>$companyID,
                                            'BoardColumnName'=>$column,
                                            'Order'=>$index,
                                            'CreatedBy'=>$created_by]);
        }
    }
}