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
    const ThisWeeks = 3;
    const CustomDate = 4;

    public static $tasks = [Task::All=>'All',Task::Overdue=>'Overdue',Task::DueSoon=>'Due Soon',
        Task::ThisWeeks=>'This Weeks',Task::CustomDate=>'Custom Date'];
}