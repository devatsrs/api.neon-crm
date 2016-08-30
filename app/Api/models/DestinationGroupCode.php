<?php
namespace Api\Model;

use Illuminate\Database\Eloquent\Model;

class DestinationGroupCode extends Model
{
    protected $guarded = array("DestinationGroupCodeID");

    protected $table = 'tblDestinationGroupCode';

    protected $primaryKey = "DestinationGroupCodeID";

    public $timestamps = false; // no created_at and updated_at


}