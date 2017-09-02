<?php
namespace Api\Models;

class DynamicFields extends \Eloquent {

    protected $guarded = array('DynamicFieldsID');
    protected $table = 'tblDynamicFields';
    public  $primaryKey = "DynamicFieldsID"; //Used in BasedController
    public $timestamps = false;

    public function fieldOptions() {

        return $this->hasMany('DynamicFieldsDetail', 'DynamicFieldsID', 'DynamicFieldsID');
    }

    public function fieldUniqueOption() {

        return $this->hasOne('DynamicFieldsDetail', 'DynamicFieldsID', 'DynamicFieldsID')->where('FieldType','is_unique');
    }
}