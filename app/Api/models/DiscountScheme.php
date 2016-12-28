<?php
namespace Api\Model;

use Illuminate\Database\Eloquent\Model;

class DiscountScheme extends Model
{
    protected $guarded = array("DiscountSchemeID");

    protected $table = 'tblDiscountScheme';

    protected $primaryKey = "DiscountSchemeID";

    public static function checkForeignKeyById($id) {


        /** todo implement this function   */
        return false;
    }

}