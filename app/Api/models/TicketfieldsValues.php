<?php
namespace Api\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class TicketfieldsValues extends \Eloquent {

    protected $table 		= 	"tblTicketfieldsValues";
    protected $primaryKey 	= 	"ValuesID";
	protected $guarded 		=	 array("ValuesID");
   // public    $timestamps 	= 	false; // no created_at and updated_at	
  // protected $fillable = ['GroupName','GroupDescription','GroupEmailAddress','GroupAssignTime','GroupAssignEmail','GroupAuomatedReply'];
	protected $fillable = [];
	
	static $Status_Closed   = 'Closed';
	static $Status_Resolved = 'Resolved';

    public static $enable_cache = true;

    public static $cache = array(
        "ticketfieldsvalues_cache"    // all records in obj
    );

    public static function getFieldValueIDLIst(){

        /*$data = Input::all();
        $LicenceKey = $data['LicenceKey'];
        $CompanyName = $data['CompanyName'];
        $TicketfieldsValues = 'TicketfieldsValues' . $LicenceKey.$CompanyName;*/
        $TicketfieldsValues = 'TicketfieldsValues';

        if (self::$enable_cache && Cache::has('ticketfieldsvalues_cache')) {
            //check if the cache has already the ```user_defaults``` item
            $admin_defaults = Cache::get('ticketfieldsvalues_cache');
            //get the admin defaults
            self::$cache['ticketfieldsvalues_cache'] = $admin_defaults['ticketfieldsvalues_cache'];
        } else {
            //if the cache doesn't have it yet
            self::$cache['ticketfieldsvalues_cache'] = TicketfieldsValues::select(['FieldValueAgent','ValuesID'])->lists('FieldValueAgent','ValuesID');

            $CACHE_EXPIRE = CompanyConfiguration::get('CACHE_EXPIRE');

            $time = empty($CACHE_EXPIRE)?60:$CACHE_EXPIRE;
            $minutes = \Carbon\Carbon::now()->addMinutes($time);

            //cache the database results so we won't need to fetch them again for 10 minutes at least
            \Illuminate\Support\Facades\Cache::add($TicketfieldsValues, array('ticketfieldsvalues_cache' => self::$cache['ticketfieldsvalues_cache']), $minutes);
            //Cache::forever('ticketfieldsvalues_cache', array('ticketfieldsvalues_cache' => self::$cache['ticketfieldsvalues_cache']));
        }
        return self::$cache['ticketfieldsvalues_cache'];
    }
	
}