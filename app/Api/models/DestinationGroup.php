<?php
namespace Api\Model;

use Illuminate\Database\Eloquent\Model;

class DestinationGroup extends Model
{

    protected $guarded = array("DestinationGroupID");

    protected $table = 'tblDestinationGroup';

    protected $primaryKey = "DestinationGroupID";

    public $timestamps = false; // no created_at and updated_at

    public static function checkForeignKeyById($id) {
        $hasInDiscountPlan = DiscountPlan::where("DestinationGroupID",$id)->count();
        if( intval($hasInDiscountPlan) > 0 ){
            return true;
        }else{
            return false;
        }
    }

}