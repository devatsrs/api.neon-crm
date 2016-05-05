<?php
namespace Api\Model;

use Illuminate\Database\Eloquent\Model;
class CRMComments extends \Eloquent {

    //protected $connection = 'sqlsrv';
    protected $fillable = [];
    protected $guarded = array('CommentID');
    protected $table = 'tblCRMComments';
    public  $primaryKey = "CommentID";

    const opportunityComments = 1;
    const taskComments = 2;

}