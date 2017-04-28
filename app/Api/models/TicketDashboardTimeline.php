<?php
namespace Api\Model;

class TicketDashboardTimeline extends \Eloquent
{
    protected $guarded = array("TicketDashboardTimelineID");

    protected $table = 'tblTicketDashboardTimeline';

    protected $primaryKey = "TicketDashboardTimelineID";

}