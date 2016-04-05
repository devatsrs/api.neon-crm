<?php
namespace Api\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CRMBoardColumn extends \Eloquent {
    protected $fillable = [];
    protected $guarded = array('BoardColumnID');
    protected $table = 'tblCRMBoardColumn';
    public  $primaryKey = "BoardColumnID";
    public static $defaultColumns = ['To Do','In Progress','Done'];

    public static function addDefaultColumns($boardID){
        $companyID = User::get_companyID();
        $created_by = User::get_user_full_name();
        foreach(CRMBoardColumn::$defaultColumns as $index=>$column){
            CRMBoardColumn::create(['BoardID'=>$boardID,
                                            'CompanyID'=>$companyID,
                                            'BoardColumnName'=>$column,
                                            'Order'=>$index,
                                            'CreatedBy'=>$created_by]);
        }
    }
}