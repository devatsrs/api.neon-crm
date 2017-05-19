<?php
namespace Api\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Input;

class TicketImportRuleAction extends \Eloquent {

    protected $guarded = array("TicketImportRuleActionID");
    protected $table = 'tblTicketImportRuleAction';
    protected $primaryKey = "TicketImportRuleActionID";
}