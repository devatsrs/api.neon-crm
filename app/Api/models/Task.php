<?php
namespace Api\Model;

use Illuminate\Database\Eloquent\Model;
class Task extends \Eloquent {

    //protected $connection = 'sqlsrv';
    protected $fillable = [];
    protected $guarded = array('TaskID');
    protected $table = 'tblTask';
    public  $primaryKey = "TaskID";

    const All = 0;
    const Overdue = 1;
    const DueSoon = 2;
    const CustomDate = 3;
	
	const Note  = 3;
	const Mail  = 2;
	const Tasks = 1;

    const Close = 1;

    public static $tasks = [Task::All=>'All',Task::Overdue=>'Overdue',Task::DueSoon=>'Due Soon',
        Task::CustomDate=>'Custom Date'];
}