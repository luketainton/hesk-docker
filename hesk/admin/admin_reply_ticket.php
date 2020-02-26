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
require(HESK_PATH . 'inc/posting_functions.inc.php');

// We only allow POST requests from the HESK form to this file
if ( $_SERVER['REQUEST_METHOD'] != 'POST' )
{
	header('Location: admin_main.php');
	exit();
}

// Check for POST requests larger than what the server can handle
if ( empty($_POST) && ! empty($_SERVER['CONTENT_LENGTH']) )
{
	hesk_error($hesklang['maxpost']);
}

hesk_session_start();
hesk_dbConnect();
hesk_isLoggedIn();

/* Check permissions for this feature */
hesk_checkPermission('can_reply_tickets');

/* A security check */
# hesk_token_check('POST');

/* Original ticket ID */
$replyto = intval( hesk_POST('orig_id', 0) ) or die($hesklang['int_error']);

/* Get details about the original ticket */
$result = hesk_dbQuery("SELECT * FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` WHERE `id`='{$replyto}' LIMIT 1");
if (hesk_dbNumRows($result) != 1)
{
	hesk_error($hesklang['ticket_not_found']);
}
$ticket = hesk_dbFetchAssoc($result);
$trackingID = $ticket['trackid'];

// Do we require owner before allowing to reply?
if ($hesk_settings['require_owner'] && ! $ticket['owner'])
{
    hesk_process_messages($hesklang['atbr'],'admin_ticket.php?track='.$ticket['trackid'].'&Refresh='.rand(10000,99999));
}

$hesk_error_buffer = array();

// Get the message
$message = hesk_input(hesk_POST('message'));

// Submit as customer?
$submit_as_customer = isset($_POST['submit_as_customer']) ? true : false;

if (strlen($message))
{
	// Save message for later and ignore the rest?
	if ( isset($_POST['save_reply']) )
	{
		// Delete any existing drafts from this owner for this ticket
		hesk_dbQuery("DELETE FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."reply_drafts` WHERE `owner`=".intval($_SESSION['id'])." AND `ticket`=".intval($ticket['id']));

		// Save the message draft
		hesk_dbQuery("INSERT INTO `".hesk_dbEscape($hesk_settings['db_pfix'])."reply_drafts` (`owner`, `ticket`, `message`) VALUES (".intval($_SESSION['id']).", ".intval($ticket['id']).", '".hesk_dbEscape($message)."')");

		/* Set reply submitted message */
		$_SESSION['HESK_SUCCESS'] = TRUE;
		$_SESSION['HESK_MESSAGE'] = $hesklang['reply_saved'];

		/* What to do after reply? */
		if ($_SESSION['afterreply'] == 1)
		{
			header('Location: admin_main.php');
		}
		elseif ($_SESSION['afterreply'] == 2)
		{
			/* Get the next open ticket that needs a reply */
			$res  = hesk_dbQuery("SELECT * FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` WHERE `owner` IN ('0','".intval($_SESSION['id'])."') AND " . hesk_myCategories() . " AND `status` IN ('0','1') ORDER BY `owner` DESC, `priority` ASC LIMIT 1");

			if (hesk_dbNumRows($res) == 1)
			{
				$row = hesk_dbFetchAssoc($res);
				$_SESSION['HESK_MESSAGE'] .= '<br /><br />'.$hesklang['rssn'];
				header('Location: admin_ticket.php?track='.$row['trackid'].'&Refresh='.rand(10000,99999));
			}
			else
			{
				header('Location: admin_main.php');
			}
		}
		else
		{
			header('Location: admin_ticket.php?track='.$ticket['trackid'].'&Refresh='.rand(10000,99999));
		}
		exit();
	}

	// Attach signature to the message?
	if ( ! $submit_as_customer && ! empty($_POST['signature']) && strlen($_SESSION['signature']))
	{
	    $message .= "\n\n" . addslashes($_SESSION['signature']) . "\n";
	}

    // Make links clickable
	$message = hesk_makeURL($message);

    // Turn newlines into <br /> tags
	$message = nl2br($message);
}
else
{
    $hesk_error_buffer[] = $hesklang['enter_message'];
}

