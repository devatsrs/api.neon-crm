<?php
namespace Api\Model;

use Illuminate\Database\Eloquent\Model;

class DiscountPlan extends Model
{
    protected $guarded = array("DiscountPlanID");

    protected $table = 'tblDiscountPlan';

    protected $primaryKey = "DiscountPlanID";

    public static function checkForeignKeyById($id) {


        /** todo implement this function   */
        return false;
    }

}