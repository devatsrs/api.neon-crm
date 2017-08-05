<?php
namespace Api\Models;

class DynamicFieldsValue extends \Eloquent {

    protected $guarded = array('DynamicFieldsValueID');
    protected $table = 'tblDynamicFieldsValue';
    public  $primaryKey = "DynamicFieldsValueID"; //Used in BasedController
    public $timestamps = false;
}