/* Attachments */
if ($hesk_settings['attachments']['use'])
{
    require(HESK_PATH . 'inc/attachments.inc.php');
    $attachments = array();
    for ($i=1;$i<=$hesk_settings['attachments']['max_number'];$i++)
    {
        $att = hesk_uploadFile($i);
        if ($att !== false && !empty($att))
        {
            $attachments[$i] = $att;
        }
    }
}
$myattachments='';

/* Time spent working on ticket */
$time_worked = hesk_getTime(hesk_POST('time_worked'));

/* Any errors? */
if (count($hesk_error_buffer)!=0)
{
    $_SESSION['ticket_message'] = hesk_POST('message');
    $_SESSION['time_worked'] = $time_worked;

	// Remove any successfully uploaded attachments
	if ($hesk_settings['attachments']['use'])
	{
		hesk_removeAttachments($attachments);
	}

    $tmp = '';
    foreach ($hesk_error_buffer as $error)
    {
        $tmp .= "<li>$error</li>\n";
    }
    $hesk_error_buffer = $tmp;

    $hesk_error_buffer = $hesklang['pcer'].'<br /><br /><ul>'.$hesk_error_buffer.'</ul>';
    hesk_process_messages($hesk_error_buffer,'admin_ticket.php?track='.$ticket['trackid'].'&Refresh='.rand(10000,99999));
}

if ($hesk_settings['attachments']['use'] && !empty($attachments))
{
    foreach ($attachments as $myatt)
    {
        hesk_dbQuery("INSERT INTO `".hesk_dbEscape($hesk_settings['db_pfix'])."attachments` (`ticket_id`,`saved_name`,`real_name`,`size`) VALUES ('".hesk_dbEscape($trackingID)."','".hesk_dbEscape($myatt['saved_name'])."','".hesk_dbEscape($myatt['real_name'])."','".intval($myatt['size'])."')");
        $myattachments .= hesk_dbInsertID() . '#' . $myatt['real_name'] .',';
    }
}

// Add reply
if ($submit_as_customer)
{
	hesk_dbQuery("INSERT INTO `".hesk_dbEscape($hesk_settings['db_pfix'])."replies` (`replyto`,`name`,`message`,`dt`,`attachments`) VALUES ('".intval($replyto)."','".hesk_dbEscape(addslashes($ticket['name']))."','".hesk_dbEscape($message."<br /><br /><i>{$hesklang['creb']} {$_SESSION['name']}</i>")."',NOW(),'".hesk_dbEscape($myattachments)."')");
}
else
{
	hesk_dbQuery("INSERT INTO `".hesk_dbEscape($hesk_settings['db_pfix'])."replies` (`replyto`,`name`,`message`,`dt`,`attachments`,`staffid`) VALUES ('".intval($replyto)."','".hesk_dbEscape(addslashes($_SESSION['name']))."','".hesk_dbEscape($message)."',NOW(),'".hesk_dbEscape($myattachments)."','".intval($_SESSION['id'])."')");
}

/* Track ticket status changes for history */
$revision = '';

/* Change the status of priority? */
if ( ! empty($_POST['set_priority']) )
{
    $priority = intval( hesk_POST('priority') );
    if ($priority < 0 || $priority > 3)
    {
    	hesk_error($hesklang['select_priority']);
    }

	$options = array(
		0 => $hesklang['critical'],
		1 => $hesklang['high'],
		2 => $hesklang['medium'],
		3 => $hesklang['low']
	);

    $revision = sprintf($hesklang['thist8'],hesk_date(),$options[$priority],$_SESSION['name'].' ('.$_SESSION['user'].')');

    $priority_sql = ",`priority`='$priority', `history`=CONCAT(`history`,'".hesk_dbEscape($revision)."') ";
}
else
{
    $priority_sql = "";
}

