<?php
namespace Api\Model;

use Illuminate\Database\Eloquent\Model;
class Task extends \Eloquent {

    //protected $connection = 'sqlsrv';
    protected $fillable = [];
    protected $guarded = array('TaskID');
    protected $table = 'tblTask';
    public  $primaryKey = "TaskID";

    const High = 1;
    const Medium = 2;
    const Low = 3;

    public static $periority = ['High'=>Task::High,'Medium'=>Task::Medium,'Low'=>Task::Low];
}