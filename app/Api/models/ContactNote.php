<?php
namespace Api\Model;

use Illuminate\Database\Eloquent\Model;
class ContactNote extends \Eloquent {
	
    protected $guarded = array('');

    protected $table = 'tblContactNote';

    protected  $primaryKey = "NoteID";
}