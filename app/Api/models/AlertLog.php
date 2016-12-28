<?php
namespace App\Lib;

use Illuminate\Database\Eloquent\Model;

class AlertLog extends Model {
	protected $fillable = [];
    protected $guarded = array('AlertLogID');
    protected $table = 'tblAlertLog';
    protected  $primaryKey = "AlertLogID";

    public $timestamps = false; // no created_at and updated_at
}