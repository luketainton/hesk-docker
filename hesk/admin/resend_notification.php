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
require(HESK_PATH . 'inc/email_functions.inc.php');

hesk_session_start();
hesk_dbConnect();
hesk_isLoggedIn();

// Check permissions for this feature
hesk_checkPermission('can_view_tickets');

// A security check
hesk_token_check('POST');

// Ticket ID
$trackingID = hesk_cleanID() or die($hesklang['int_error'].': '.$hesklang['no_trackID']);

// Ticket details
$res = hesk_dbQuery("SELECT * FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` WHERE `trackid`='".hesk_dbEscape($trackingID)."' LIMIT 1");
if (hesk_dbNumRows($res) != 1)
{
	hesk_error($hesklang['ticket_not_found']);
}
$ticket = hesk_dbFetchAssoc($res);

// Do we have permission to view this ticket?
if ($ticket['owner'] && $ticket['owner'] != $_SESSION['id'] && ! hesk_checkPermission('can_view_ass_others',0))
{
    // Maybe this user is allowed to view tickets he/she assigned?
    if ( ! $can_view_ass_by || $ticket['assignedby'] != $_SESSION['id'])
    {
        hesk_error($hesklang['ycvtao']);
    }
}

if ( ! $ticket['owner'] && ! hesk_checkPermission('can_view_unassigned',0))
{
	hesk_error($hesklang['ycovtay']);
}

// Is this user allowed to view tickets inside this category?
hesk_okCategory($ticket['category']);

// Reply or original message?
$reply_id  = intval( hesk_GET('reply', 0) );

if ($reply_id > 0)
{
    $result = hesk_dbQuery("SELECT * FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."replies` WHERE `id`={$reply_id} AND `replyto`=".intval($ticket['id'])." LIMIT 1");
    if (hesk_dbNumRows($res) != 1)
    {
        hesk_error($hesklang['ernf']);
    }

    $reply = hesk_dbFetchAssoc($result);

    $ticket['message'] = $reply['message'];
}

/* --> Prepare message */

// 1. Generate the array with ticket info that can be used in emails
$info = array(
'email'			=> $ticket['email'],
'category'		=> $ticket['category'],
'priority'		=> $ticket['priority'],
'owner'			=> $ticket['owner'],
'trackid'		=> $ticket['trackid'],
'status'		=> $ticket['status'],
'name'			=> $ticket['name'],
'subject'		=> $ticket['subject'],
'message'		=> $ticket['message'],
'attachments'	=> $ticket['attachments'],
'dt'			=> hesk_date($ticket['dt'], true),
'lastchange'	=> hesk_date($ticket['lastchange'], true),
'id'			=> $ticket['id'],
'time_worked'   => $ticket['time_worked'],
'last_reply_by' => hesk_getReplierName($ticket),
);

// 2. Add custom fields to the array
foreach ($hesk_settings['custom_fields'] as $k => $v)
{
	$info[$k] = $v['use'] ? $ticket[$k] : '';
}

// 3. Make sure all values are properly formatted for email
$ticket = hesk_ticketToPlain($info, 1, 0);

// Notification of a reply
if ($reply_id > 0)
{
    // Reply by staff, send notification to customer
    if ($reply['staffid'])
    {
        hesk_notifyCustomer('new_reply_by_staff');
    }
    // Reply by customer, notify assigned staff?
    elseif ($ticket['owner'])
    {
        hesk_notifyAssignedStaff(false, 'new_reply_by_customer', 'notify_reply_my');
    }
    // Reply by customer, notify staff
    else
    {
        hesk_notifyStaff('new_reply_by_customer',"`notify_reply_unassigned`='1'");
    }

    hesk_process_messages($hesklang['rns'],'admin_ticket.php?track='.$trackingID.'&Refresh='.rand(10000,99999),'SUCCESS');
}

// Notification of the original ticket
hesk_notifyCustomer();

// Notify staff?
if ($ticket['owner'])
{
    hesk_notifyAssignedStaff(false, 'ticket_assigned_to_you');
}
else
{
    hesk_notifyStaff('new_ticket_staff', "`notify_new_unassigned`='1'");
}

hesk_process_messages($hesklang['tns'],'admin_ticket.php?track='.$trackingID.'&Refresh='.rand(10000,99999),'SUCCESS');
