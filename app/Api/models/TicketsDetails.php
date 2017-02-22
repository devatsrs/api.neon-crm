<?php
namespace Api\Model;

use Illuminate\Database\Eloquent\Model;
class TicketsDetails extends \Eloquent 
{
    protected $guarded = array("TicketsDetailsID");

    protected $table = 'tblTicketsDetails';

    protected $primaryKey = "TicketsDetailsID";	
   
}