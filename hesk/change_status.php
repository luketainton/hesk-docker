<?php
/**
 *
 * This file is part of HESK - PHP Help Desk Software.
 *
 * (c) Copyright Klemen Stirn. All rights reserved.
 * https://www.hesk.com
 *
 * For the full copyright and license agreement information visit
 * https://www.hesk.com/eula.php
 *
 */

define('IN_SCRIPT',1);
define('HESK_PATH','./');

// Get all the required files and functions
require(HESK_PATH . 'hesk_settings.inc.php');
require(HESK_PATH . 'inc/common.inc.php');

// Are we in maintenance mode?
hesk_check_maintenance();

hesk_load_database_functions();
hesk_session_start();

// A security check
hesk_token_check();

// Get the tracking ID
$trackingID = hesk_cleanID() or die("$hesklang[int_error]: $hesklang[no_trackID]");

// Get new status
$status = intval( hesk_GET('s', 0) );

$locked = 0;

if ($status == 3) // Closed
{
	// Is customer closing tickets enabled?
	if ( ! $hesk_settings['custclose'])
	{
		hesk_error($hesklang['attempt']);
	}

	$action = $hesklang['closed'];
    $revision = sprintf($hesklang['thist3'],hesk_date(),$hesklang['customer']);

    if ($hesk_settings['custopen'] != 1)
    {
    	$locked = 1;
    }

	// Mark that customer resolved the ticket
	$closedby_sql = ' , `closedat`=NOW(), `closedby`=0 ';
}
elseif ($status == 2) // Opened
{
	// Is customer reopening tickets enabled?
	if ( ! $hesk_settings['custopen'])
	{
		hesk_error($hesklang['attempt']);
	}

	$action = $hesklang['opened'];
    $revision = sprintf($hesklang['thist4'],hesk_date(),$hesklang['customer']);

	// We will ask the customer why is the ticket being reopened
	$_SESSION['force_form_top'] = true;

	// Ticket is not resolved
	$closedby_sql = ' , `closedat`=NULL, `closedby`=NULL ';
}
else
{
	die("$hesklang[int_error]: $hesklang[status_not_valid].");
}

// Connect to database
hesk_dbConnect();

// Verify email address match if needed
hesk_verifyEmailMatch($trackingID);

// Setup required session vars
$_SESSION['t_track'] = $trackingID;
$_SESSION['t_email'] = $hesk_settings['e_email'];

// Load statuses
require_once(HESK_PATH . 'inc/statuses.inc.php');

// Is current ticket status even changeable by customers?
$ticket = hesk_dbFetchAssoc( hesk_dbQuery( "SELECT `status`, `staffreplies`, `lastreplier` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` WHERE `trackid`='".hesk_dbEscape($trackingID)."' LIMIT 1") );
if ( ! hesk_can_customer_change_status($ticket['status']))
{
    hesk_process_messages($hesklang['scno'],'ticket.php');
}

// Lets make status assignment a bit smarter when reopening tickets
if ($status == 2)
{
	// If ticket has no staff replies set the status to "New"
	if ($ticket['staffreplies'] < 1)
	{
		$status = 0;
	}
	// If last reply was by customer set status to "Waiting reply from staff"
	elseif ($ticket['lastreplier'] == 0)
	{
		$status = 1;
	}
	// If nothing matches: last reply was from staff, keep status "Waiting reply from customer"
}

// Modify values in the database
hesk_dbQuery("UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` SET `status`='{$status}', `locked`='{$locked}' $closedby_sql , `history`=CONCAT(`history`,'".hesk_dbEscape($revision)."') WHERE `trackid`='".hesk_dbEscape($trackingID)."' AND `locked` != '1'");

// Did we modify anything*
if (hesk_dbAffectedRows() != 1)
{
	hesk_process_messages($hesklang['elocked'],'ticket.php');
}

// Show success message
if ($status != 3)
{
	hesk_process_messages($hesklang['wrepo'],'ticket.php','NOTICE');
}
else
{
	hesk_process_messages($hesklang['your_ticket_been'].' '.$action,'ticket.php','SUCCESS');
}
