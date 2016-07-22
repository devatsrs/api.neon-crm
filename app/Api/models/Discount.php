<?php
namespace Api\Model;

use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    protected $guarded = array("DiscountID");

    protected $table = 'tblDiscount';

    protected $primaryKey = "DiscountID";

    public static function checkForeignKeyById($id) {


        /** todo implement this function   */
        return false;
    }

}