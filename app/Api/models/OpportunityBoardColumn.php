<?php
namespace Api\Model;

use Illuminate\Database\Eloquent\Model;
class OpportunityBoardColumn extends \Eloquent {
    protected $fillable = [];
    protected $guarded = array('OpportunityBoardColumnID');
    protected $table = 'tblOpportunityBoardColumn';
    public  $primaryKey = "OpportunityBoardColumnID";
    public static $defaultColumns = ['To do','In Progress','Done'];

    public static function addDefaultColumns($boardID){
        $companyID = User::get_companyID();
        $created_by = User::get_user_full_name();
        foreach(OpportunityBoardColumn::$defaultColumns as $index=>$column){
            OpportunityBoardColumn::create(['BoardID'=>$boardID,
                                            'CompanyID'=>$companyID,
                                            'BoardColumnName'=>$column,
                                            'Order'=>$index,
                                            'CreatedBy'=>$created_by,
                                            'created_at'=>DB::raw('Now()')]);
        }
    }
}