// Get new ticket status
$sql_status = '';
// -> If locked, keep it resolved
if ($ticket['locked'])
{
	$new_status = 3;
}
// -> Submit as: Resolved
elseif ( isset($_POST['submit_as_resolved']) && hesk_checkPermission('can_resolve', 0) )
{
	$new_status = 3;

	if ($ticket['status'] != $new_status)
	{
		$revision   = sprintf($hesklang['thist3'],hesk_date(),$_SESSION['name'].' ('.$_SESSION['user'].')');
		$sql_status = " , `closedat`=NOW(), `closedby`=".intval($_SESSION['id']).", `history`=CONCAT(`history`,'".hesk_dbEscape($revision)."') ";

		// Lock the ticket if customers are not allowed to reopen tickets
		if ($hesk_settings['custopen'] != 1)
		{
			$sql_status .= " , `locked`='1' ";
		}
	}
}
// -> Submit as: In Progress
elseif ( isset($_POST['submit_as_in_progress']) )
{
	$new_status = 4;

	if ($ticket['status'] != $new_status)
	{
		$revision   = sprintf($hesklang['thist9'],hesk_date(),$hesklang['in_progress'],$_SESSION['name'].' ('.$_SESSION['user'].')');
		$sql_status = " , `history`=CONCAT(`history`,'".hesk_dbEscape($revision)."') ";
	}
}
// -> Submit as: On Hold
elseif ( isset($_POST['submit_as_on_hold']) )
{
	$new_status = 5;

	if ($ticket['status'] != $new_status)
	{
		$revision   = sprintf($hesklang['thist9'],hesk_date(),$hesklang['on_hold'],$_SESSION['name'].' ('.$_SESSION['user'].')');
		$sql_status = " , `history`=CONCAT(`history`,'".hesk_dbEscape($revision)."') ";
	}
}
// -> Submit as Customer reply
elseif ($submit_as_customer)
{
	$new_status = 1;

	if ($ticket['status'] != $new_status)
	{
		$revision   = sprintf($hesklang['thist9'],hesk_date(),$hesklang['wait_reply'],$_SESSION['name'].' ('.$_SESSION['user'].')');
		$sql_status = " , `history`=CONCAT(`history`,'".hesk_dbEscape($revision)."') ";
	}
}
// -> Default: submit as "Replied by staff"
else
{
	$new_status = 2;
}

$sql = "UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` SET `status`='{$new_status}',";
$sql.= $submit_as_customer ? "`lastreplier`='0', `replierid`='0' " : "`lastreplier`='1', `replierid`='".intval($_SESSION['id'])."' ";

/* Update time_worked or force update lastchange */
if ($time_worked == '00:00:00')
{
	$sql .= ", `lastchange` = NOW() ";
}
else
{
    $parts = explode(':', $ticket['time_worked']);
    $seconds = ($parts[0] * 3600) + ($parts[1] * 60) + $parts[2];

    $parts = explode(':', $time_worked);
    $seconds += ($parts[0] * 3600) + ($parts[1] * 60) + $parts[2];

    require(HESK_PATH . 'inc/reporting_functions.inc.php');
    $ticket['time_worked'] = hesk_SecondsToHHMMSS($seconds);

	$sql .= ",`time_worked` = ADDTIME(`time_worked`,'" . hesk_dbEscape($time_worked) . "') ";
}

if ( ! empty($_POST['assign_self']) && hesk_checkPermission('can_assign_self',0))
{
	$revision = sprintf($hesklang['thist2'],hesk_date(),$_SESSION['name'].' ('.$_SESSION['user'].')',$_SESSION['name'].' ('.$_SESSION['user'].')');
    $sql .= " , `owner`=".intval($_SESSION['id']).", `history`=CONCAT(`history`,'".hesk_dbEscape($revision)."') ";
}

