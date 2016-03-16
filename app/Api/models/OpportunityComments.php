<?php
namespace Api\Model;

use Illuminate\Database\Eloquent\Model;
class OpportunityComments extends \Eloquent {

    //protected $connection = 'sqlsrv';
    protected $fillable = [];
    protected $guarded = array('OpportunityCommentID');
    protected $table = 'tblOpportunityComments';
    public  $primaryKey = "OpportunityCommentID";

}