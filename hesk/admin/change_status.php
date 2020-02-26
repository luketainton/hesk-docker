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
define('HESK_PATH','../');

/* Get all the required files and functions */
require(HESK_PATH . 'hesk_settings.inc.php');
require(HESK_PATH . 'inc/common.inc.php');
require(HESK_PATH . 'inc/admin_functions.inc.php');
hesk_load_database_functions();

hesk_session_start();
hesk_dbConnect();
hesk_isLoggedIn();

/* Check permissions for this feature */
hesk_checkPermission('can_view_tickets');

/* A security check */
hesk_token_check();

/* Ticket ID */
$trackingID = hesk_cleanID() or die($hesklang['int_error'].': '.$hesklang['no_trackID']);

// Load statuses
require_once(HESK_PATH . 'inc/statuses.inc.php');

/* New status */
$status = intval( hesk_REQUEST('s') );
if ( ! isset($hesk_settings['statuses'][$status]))
{
	hesk_process_messages($hesklang['instat'],'admin_ticket.php?track='.$trackingID.'&Refresh='.mt_rand(10000,99999),'NOTICE');
}

// We need can_reply_tickets permission unless we are closing a ticket
if ($status != 3)
{
    hesk_checkPermission('can_reply_tickets');
}

$locked = 0;

if ($status == 3) // Closed
{
    if ( ! hesk_checkPermission('can_resolve', 0))
    {
        hesk_process_messages($hesklang['noauth_resolve'],'admin_ticket.php?track='.$trackingID.'&Refresh='.mt_rand(10000,99999),'NOTICE');
    }

	$action = $hesklang['ticket_been'] . ' ' . $hesklang['closed'];
    $revision = sprintf($hesklang['thist3'],hesk_date(),$_SESSION['name'].' ('.$_SESSION['user'].')');

    if ($hesk_settings['custopen'] != 1)
    {
    	$locked = 1;
    }

	// Notify customer of closed ticket?
	if ($hesk_settings['notify_closed'])
	{
		// Get ticket info
		$result = hesk_dbQuery("SELECT * FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` WHERE `trackid`='".hesk_dbEscape($trackingID)."' LIMIT 1");
		if (hesk_dbNumRows($result) != 1)
		{
			hesk_error($hesklang['ticket_not_found']);
		}
		$ticket = hesk_dbFetchAssoc($result);
		$ticket['dt'] = hesk_date($ticket['dt'], true);
		$ticket['lastchange'] = hesk_date($ticket['lastchange'], true);
		$ticket = hesk_ticketToPlain($ticket, 1, 0);

		// Notify customer
		require(HESK_PATH . 'inc/email_functions.inc.php');
		hesk_notifyCustomer('ticket_closed');
	}

	// Log who marked the ticket resolved
	$closedby_sql = ' , `closedat`=NOW(), `closedby`='.intval($_SESSION['id']).' ';
}
elseif ($status != 0)
{
    $status_name = hesk_get_status_name($status);
	$action = sprintf($hesklang['tsst'], $status_name);
    $revision = sprintf($hesklang['thist9'],hesk_date(),$status_name,$_SESSION['name'].' ('.$_SESSION['user'].')');

	// Ticket is not resolved
	$closedby_sql = ' , `closedat`=NULL, `closedby`=NULL ';
}
else // Opened
{
	$action = $hesklang['ticket_been'] . ' ' . $hesklang['opened'];
    $revision = sprintf($hesklang['thist4'],hesk_date(),$_SESSION['name'].' ('.$_SESSION['user'].')');

	// Ticket is not resolved
	$closedby_sql = ' , `closedat`=NULL, `closedby`=NULL ';
}

hesk_dbQuery("UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` SET `status`='{$status}', `locked`='{$locked}' $closedby_sql , `history`=CONCAT(`history`,'".hesk_dbEscape($revision)."') WHERE `trackid`='".hesk_dbEscape($trackingID)."'");

if (hesk_dbAffectedRows() != 1)
{
	hesk_error("$hesklang[int_error]: $hesklang[trackID_not_found].");
}

hesk_process_messages($action,'admin_ticket.php?track='.$trackingID.'&Refresh='.rand(10000,99999),'SUCCESS');
