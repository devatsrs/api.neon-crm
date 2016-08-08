<?php
/**
 * Created by PhpStorm.
 * User: fudev
 * Date: 25/07/14
 * Time: 14:06
 */

namespace PhpEws\Console\Command;


use PhpEws\DataType\ArrayOfStringsType;
use PhpEws\DataType\AttendeeType;
use PhpEws\DataType\BodyType;
use PhpEws\DataType\BodyTypeType;
use PhpEws\DataType\CalendarItemCreateOrDeleteOperationType;
use PhpEws\DataType\CalendarItemType;
use PhpEws\DataType\CreateItemType;
use PhpEws\DataType\EmailAddressDictionaryEntryType;
use PhpEws\DataType\EmailAddressKeyType;
use PhpEws\DataType\EmailAddressType;
use PhpEws\DataType\ImportanceChoicesType;
use PhpEws\DataType\ItemClassType;
use PhpEws\DataType\MeetingAttendeeType;
use PhpEws\DataType\MeetingRequestMessageType;
use PhpEws\DataType\NonEmptyArrayOfAllItemsType;
use PhpEws\DataType\NonEmptyArrayOfAttendeesType;
use PhpEws\DataType\SensitivityChoicesType;
use PhpEws\DataType\SingleRecipientType;
use PhpEws\EwsConnection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;

class ConnectCommand  extends Command {

    protected function configure()
    {
        $this
            ->setName('demo:greet')
            ->setDescription('Greet someone')
            ->addArgument(
                'name',
                InputArgument::OPTIONAL,
                'Who do you want to greet?'
            )
            ->addOption(
                'yell',
                null,
                InputOption::VALUE_NONE,
                'If set, the task will yell in uppercase letters'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

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
        date_default_timezone_set("UTC");
        $date = new \DateTime('2016-07-28 10:00 AM');
        $request->Items->CalendarItem->Start = $date->format('c');
        $date->modify('+3 hour');
        $request->Items->CalendarItem->End = $date->format('c');

// Set no reminders
        $request->Items->CalendarItem->ReminderIsSet = false;

// Or use this to specify when reminder is displayed (if this is not set, the default is 15 minutes)
        $request->Items->CalendarItem->ReminderMinutesBeforeStart = 15;

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
        //$request->SendMeetingInvitations = CalendarItemCreateOrDeleteOperationType::SEND_ONLY_TO_ALL;

        $request->Items->CalendarItem->RequiredAttendees = new NonEmptyArrayOfAttendeesType();
        //$request->Items->CalendarItem->RequiredAttendees->Attendee = $this->add_EWS_attendees(["shriramsoft@gmail.com"]);
        $request->Items->CalendarItem->RequiredAttendees->Attendee = $this->add_EWS_attendees(["dev@wave-tel.com","aamir.saeed@wave-tel.com"]);

        $response = $ews->CreateItem($request);

        //$output->writeln($response);
    }

    function add_EWS_attendees($attendees) {

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


} 
