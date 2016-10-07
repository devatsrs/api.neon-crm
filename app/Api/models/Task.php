<?php
namespace Api\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

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

    const Open =0;
    const Close = 1;

    public static $tasks = [Task::All=>'All',Task::Overdue=>'Overdue',Task::DueSoon=>'Due Soon',
        Task::CustomDate=>'Custom Date'];


    /**
     * Get all attendees and Assigned User to add in Calendar.
     */
    public static function get_all_attendees_email($Task){

        $attendees = array();

        //Assign To *
        $UserID = $Task->UsersIDs;
        if($UserID>0){
            $attendees[] =User::find($UserID)->pluck("EmailAddress");
        }

        $TaggedUsers = $Task->TaggedUsers;
        
		//Log::info("tagged user " .  $TaggedUsers);
		
        if(!empty($TaggedUsers)){

            //Log::info("tagged user " .  $TaggedUsers);

            $UserIDs = explode(",",$TaggedUsers);

            if(is_array($UserIDs) && !empty($UserIDs) ) {

                foreach ($UserIDs as $UserID) {
                    $attendees[] = User::find($UserID)->pluck("EmailAddress");

                    //Log::info(" tagged user "  . $UserID . " - " .  print_r($attendees,true) );

                }
            }
        }
        return $attendees;
    }
}
