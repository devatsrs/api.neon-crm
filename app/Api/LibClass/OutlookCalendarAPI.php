<?php
/**
 * Created by PhpStorm.
 * User: deven
 * Date: 27/07/2016
 * Time: 5:40 PM
 */

namespace App;

use Illuminate\Support\Facades\Log;
use PhpEws\DataType\ArrayOfStringsType;
use PhpEws\DataType\AttendeeType;
use PhpEws\DataType\BodyType;
use PhpEws\DataType\BodyTypeType;
use PhpEws\DataType\CalendarItemCreateOrDeleteOperationType;
use PhpEws\DataType\CalendarItemType;
use PhpEws\DataType\CalendarItemUpdateOperationType;
use PhpEws\DataType\CreateItemType;
use PhpEws\DataType\EmailAddressType;
use PhpEws\DataType\GetServerTimeZonesType;
use PhpEws\DataType\ImportanceChoicesType;
use PhpEws\DataType\ItemChangeType;
use PhpEws\DataType\ItemClassType;
use PhpEws\DataType\ItemIdType;
use PhpEws\DataType\NonEmptyArrayOfAllItemsType;
use PhpEws\DataType\NonEmptyArrayOfAttendeesType;
use PhpEws\DataType\NonEmptyArrayOfPeriodsType;
use PhpEws\DataType\PathToUnindexedFieldType;
use PhpEws\DataType\SensitivityChoicesType;
use PhpEws\DataType\SetItemFieldType;
use PhpEws\DataType\TimeZoneDefinitionType;
use PhpEws\DataType\UpdateItemType;
use PhpEws\EwsConnection;

class OutlookCalendarAPI
{
    protected  $ews;
    const REMINDER_MIN = 15;

    public function  __construct($server,$username,$password){

        $version =  EwsConnection::VERSION_2010_SP2;
        $this->ews = new EwsConnection($server, $username, $password, $version);
    }

