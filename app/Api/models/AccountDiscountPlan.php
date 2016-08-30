<?php
namespace Api\Model;

use Illuminate\Database\Eloquent\Model;

class AccountDiscountPlan extends Model
{
    protected $guarded = array("AccountDiscountPlanID");

    protected $table = 'tblAccountDiscountPlan';

    protected $primaryKey = "AccountDiscountPlanID";

    public static function checkForeignKeyById($id) {

        $hasInAccountDiscountScheme = AccountDiscountScheme::where("AccountDiscountPlanID",$id)->count();
        if( intval($hasInAccountDiscountScheme) > 0 ){
            return true;
        }else{
            return false;
        }
    }

}