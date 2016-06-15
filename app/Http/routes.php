<?php
$api = app('Dingo\Api\Routing\Router');

// Version 1 of our API
$api->version('v1', function ($api) {

	// Set our namespace for the underlying routes
	$api->group(['namespace' => 'Api\Controllers', 'middleware' => ['cors','dbselector']], function ($api) {

		// Login route
		$api->post('login', 'AuthController@authenticate');
		$api->post('register', 'AuthController@register');
        $api->post('l/{id}', 'AuthController@authenticate');

		// Dogs! All routes in here are protected and thus need a valid token
		//$api->group( [ 'protected' => true, 'middleware' => 'jwt.refresh' ], function ($api) {
		$api->group( [ 'middleware' => 'jwt.refresh' ], function ($api) {

			//token validation
			$api->get('users/me', 'AuthController@me');
			$api->get('validate_token', 'AuthController@validateToken');

            //leads
            $api->get('lead/{id}/get_account', 'LeadController@GetLead');
            $api->get('lead/get_leads', 'LeadController@GetLeads');
            //accounts
            $api->get('account/{id}/get_account', 'AccountController@GetAccount');

			// account credit
            $api->get('account/get_credit', 'AccountController@GetCredit');
			$api->post('account/update_credit', 'AccountController@UpdateCredit');
			$api->delete('account/delete_credit', 'AccountController@DeleteCredit');

			// account temp credit
			$api->get('account/get_credit', 'AccountController@GetTempCredit');
			$api->post('account/update_temp_credit', 'AccountController@UpdateTempCredit');
			$api->delete('account/delete_temp_credit', 'AccountController@DeleteTempCredit');
			$api->post('account/add_note', 'AccountController@add_note');
            $api->get('account/get_note','AccountController@GetNote');
            $api->post('account/delete_note','AccountController@DeleteNote');


            $api->get('account/GetTimeLine', 'AccountController@GetTimeLine');
            $api->post('accounts/sendemail', 'AccountActivityController@sendMail');
            $api->get('account/get_email','AccountActivityController@GetMail');
            $api->post('account/delete_email','AccountActivityController@DeleteMail');

			// account threshold credit
			$api->get('account/get_account_threshold', 'AccountController@GetAccountThreshold');
			$api->post('account/update_account_threshold', 'AccountController@UpdateAccountThreshold');
			$api->delete('account/delete_temp_credit', 'AccountController@DeleteAccountThreshold');

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

            //Opportunity Comments
            $api->post('opportunitycomment/add_comment', 'OpportunityCommentsController@add_comment');
            $api->get('opportunitycomments/{id}/get_comments', 'OpportunityCommentsController@get_comments');

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



            //Allowed extensions
            $api->get('get_allowed_extensions', 'TaskController@get_allowed_extensions');

            //Task Comments
            $api->post('taskcomment/add_comment', 'TaskCommentsController@add_comment');
            $api->get('taskcomments/{id}/get_comments', 'TaskCommentsController@get_comments');


		});

	});

});