$sql .= " $priority_sql ";
$sql .= " $sql_status ";

// Is this the first staff reply? Log it for reporting
if ( ! $ticket['firstreplyby'] )
{
	$sql .= " , `firstreply`=NOW(), `firstreplyby`=".intval($_SESSION['id'])." ";
}

// Keep track of replies to this ticket for easier reporting
$sql .= " , `replies`=`replies`+1 ";
$sql .= $submit_as_customer ? '' : " , `staffreplies`=`staffreplies`+1 ";

// End and execute the query
$sql .= " WHERE `id`='{$replyto}'";
hesk_dbQuery($sql);
unset($sql);

/* Update number of replies in the users table */
hesk_dbQuery("UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."users` SET `replies`=`replies`+1 WHERE `id`='".intval($_SESSION['id'])."'");

// --> Prepare reply message

// 1. Generate the array with ticket info that can be used in emails
$info = array(
'email'			=> $ticket['email'],
'category'		=> $ticket['category'],
'priority'		=> $ticket['priority'],
'owner'			=> $ticket['owner'],
'trackid'		=> $ticket['trackid'],
'status'		=> $new_status,
'name'			=> $ticket['name'],
'subject'		=> $ticket['subject'],
'message'		=> stripslashes($message),
'attachments'	=> $myattachments,
'dt'			=> hesk_date($ticket['dt'], true),
'lastchange'	=> hesk_date($ticket['lastchange'], true),
'id'			=> $ticket['id'],
'language'		=> $ticket['language'],
'time_worked'   => $ticket['time_worked'],
'last_reply_by'	=> ($submit_as_customer ? $ticket['name'] : $_SESSION['name']),
);

// 2. Add custom fields to the array
foreach ($hesk_settings['custom_fields'] as $k => $v)
{
	$info[$k] = $v['use'] ? $ticket[$k] : '';
}

// 3. Make sure all values are properly formatted for email
$ticket = hesk_ticketToPlain($info, 1, 0);

// Notify the assigned staff?
if ($submit_as_customer)
{
	if ($ticket['owner'] && $ticket['owner'] != $_SESSION['id'])
	{
		hesk_notifyAssignedStaff(false, 'new_reply_by_customer', 'notify_reply_my');
	}
}
// Notify customer?
elseif ( ! isset($_POST['no_notify']) || intval( hesk_POST('no_notify') ) != 1)
{
	hesk_notifyCustomer('new_reply_by_staff');
}

// Delete any existing drafts from this owner for this ticket
hesk_dbQuery("DELETE FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."reply_drafts` WHERE `owner`=".intval($_SESSION['id'])." AND `ticket`=".intval($ticket['id']));

/* Set reply submitted message */
$_SESSION['HESK_SUCCESS'] = TRUE;
$_SESSION['HESK_MESSAGE'] = $hesklang['reply_submitted'];

/* What to do after reply? */
if ($_SESSION['afterreply'] == 1)
{
	header('Location: admin_main.php');
}
elseif ($_SESSION['afterreply'] == 2)
{
	/* Get the next open ticket that needs a reply */
    $res  = hesk_dbQuery("SELECT * FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` WHERE `owner` IN ('0','".intval($_SESSION['id'])."') AND " . hesk_myCategories() . " AND `status` IN ('0','1') ORDER BY `owner` DESC, `priority` ASC LIMIT 1");

    if (hesk_dbNumRows($res) == 1)
    {
    	$row = hesk_dbFetchAssoc($res);
        $_SESSION['HESK_MESSAGE'] .= '<br /><br />'.$hesklang['rssn'];
        header('Location: admin_ticket.php?track='.$row['trackid'].'&Refresh='.rand(10000,99999));
    }
    else
    {
		header('Location: admin_main.php');
    }
}
else
{
	header('Location: admin_ticket.php?track='.$ticket['trackid'].'&Refresh='.rand(10000,99999));
}
exit();
?>