    public function test(){
        /**
         * Important links
         *
         * https://github.com/jamesiarmes/php-ews/issues/28
         * https://github.com/jamesiarmes/php-ews/wiki/Calendar:-Create-Event
         * https://github.com/jamesiarmes/php-ews
         * https://github.com/jamesiarmes/php-ews/issues/172
         * https://github.com/jamesiarmes/php-ews/issues/237
         */
        /* $name = $input->getArgument('name');
         if ($name) {
             $text = 'Hello '.$name;
         } else {
             $text = 'Hello';
         }

         if ($input->getOption('yell')) {
             $text = strtoupper($text);
         }


         $output->writeln($text);*/
//https://pod51036.outlook.com/ews/services.wsdl
        $server = 'pod51036.outlook.com/ews/services.wsdl';
        $username = 'dev@wave-tel.com';
        $password = 'Welcome100';
        $version =  EwsConnection::VERSION_2010_SP2;
        $ews = new EwsConnection($server, $username, $password, $version);


        // Start building the request.
        $request = new CreateItemType();
        $request->Items = new NonEmptyArrayOfAllItemsType();
        $request->Items->CalendarItem = new CalendarItemType();

// Set the subject.
        $request->Items->CalendarItem->Subject = 'Opportunity Event Organized by Dev on 28th ';

// Set the start and end times. For Exchange 2007, you need to include the timezone offset.
// For Exchange 2010, you should set the StartTimeZone and EndTimeZone properties. See below for
// an example.
        date_default_timezone_set("GMT");

/*        $TimeZoneDefinitionType = new TimeZoneDefinitionType();
        $TimeZoneDefinitionType->Id = "GMT Standard Time";

        $request->CalendarItem->StartTimeZone = $TimeZoneDefinitionType ; //"GMT Standard Time";
        $request->Items->CalendarItem->EndTimeZone = $TimeZoneDefinitionType ; // "GMT Standard Time";*/

        //$request->Items->CalendarItem->TimeZone = "Europe/London";
        $date = new \DateTime('2016-07-28 10:00 AM');
        $request->Items->CalendarItem->Start = $date->format('c');
        $date->modify('+3 hour');
        $request->Items->CalendarItem->End = $date->format('c');

        // Build the timezone definition and set it as the StartTimeZone.
        $request->Items->CalendarItem->StartTimeZone = new TimeZoneDefinitionType();
        $request->Items->CalendarItem->StartTimeZone->Id = 'GMT Standard Time';
        $request->Items->CalendarItem->StartTimeZone->Periods = new NonEmptyArrayOfPeriodsType();

// Set no reminders
        $request->Items->CalendarItem->ReminderIsSet = false;

// Or use this to specify when reminder is displayed (if this is not set, the default is 15 minutes)
        $request->Items->CalendarItem->ReminderMinutesBeforeStart = self::REMINDER_MIN;

// Build the body.
        $request->Items->CalendarItem->Body = new BodyType();
        $request->Items->CalendarItem->Body->BodyType = BodyTypeType::HTML;
        $request->Items->CalendarItem->Body->_ = 'This is test body';

// Set the item class type (not required).
        $request->Items->CalendarItem->ItemClass = new ItemClassType();
        $request->Items->CalendarItem->ItemClass->_ = ItemClassType::APPOINTMENT;

// Set the sensativity of the event (defaults to normal).
        $request->Items->CalendarItem->Sensitivity = new SensitivityChoicesType();
        $request->Items->CalendarItem->Sensitivity->_ = SensitivityChoicesType::NORMAL;

// Add some categories to the event.
        $request->Items->CalendarItem->Categories = new ArrayOfStringsType();
        $request->Items->CalendarItem->Categories->String = array('Testing', 'php-ews');

// Set the importance of the event.
        $request->Items->CalendarItem->Importance = new ImportanceChoicesType();
        $request->Items->CalendarItem->Importance->_ = ImportanceChoicesType::NORMAL;




        // Don't send meeting invitations.
        $request->SendMeetingInvitations = CalendarItemCreateOrDeleteOperationType::SEND_ONLY_TO_ALL;

        $request->Items->CalendarItem->RequiredAttendees = new NonEmptyArrayOfAttendeesType();
        //$request->Items->CalendarItem->RequiredAttendees->Attendee = $this->add_EWS_attendees(["shriramsoft@gmail.com"]);
        $request->Items->CalendarItem->RequiredAttendees->Attendee = $this->add_EWS_attendees(["dev@wave-tel.com"]);

        return $response = $ews->CreateItem($request);
    }
    /** Create Outlook calendar event
     * @param array $options
     * @return \PhpEws\CreateItemResponseType
     */
    public function create_event($options = array())
    {

        $timezone = $options["timezone"];
        $start_date = $options["start_date"];
        $due_date = $options["due_date"];
        $description = $options["description"];
        $attendees = $options["attendees"]; // array of emails.
        $subject = $options["subject"];

        /**
         * Important links
         *
         * https://github.com/jamesiarmes/php-ews/issues/28
         * https://github.com/jamesiarmes/php-ews/wiki/Calendar:-Create-Event
         * https://github.com/jamesiarmes/php-ews
         * https://github.com/jamesiarmes/php-ews/issues/172
         * https://github.com/jamesiarmes/php-ews/issues/237
         */

// Set the start and end times. For Exchange 2007, you need to include the timezone offset.
// For Exchange 2010, you should set the StartTimeZone and EndTimeZone properties. See below for
// an example.

        // Start building the request.
        $request = new CreateItemType();
        $request->Items = new NonEmptyArrayOfAllItemsType();
        $request->Items->CalendarItem = new CalendarItemType();

        // Set the subject.
        $request->Items->CalendarItem->Subject = $subject;

        date_default_timezone_set($timezone);

        $start_date = new \DateTime($start_date);
        $request->Items->CalendarItem->Start = $start_date->format('c');

        Log::info($start_date->format('c'));
        Log::info(print_r($start_date,true));

        $due_date = new \DateTime($due_date);
        $request->Items->CalendarItem->End = $due_date->format('c');
        Log::info($due_date->format('c'));
        Log::info(print_r($due_date,true));

// Set no reminders
        $request->Items->CalendarItem->ReminderIsSet = false;
// Or use this to specify when reminder is displayed (if this is not set, the default is 15 minutes)
        $request->Items->CalendarItem->ReminderMinutesBeforeStart = self::REMINDER_MIN;

// Build the body.
        $request->Items->CalendarItem->Body = new BodyType();
        $request->Items->CalendarItem->Body->BodyType = BodyTypeType::HTML;
        $request->Items->CalendarItem->Body->_ = $description;

// Set the item class type (not required).
        $request->Items->CalendarItem->ItemClass = new ItemClassType();
        $request->Items->CalendarItem->ItemClass->_ = ItemClassType::APPOINTMENT;

        // Set the sensativity of the event (defaults to normal).
        $request->Items->CalendarItem->Sensitivity = new SensitivityChoicesType();
        $request->Items->CalendarItem->Sensitivity->_ = SensitivityChoicesType::NORMAL;

// Add some categories to the event.
        //$request->Items->CalendarItem->Categories = new ArrayOfStringsType();
        //$request->Items->CalendarItem->Categories->String = array('Testing', 'php-ews');

// Set the importance of the event.
        $request->Items->CalendarItem->Importance = new ImportanceChoicesType();
        $request->Items->CalendarItem->Importance->_ = ImportanceChoicesType::NORMAL;

        // Don't send meeting invitations.
        $request->SendMeetingInvitations = CalendarItemCreateOrDeleteOperationType::SEND_ONLY_TO_ALL;

        $request->Items->CalendarItem->RequiredAttendees = new NonEmptyArrayOfAttendeesType();
        //$request->Items->CalendarItem->RequiredAttendees->Attendee = $this->add_EWS_attendees(["shriramsoft@gmail.com"]);
        $request->Items->CalendarItem->RequiredAttendees->Attendee = $this->add_EWS_attendees($attendees);


        Log::info("Creating event");

        $response = $this->ews->CreateItem($request);

        return $this->parse_response($response);
        //$output->writeln($response);
    }


