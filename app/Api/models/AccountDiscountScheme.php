<?php
namespace Api\Model;

use Illuminate\Database\Eloquent\Model;

class AccountDiscountScheme extends Model
{
    protected $guarded = array("AccountDiscountSchemeID");

    protected $table = 'tblAccountDiscountScheme';

    protected $primaryKey = "AccountDiscountSchemeID";

    public static function checkForeignKeyById($id) {


        /** todo implement this function   */
        return false;
    }

}