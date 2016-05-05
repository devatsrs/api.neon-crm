<?php
namespace Api\Model;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;


class Note extends Model {

	//protected $fillable = ["NoteID","CompanyID","AccountID","Title","Note","created_at","updated_at","created_by","updated_by" ];

    protected $guarded = array();

    protected $table = 'tblNote';

    protected  $primaryKey = "NoteID";

}