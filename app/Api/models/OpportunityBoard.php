<?php
namespace Api\Model;

use Illuminate\Database\Eloquent\Model;

class OpportunityBoard extends \Eloquent {

    //protected $connection = 'sqlsrv';
    protected $fillable = [];
    protected $guarded = array('OpportunityBoardID');
    protected $table = 'tblOpportunityBoards';
    public  $primaryKey = "OpportunityBoardID";

    public static function getBoards(){
        $compantID = User::get_companyID();
        $opportunity = OpportunityBoard::select(['OpportunityBoardID','OpportunityBoardName'])->where(['CompanyID'=>$compantID])->lists('OpportunityBoardName','OpportunityBoardID');
        if(!empty($opportunity)){
            $opportunity = [''=>'Select'] + $opportunity;
        }
        return $opportunity;
    }
}