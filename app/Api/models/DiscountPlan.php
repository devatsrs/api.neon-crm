<?php
namespace Api\Model;

use Illuminate\Database\Eloquent\Model;

class DiscountPlan extends Model
{
    protected $guarded = array("DiscountPlanID");

    protected $table = 'tblDiscountPlan';

    protected $primaryKey = "DiscountPlanID";

    public static function checkForeignKeyById($id) {

        $hasInDiscount = Discount::where("DiscountPlanID",$id)->count();
        $hasInAccountDiscountPlan = AccountDiscountPlan::where("DiscountPlanID",$id)->count();
        if( intval($hasInDiscount) > 0 || intval($hasInAccountDiscountPlan) > 0 ){
            return true;
        }else{
            return false;
        }
    }

}