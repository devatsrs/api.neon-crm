<?php
//use Illuminate\Http\Request;

$api = app('Dingo\Api\Routing\Router');

// Version 1 of our API
$api->version('v1', function ($api) {

	// Set our namespace for the underlying routes
	$api->group(['namespace' => 'Api\Controllers', 'middleware' => ['cors','dbselector']], function ($api) {

		// Login route
		$api->post('login', 'AuthController@authenticate');
        $api->post('logout', 'AuthController@logout');
		$api->post('register', 'AuthController@register');
		$api->post('l/{id}', 'AuthController@authenticate');
		$request =$api->getCurrentRequest();

       $postdata    =  Input::all(); 
        if(isset($postdata['LoginType']) && $postdata['LoginType']=='customer') { //set customer configuration  
              \Config::set('jwt.user' , "Api\Model\Customer");
              \Config::set('auth.table', 'tblAccount');
              \Config::set('auth.model', Api\Model\Customer::class);
			  \Config::set('jwt.identifier', 'AccountID');
        }
		
		// Dogs! All routes in here are protected and thus need a valid token
		//$api->group( [ 'protected' => true, 'middleware' => 'jwt.refresh' ], function ($api) {
		$api->group( [ 'middleware' => 'jwt.refresh' ], function ($api) {

			//token validation
			$api->get('users/me', 'AuthController@me');
			$api->get('validate_token', 'AuthController@validateToken');

            //leads
            $api->get('lead/{id}/get_account', 'LeadController@GetLead');
            $api->get('lead/get_leads', 'LeadController@GetLeads');
            $api->post('lead/add_lead', 'LeadController@add_lead');
            $api->post('lead/{id}/update_lead', 'LeadController@update_lead');
            //accounts
            $api->get('account/{id}/get_account', 'AccountController@GetAccount');

            $api->post('account/add_account', 'AccountController@add_account');
            $api->post('account/{id}/update_account', 'AccountController@update_account');
            $api->get('account/GetAccountLeadByContactNumber', 'AccountController@GetAccountLeadByContactNumber');

            $api->post('emailattachment/{id}/getattachment/{attachmentID}', 'AccountActivityController@getAttachment');
			
			//dashboard			
			$api->post('dashboard/GetUsersTasks', 'DashboardController@GetUsersTasks');
			$api->post('dashboard/GetPipleLineData', 'DashboardController@GetPipleLineData');
			$api->post('dashboard/GetSalesdata', 'DashboardController@GetSalesdata');
			$api->post('dashboard/GetForecastData', 'DashboardController@GetForecastData');			
			$api->post('dashboard/get_opportunities_grid','DashboardController@getOpportunitiesGrid');			
			$api->post('dashboard/CrmDashboardSalesRevenue','DashboardController@CrmDashboardSalesRevenue');
			$api->post('dashboard/CrmDashboardUserRevenue','DashboardController@CrmDashboardUserRevenue');
			


			// account credit
            $api->get('account/get_credit', 'AccountController@GetCredit');
			$api->post('account/update_credit', 'AccountController@UpdateCredit');
			$api->delete('account/delete_credit', 'AccountController@DeleteCredit');
			$api->get('account/get_creditinfo', 'AccountController@GetCreditInfo');
			$api->post('account/update_creditinfo', 'AccountController@UpdateCreditInfo');
			$api->get('account/get_credithistorygrid', 'AccountController@GetCreditHistoryGrid');

			// account temp credit
			$api->get('account/get_temp_credit', 'AccountController@GetTempCredit');
			$api->post('account/update_temp_credit', 'AccountController@UpdateTempCredit');
			$api->delete('account/delete_temp_credit', 'AccountController@DeleteTempCredit');
			$api->post('account/add_note', 'AccountController@add_note');
            $api->get('account/get_note','AccountController@GetNote');
            $api->post('account/delete_note','AccountController@DeleteNote');
			$api->post('account/update_note','AccountController@UpdateNote');
			$api->post('account/GetConversations','AccountController@GetConversations');

            $api->get('account/GetTimeLine', 'AccountController@GetTimeLine');
            $api->post('accounts/sendemail', 'AccountActivityController@sendMail');
            $api->get('account/get_email','AccountActivityController@GetMail');
            $api->post('account/delete_email','AccountActivityController@DeleteMail');
			

			// account threshold credit
			$api->get('account/get_account_threshold', 'AccountController@GetAccountThreshold');
			$api->post('account/update_account_threshold', 'AccountController@UpdateAccountThreshold');
			$api->delete('account/delete_temp_credit', 'AccountController@DeleteAccountThreshold');
			
			
			$api->get('contact/GetTimeLine', 'ContactsController@GetTimeLine');
			$api->post('contact/add_note', 'ContactsController@add_note');
            $api->get('contact/get_note','ContactsController@GetNote');
            $api->post('contact/delete_note','ContactsController@DeleteNote');
			$api->post('contact/update_note','ContactsController@UpdateNote');
			
            //Opportunity Board
            $api->get('opportunityboard/get_boards','OpportunityBoardController@getBoards');
            $api->post('opportunityboard/add_board','OpportunityBoardController@addBoard');
            $api->post('opportunityboard/{id}/update_board','OpportunityBoardController@updateBoard');

            //Opportunity Board column
            $api->get('opportunityboardcolumn/{id}/get_columns','OpportunityBoardColumnController@getColumns');
            $api->post('opportunityboardcolumn/add_column','OpportunityBoardColumnController@addColumn');
            $api->post('opportunityboardcolumn/{id}/update_column','OpportunityBoardColumnController@updateColumn');
            $api->post('opportunityboardcolumn/{id}/update_columnOrder','OpportunityBoardColumnController@updateColumnOrder');

            //Opportunity
            $api->post('opportunity/{id}/get_opportunities','OpportunityController@getOpportunities');
            $api->get('opportunity/{id}/get_attachments','OpportunityController@getAttachments');
            $api->post('opportunity/{id}/save_attachment','OpportunityController@saveAttachment');
            $api->get('opportunity/{id}/delete_attachment/{attachmentid}','OpportunityController@deleteAttachment');
            $api->post('opportunity/add_opportunity','OpportunityController@addOpportunity');
            $api->post('opportunity/{id}/update_opportunity','OpportunityController@updateOpportunity');
            $api->post('opportunity/{id}/update_columnorder','OpportunityController@updateColumnOrder');
            $api->get('opportunity/{id}/get_lead','OpportunityController@getLead');
            $api->get('opportunity/{id}/get_dropdownleadaccount','OpportunityController@getDropdownLeadAccount');
            $api->post('opportunity/{id}/getattachment/{attachmentid}', 'OpportunityController@getAttachment');

            //Opportunity Comments
            $api->post('opportunitycomment/add_comment', 'OpportunityCommentsController@add_comment');
            $api->get('opportunitycomments/{id}/get_comments', 'OpportunityCommentsController@get_comments');
            $api->post('opportunitycomment/{id}/getattachment/{attachmentid}', 'OpportunityCommentsController@getAttachment');

			 //Task
			$api->post('task/{id}/get_tasks','TaskController@getTasks');
            $api->get('task/{id}/get_attachments','TaskController@getAttachments');
            $api->post('task/{id}/save_attachment','TaskController@saveAttachment');
            $api->get('task/{id}/delete_attachment/{attachmentid}','TaskController@deleteAttachment');
            $api->post('task/add_task','TaskController@addTask');
            $api->post('task/{id}/update_task','TaskController@updateTask');
            $api->post('task/{id}/update_columnorder','TaskController@updateColumnOrder');
            $api->get('task/{id}/get_lead','TaskController@getLead');
            $api->get('task/{id}/get_dropdownleadaccount','TaskController@getDropdownLeadAccount');
            $api->get('task/get_priorities','TaskController@getPriority');
            $api->get('task/GetTask','TaskController@GetTask');
			$api->post('task/deletetask','TaskController@DeleteTask');		

            $api->post('task/{id}/getattachment/{attachmentid}', 'TaskController@getAttachment');

            //Allowed extensions
            $api->get('get_allowed_extensions', 'TaskController@get_allowed_extensions');

            //Task Comments
            $api->post('taskcomment/add_comment', 'TaskCommentsController@add_comment');
            $api->get('taskcomments/{id}/get_comments', 'TaskCommentsController@get_comments');
            $api->post('taskcomment/{id}/getattachment/{attachmentid}', 'TaskCommentsController@getAttachment');

            // Destination Group Set
            $api->get('destinationgroupset/datagrid', 'DestinationGroupSetController@DataGrid');
            //$api->get('destinationgroup/{id}', 'DestinationGroupSetController@show');
            $api->post('destinationgroupset/store', 'DestinationGroupSetController@Store');
            $api->put('destinationgroupset/update/{DestinationGroupSetID}', 'DestinationGroupSetController@Update');
            $api->delete('destinationgroupset/delete/{DestinationGroupSetID}', 'DestinationGroupSetController@Delete');

            // Destination Group
            $api->get('destinationgroup/datagrid', 'DestinationGroupController@DataGrid');
            //$api->get('destinationgroup/{id}', 'DestinationGroupController@show');
            $api->post('destinationgroup/store', 'DestinationGroupController@Store');
            $api->put('destinationgroup/update/{DestinationGroupID}', 'DestinationGroupController@Update');
            $api->put('destinationgroup/update_name/{DestinationGroupID}', 'DestinationGroupController@UpdateName');
            $api->delete('destinationgroup/delete/{DestinationGroupID}', 'DestinationGroupController@Delete');
            $api->get('destinationgroupsetcode/datagrid', 'DestinationGroupController@CodeDataGrid');


            // Discount Plan
            $api->get('discountplan/datagrid', 'DiscountPlanController@DataGrid');
            //$api->get('destinationgroup/{id}', 'DiscountPlanController@show');
            $api->post('discountplan/store', 'DiscountPlanController@Store');
            $api->put('discountplan/update/{DiscountPlanID}', 'DiscountPlanController@Update');
            $api->delete('discountplan/delete/{DiscountPlanID}', 'DiscountPlanController@Delete');

            // Discount
            $api->get('discount/datagrid', 'DiscountController@DataGrid');
            //$api->get('destinationgroup/{id}', 'DiscountPlanController@show');
            $api->post('discount/store', 'DiscountController@Store');
            $api->put('discount/update/{DiscountPlanID}', 'DiscountController@Update');
            $api->delete('discount/delete/{DiscountPlanID}', 'DiscountController@Delete');

            // Billing Class
            $api->get('billing_class/datagrid', 'BillingClassController@DataGrid');
            $api->get('billing_class/get/{BillingClassID}', 'BillingClassController@get');
            $api->post('billing_class/store', 'BillingClassController@Store');
            $api->put('billing_class/update/{BillingClassID}', 'BillingClassController@Update');
            $api->delete('billing_class/delete/{BillingClassID}', 'BillingClassController@Delete');

            // Alerts
            $api->get('qos_alert/datagrid', 'AlertController@DataGrid');
            //$api->get('qos_alert/get/{AlertID}', 'BillingClassController@get');
            $api->post('qos_alert/store', 'AlertController@Store');
            $api->put('qos_alert/update/{AlertID}', 'AlertController@Update');
            $api->delete('qos_alert/delete/{AlertID}', 'AlertController@Delete');
            $api->get('alert/history', 'AlertController@History');

            // Mailbox Class
            $api->post('email/sendemail', 'MailboxController@sendMail');
			
			$api->post('ticketgroups/get_groups', 'TicketsGroupController@getGroups');
			$api->post('ticketgroups/store', 'TicketsGroupController@Store');
			$api->post('ticketgroups/get/{id}', 'TicketsGroupController@get');
			
			$api->post('ticketgroups/get_group_agents/{id}', 'TicketsGroupController@get_group_agents');
			$api->post('ticketgroups/get_group_agents_ids/{id}', 'TicketsGroupController@get_group_agents_ids');
			$api->put('ticketgroups/update/{id}', 'TicketsGroupController@Update');
            $api->post('ticketgroups/delete/{id}', 'TicketsGroupController@Delete');			
			$api->post('ticketgroups/send_activation_single/{id}', 'TicketsGroupController@send_activation_single');
			$api->post('ticketgroups/validatesmtp', 'TicketsGroupController@validatesmtp');
			
			
			$api->post('ticketsfields/getfields', 'TicketsFieldsController@GetFields');
			$api->post('ticketsfields/iframeSubmits', 'TicketsFieldsController@iframeSubmits');
			$api->post('ticketsfields/GetDynamicFields', 'TicketsFieldsController@GetDynamicFields');
			
			$api->post('tickets/get_tickets', 'TicketsController@GetResult');			
			$api->post('tickets/store', 'TicketsController@Store');
			
			$api->post('tickets/getticket/{id}', 'TicketsController@GetSingleTicket');
			$api->post('tickets/getticketdetail/{id}', 'TicketsController@GetSingleTicketDetails');		
			$api->post('tickets/getticketdetailsdata', 'TicketsController@GetTicketDetailsData');
			$api->post('tickets/ticketcction', 'TicketsController@TicketAction');
			$api->post('tickets/actionsubmit/{id}', 'TicketsController@ActionSubmit');		
			$api->post('tickets/closeticket/{id}', 'TicketsController@CloseTicket');	
			$api->post('tickets/delete/{id}', 'TicketsController@Delete');	
			$api->post('tickets/edit/{id}', 'TicketsController@Edit');
			$api->post('tickets/update/{id}', 'TicketsController@Update');
			$api->post('tickets/updatedetailpage/{id}', 'TicketsController@UpdateDetailPage');			
			$api->post('tickets/SendMailTicket', 'TicketsController@SendMailTicket');
			$api->post('tickets/updateticketattributes/{id}', 'TicketsController@UpdateTicketAttributes');									
			$api->post('tickets/add_note', 'TicketsController@add_note');															
		});

	});

});