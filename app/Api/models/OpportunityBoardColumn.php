<?php
namespace Api\Model;

use Illuminate\Database\Eloquent\Model;
class OpportunityBoardColumn extends \Eloquent {
    protected $fillable = [];
    protected $guarded = array('OpportunityBoardColumnID');
    protected $table = 'tblOpportunityBoardColumn';
    public  $primaryKey = "OpportunityBoardColumnID";

}