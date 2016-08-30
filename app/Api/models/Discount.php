<?php
namespace Api\Model;

use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    protected $guarded = array("DiscountID");

    protected $table = 'tblDiscount';

    protected $primaryKey = "DiscountID";

    public static function checkForeignKeyById($id) {


        $hasInAccountDiscountScheme = AccountDiscountScheme::where("DiscountID",$id)->count();
        if( intval($hasInAccountDiscountScheme) > 0){
            return true;
        }else{
            return false;
        }
    }

}