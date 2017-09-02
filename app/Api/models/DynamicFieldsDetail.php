<?php
namespace Api\Models;

class DynamicFieldsDetail extends \Eloquent {

    protected $guarded = array('DynamicFieldsDetailID');
    protected $table = 'tblDynamicFieldsDetail';
    public  $primaryKey = "DynamicFieldsDetailID"; //Used in BasedController
    public $timestamps = false;

}