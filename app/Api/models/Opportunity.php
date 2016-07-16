<?php
namespace Api\Model;

use Illuminate\Database\Eloquent\Model;
class Opportunity extends \Eloquent {

    //protected $connection = 'sqlsrv';
    protected $fillable = [];
    protected $guarded = array('OpportunityID');
    protected $table = 'tblOpportunity';
    public  $primaryKey = "OpportunityID";

    const Open = 1;
    const Won = 2;
    const Lost = 3;
    const Abandoned = 4;
    const Close  = 5;

    public static $defaultSelectedStatus = [Opportunity::Open,Opportunity::Won,Opportunity::Lost,Opportunity::Abandoned];

    public static $status = [Opportunity::Open=>'Open',Opportunity::Won=>'Won',Opportunity::Lost=>'Lost',
        Opportunity::Abandoned=>'Abandoned'];

    public static function getOpportunityList($select=1){
        $companyID = User::get_companyID();
        $row = Opportunity::where(['CompanyID'=>$companyID])->select(['OpportunityID','OpportunityName'])->lists('OpportunityName','OpportunityID');
        if(!empty($row) & $select==1){
            $row = array(""=> "Select an Opportunity")+$row;
        }
        return $row;
    }
}