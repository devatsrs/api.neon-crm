<?php
namespace Api\Model;

use Illuminate\Database\Eloquent\Model;

class DestinationGroupSet extends Model
{
    protected $guarded = array("DestinationGroupSetID");

    protected $table = 'tblDestinationGroupSet';

    protected $primaryKey = "DestinationGroupSetID";

    public $timestamps = false; // no created_at and updated_at


    public static function checkForeignKeyById($id) {

        $hasInDiscountPlan = DiscountPlan::where("DestinationGroupSetID",$id)->count();
        if( intval($hasInDiscountPlan) > 0 ){
            return true;
        }else{
            return false;
        }

    }


}