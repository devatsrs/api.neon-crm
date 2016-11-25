<?php
namespace Api\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Ticket extends Model
{
    protected $guarded = array("ID");

    protected $table = 'tblTickets';

    protected $primaryKey = "ID";
}