    public function update_event($options = array()) {

        $timezone = $options["timezone"];
        $start_date = $options["start_date"];
        $due_date = $options["due_date"];
        $description = $options["description"];
        $attendees = $options["attendees"]; // array of emails.
        $subject = $options["subject"];


        if(isset($options["event_id"]) && isset($options["change_key"]) && !empty($options["event_id"]) && !empty($options["change_key"]) ) {

            Log::info("Updating event - " . $options["event_id"]);

            $request = new UpdateItemType();
            $request->ConflictResolution = 'AlwaysOverwrite';
            $request->SendMeetingInvitationsOrCancellations = CalendarItemUpdateOperationType::SEND_ONLY_TO_ALL;
            $request->ItemChanges = array();


            $change = new ItemChangeType();
            $change->ItemId = new ItemIdType();
            $change->ItemId->Id = $options["event_id"];
            $change->ItemId->ChangeKey = $options["change_key"];


            //Update Subject Property
            $field = new SetItemFieldType();
            $field->FieldURI = new PathToUnindexedFieldType();
            $field->FieldURI->FieldURI = 'item:Subject';
            $field->CalendarItem = new CalendarItemType();
            $field->CalendarItem->Subject = $subject;
            $change->Updates->SetItemField[] = $field;

            $due_date = new \DateTime($due_date);

            // Update End Property
            $field = new SetItemFieldType();
            $field->FieldURI = new PathToUnindexedFieldType();
            $field->FieldURI->FieldURI = 'calendar:End';
            $field->CalendarItem = new CalendarItemType();
            $field->CalendarItem->End = $due_date->format('c');
            $change->Updates->SetItemField[] = $field;

            // Update Body Property
            $field = new SetItemFieldType();
            $field->FieldURI = new PathToUnindexedFieldType();
            $field->FieldURI->FieldURI = 'item:Body';
            $field->CalendarItem = new CalendarItemType();
            $field->CalendarItem->Body = new BodyType();
            $field->CalendarItem->Body->BodyType = BodyTypeType::HTML;
            $field->CalendarItem->Body->_ = $description;
            $change->Updates->SetItemField[] = $field;


            //Attendees
            $field = new SetItemFieldType();
            $field->FieldURI = new PathToUnindexedFieldType();
            $field->FieldURI->FieldURI = 'calendar:RequiredAttendees';
            $field->CalendarItem = new CalendarItemType();
            $field->CalendarItem->RequiredAttendees = new NonEmptyArrayOfAttendeesType();
            $field->CalendarItem->RequiredAttendees->Attendee = $this->add_EWS_attendees($attendees);
            $change->Updates->SetItemField[] = $field;


            //End
            $request->ItemChanges[] = $change;

            Log::info("Updating event");
            $response = $this->ews->UpdateItem($request);

            return $this->parse_response($response);

        }

    }


    public function add_EWS_attendees($attendees) {

        $toAdd = Array();
        $i = 0;
        foreach ($attendees as $atten)
        {
            $toAdd[$i] = new AttendeeType();
            $toAdd[$i]->Mailbox = new EmailAddressType();  // line missing in your code
            $toAdd[$i]->Mailbox->EmailAddress = $atten;  // line modified
            $toAdd[$i]->Name = $atten;
            $i++;
        }

        return ($toAdd);
    }



    /** Parse response to output api response to front end.
     * @param $response
     * @return array
     */
    public function parse_response($response){

        Log::info(print_r($response,true));

        /**
         * Return output for api request response.
         */
        $output = array(
            "event_id" => "",
            "change_key" => "",
            "message" => ""
        );

        if(!empty($response)){

            if(isset($response->ResponseMessages->CreateItemResponseMessage->ResponseClass) &&
                $response->ResponseMessages->CreateItemResponseMessage->ResponseClass == 'Success'
            ){

                $output['event_id'] = $response->ResponseMessages->CreateItemResponseMessage->Items->CalendarItem->ItemId->Id;
                $output['change_key'] = $response->ResponseMessages->CreateItemResponseMessage->Items->CalendarItem->ItemId->ChangeKey;
                $output['message'] = "Event Created Successfully.";
            }
            else if(isset($response->ResponseMessages->UpdateItemResponseMessage->ResponseClass) &&
                $response->ResponseMessages->UpdateItemResponseMessage->ResponseClass == 'Success'
            ){

                $output['event_id'] = $response->ResponseMessages->UpdateItemResponseMessage->Items->CalendarItem->ItemId->Id;
                $output['change_key'] = $response->ResponseMessages->UpdateItemResponseMessage->Items->CalendarItem->ItemId->ChangeKey;
                $output['message'] = "Event Updated Successfully.";
            }
        }


        else {

            $output['message'] = "Unable to Create Event on Calendar.";

            Log::info("error creating calendar event");
            Log::info(print_r($response,true));
        }

        return $output;

    }
}