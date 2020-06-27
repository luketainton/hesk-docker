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
$can_del_notes		 = hesk_checkPermission('can_del_notes',0);
$can_reply			 = hesk_checkPermission('can_reply_tickets',0);
$can_delete			 = hesk_checkPermission('can_del_tickets',0);
$can_edit			 = hesk_checkPermission('can_edit_tickets',0);
$can_archive		 = hesk_checkPermission('can_add_archive',0);
$can_assign_self	 = hesk_checkPermission('can_assign_self',0);
$can_view_unassigned = hesk_checkPermission('can_view_unassigned',0);
$can_change_cat		 = hesk_checkPermission('can_change_cat',0);
$can_change_own_cat  = hesk_checkPermission('can_change_own_cat',0);
$can_ban_emails		 = hesk_checkPermission('can_ban_emails', 0);
$can_unban_emails	 = hesk_checkPermission('can_unban_emails', 0);
$can_ban_ips		 = hesk_checkPermission('can_ban_ips', 0);
$can_unban_ips		 = hesk_checkPermission('can_unban_ips', 0);
$can_resolve		 = hesk_checkPermission('can_resolve', 0);
$can_view_ass_by     = hesk_checkPermission('can_view_ass_by', 0);
$can_privacy		 = hesk_checkPermission('can_privacy',0);
$can_export          = hesk_checkPermission('can_export',0);

// Get ticket ID
$trackingID = hesk_cleanID() or print_form();

// Load custom fields
require_once(HESK_PATH . 'inc/custom_fields.inc.php');

// Load statuses
require_once(HESK_PATH . 'inc/statuses.inc.php');

$_SERVER['PHP_SELF'] = 'admin_ticket.php?track='.$trackingID.'&Refresh='.mt_rand(10000,99999);

// We will need some extra functions
define('TIMER',1);
define('BACK2TOP',1);
if ($hesk_settings['time_display']) {
    define('TIMEAGO',1);
}

/* Get ticket info */
$res = hesk_dbQuery("SELECT `t1`.* , `t2`.name AS `repliername` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` AS `t1` LEFT JOIN `".hesk_dbEscape($hesk_settings['db_pfix'])."users` AS `t2` ON `t1`.`replierid` = `t2`.`id` WHERE `trackid`='".hesk_dbEscape($trackingID)."' LIMIT 1");

/* Ticket found? */
if (hesk_dbNumRows($res) != 1)
{
	/* Ticket not found, perhaps it was merged with another ticket? */
	$res = hesk_dbQuery("SELECT * FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` WHERE `merged` LIKE '%#".hesk_dbEscape($trackingID)."#%' LIMIT 1");

	if (hesk_dbNumRows($res) == 1)
	{
    	/* OK, found in a merged ticket. Get info */
     	$ticket = hesk_dbFetchAssoc($res);
        hesk_process_messages( sprintf($hesklang['tme'], $trackingID, $ticket['trackid']) ,'NOREDIRECT','NOTICE');
        $trackingID = $ticket['trackid'];
	}
    else
    {
    	/* Nothing found, error out */
	    hesk_process_messages($hesklang['ticket_not_found'],'NOREDIRECT');
	    print_form();
    }
}
else
{
	/* We have a match, get ticket info */
	$ticket = hesk_dbFetchAssoc($res);
}

/* Permission to view this ticket? */
if ($ticket['owner'] && $ticket['owner'] != $_SESSION['id'] && ! hesk_checkPermission('can_view_ass_others',0))
{
    // Maybe this user is allowed to view tickets he/she assigned?
    if ( ! $can_view_ass_by || $ticket['assignedby'] != $_SESSION['id'])
    {
        hesk_error($hesklang['ycvtao']);
    }
}

if (!$ticket['owner'] && ! $can_view_unassigned)
{
	hesk_error($hesklang['ycovtay']);
}

/* Set last replier name */
if ($ticket['lastreplier'])
{
	if (empty($ticket['repliername']))
	{
		$ticket['repliername'] = $hesklang['staff'];
	}
}
else
{
	$ticket['repliername'] = $ticket['name'];
}

/* Get category name and ID */
$result = hesk_dbQuery("SELECT `id`, `name` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."categories` WHERE `id`='".intval($ticket['category'])."' LIMIT 1");

/* If this category has been deleted use the default category with ID 1 */
if (hesk_dbNumRows($result) != 1)
{
	$result = hesk_dbQuery("SELECT `id`, `name` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."categories` WHERE `id`='1' LIMIT 1");
}

$category = hesk_dbFetchAssoc($result);

/* Is this user allowed to view tickets inside this category? */
hesk_okCategory($category['id']);

/* Delete post action */
if (isset($_GET['delete_post']) && $can_delete && hesk_token_check())
{
	$n = intval( hesk_GET('delete_post') );
    if ($n)
    {
		/* Get last reply ID, we'll need it later */
		$res = hesk_dbQuery("SELECT `id` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."replies` WHERE `replyto`='".intval($ticket['id'])."' ORDER BY `id` DESC LIMIT 1");
        $last_reply_id = hesk_dbResult($res,0,0);

		// Was this post submitted by staff and does it have any attachments?
		$res = hesk_dbQuery("SELECT `dt`, `staffid`, `attachments` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."replies` WHERE `id`='".intval($n)."' AND `replyto`='".intval($ticket['id'])."' LIMIT 1");
		$reply = hesk_dbFetchAssoc($res);

		// If the reply was by a staff member update the appropriate columns
		if ( $reply['staffid'] )
		{
			// Is this the only staff reply? Delete "firstreply" and "firstreplyby" columns
			if ($ticket['staffreplies'] <= 1)
			{
				$staffreplies_sql = ' , `firstreply`=NULL, `firstreplyby`=NULL, `staffreplies`=0 ';
			}
			// Are we deleting the first staff reply? Update "firstreply" and "firstreplyby" columns
			elseif ($reply['dt'] == $ticket['firstreply'] && $reply['staffid'] == $ticket['firstreplyby'])
			{
				// Get the new first reply info
				$res = hesk_dbQuery("SELECT `dt`, `staffid` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."replies` WHERE `replyto`='".intval($ticket['id'])."' AND `id`!='".intval($n)."' AND `staffid`!=0 ORDER BY `id` ASC LIMIT 1");

				// Did we find the new first reply?
				if ( hesk_dbNumRows($res) )
				{
					$firstreply = hesk_dbFetchAssoc($res);
					$staffreplies_sql = " , `firstreply`='".hesk_dbEscape($firstreply['dt'])."', `firstreplyby`='".hesk_dbEscape($firstreply['staffid'])."', `staffreplies`=`staffreplies`-1 ";
				}
				// The count must have been wrong, update it
				else
				{
					$staffreplies_sql = ' , `firstreply`=NULL, `firstreplyby`=NULL, `staffreplies`=0 ';
				}
			}
			// OK, this is not the first and not the only staff reply, just reduce number
			else
			{
            	$staffreplies_sql = ' , `staffreplies`=`staffreplies`-1 ';
			}
		}
		else
		{
			$staffreplies_sql = '';
		}

		/* Delete any attachments to this post */
		if ( strlen($reply['attachments']) )
		{
        	$hesk_settings['server_path'] = dirname(dirname(__FILE__));

			/* List of attachments */
			$att=explode(',',substr($reply['attachments'], 0, -1));
			foreach ($att as $myatt)
			{
				list($att_id, $att_name) = explode('#', $myatt);

				/* Delete attachment files */
				$res = hesk_dbQuery("SELECT * FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."attachments` WHERE `att_id`='".intval($att_id)."' LIMIT 1");
				if (hesk_dbNumRows($res) && $file = hesk_dbFetchAssoc($res))
				{
					hesk_unlink($hesk_settings['server_path'].'/'.$hesk_settings['attach_dir'].'/'.$file['saved_name']);
				}

				/* Delete attachments info from the database */
				hesk_dbQuery("DELETE FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."attachments` WHERE `att_id`='".intval($att_id)."'");
			}
		}

		/* Delete this reply */
		hesk_dbQuery("DELETE FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."replies` WHERE `id`='".intval($n)."' AND `replyto`='".intval($ticket['id'])."'");

        /* Reply wasn't deleted */
        if (hesk_dbAffectedRows() != 1)
        {
			hesk_process_messages($hesklang['repl1'],$_SERVER['PHP_SELF']);
        }
        else
        {
			$closed_sql = '';

			/* Reply deleted. Need to update status and last replier? */
			$res = hesk_dbQuery("SELECT `dt`, `staffid` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."replies` WHERE `replyto`='".intval($ticket['id'])."' ORDER BY `id` DESC LIMIT 1");
			if (hesk_dbNumRows($res))
			{
				$replier_id = hesk_dbResult($res,0,1);
                $last_replier = $replier_id ? 1 : 0;

				/* Change status? */
                $status_sql = '';
				if ($last_reply_id == $n)
				{
					$status = $ticket['locked'] ? 3 : ($last_replier ? 2 : 1);
                    $status_sql = " , `status`='".intval($status)."' ";

					// Update closedat and closedby columns as required
					if ($status == 3)
					{
						$closed_sql = " , `closedat`=NOW(), `closedby`=".intval($_SESSION['id'])." ";
					}
				}

				hesk_dbQuery("UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` SET `lastchange`=NOW(), `lastreplier`='{$last_replier}', `replierid`='".intval($replier_id)."', `replies`=`replies`-1 $status_sql $closed_sql $staffreplies_sql WHERE `id`='".intval($ticket['id'])."'");
			}
			else
			{
				// Update status, closedat and closedby columns as required
				if ($ticket['locked'])
				{
					$status = 3;
					$closed_sql = " , `closedat`=NOW(), `closedby`=".intval($_SESSION['id'])." ";
				}
				else
				{
                	$status = 0;
					$closed_sql = " , `closedat`=NULL, `closedby`=NULL ";
				}

				hesk_dbQuery("UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` SET `lastchange`=NOW(), `lastreplier`='0', `status`='$status', `replies`=0 $staffreplies_sql WHERE `id`='".intval($ticket['id'])."'");
			}

			hesk_process_messages($hesklang['repl'],$_SERVER['PHP_SELF'],'SUCCESS');
        }
    }
    else
    {
    	hesk_process_messages($hesklang['repl0'],$_SERVER['PHP_SELF']);
    }
}

/* Delete notes action */
if (isset($_GET['delnote']) && hesk_token_check())
{
	$n = intval( hesk_GET('delnote') );
    if ($n)
    {
		// Get note info
		$res = hesk_dbQuery("SELECT `who`, `attachments` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."notes` WHERE `id`={$n}");

		if ( hesk_dbNumRows($res) )
		{
			$note = hesk_dbFetchAssoc($res);

			// Permission to delete note?
			if ($can_del_notes || $note['who'] == $_SESSION['id'])
			{
				// Delete note
				hesk_dbQuery("DELETE FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."notes` WHERE `id`='".intval($n)."'");

			    // Delete attachments
				if ( strlen($note['attachments']) )
				{
					$hesk_settings['server_path'] = dirname(dirname(__FILE__));

		            $attachments = array();

					$att=explode(',',substr($note['attachments'], 0, -1));
					foreach ($att as $myatt)
					{
						list($att_id, $att_name) = explode('#', $myatt);
						$attachments[] = intval($att_id);
					}

					if ( count($attachments) )
					{
						$res = hesk_dbQuery("SELECT * FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."attachments` WHERE `att_id` IN (".implode(',', $attachments).") ");
						while ($file = hesk_dbFetchAssoc($res))
						{
							hesk_unlink($hesk_settings['server_path'].'/'.$hesk_settings['attach_dir'].'/'.$file['saved_name']);
						}
						hesk_dbQuery("DELETE FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."attachments` WHERE `att_id` IN (".implode(',', $attachments).") ");
					}
				}
			}
		}
	}

    header('Location: admin_ticket.php?track='.$trackingID.'&Refresh='.mt_rand(10000,99999));
    exit();
}

/* Add a note action */
if (isset($_POST['notemsg']) && hesk_token_check('POST'))
{
	// Error buffer
	$hesk_error_buffer = array();

	// Get message
	$msg = hesk_input( hesk_POST('notemsg') );

	// Get attachments
	if ($hesk_settings['attachments']['use'])
	{
		require(HESK_PATH . 'inc/posting_functions.inc.php');
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

	// We need message and/or attachments to accept note
	if ( count($attachments) || strlen($msg) || count($hesk_error_buffer) )
	{
		// Any errors?
		if ( count($hesk_error_buffer) != 0 )
		{
			$_SESSION['note_message'] = hesk_POST('notemsg');

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

		// Process attachments
		if ($hesk_settings['attachments']['use'] && ! empty($attachments) )
		{
			foreach ($attachments as $myatt)
			{
				hesk_dbQuery("INSERT INTO `".hesk_dbEscape($hesk_settings['db_pfix'])."attachments` (`ticket_id`,`saved_name`,`real_name`,`size`,`type`) VALUES ('".hesk_dbEscape($trackingID)."','".hesk_dbEscape($myatt['saved_name'])."','".hesk_dbEscape($myatt['real_name'])."','".intval($myatt['size'])."', '1')");
				$myattachments .= hesk_dbInsertID() . '#' . $myatt['real_name'] .',';
			}
		}

		// Add note to database
		$msg = nl2br(hesk_makeURL($msg));
		hesk_dbQuery("INSERT INTO `".hesk_dbEscape($hesk_settings['db_pfix'])."notes` (`ticket`,`who`,`dt`,`message`,`attachments`) VALUES ('".intval($ticket['id'])."','".intval($_SESSION['id'])."',NOW(),'".hesk_dbEscape($msg)."','".hesk_dbEscape($myattachments)."')");

        /* Notify assigned staff that a note has been added if needed */
        if ($ticket['owner'] && $ticket['owner'] != $_SESSION['id'])
        {
			$res = hesk_dbQuery("SELECT `email`, `notify_note` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."users` WHERE `id`='".intval($ticket['owner'])."' LIMIT 1");

			if (hesk_dbNumRows($res) == 1)
			{
				$owner = hesk_dbFetchAssoc($res);

				// 1. Generate the array with ticket info that can be used in emails
				$info = array(
				'email'			=> $ticket['email'],
				'category'		=> $ticket['category'],
				'priority'		=> $ticket['priority'],
				'owner'			=> $ticket['owner'],
				'trackid'		=> $ticket['trackid'],
				'status'		=> $ticket['status'],
				'name'			=> $_SESSION['name'],
				'subject'		=> $ticket['subject'],
				'message'		=> stripslashes($msg),
				'dt'			=> hesk_date($ticket['dt'], true),
				'lastchange'	=> hesk_date($ticket['lastchange'], true),
				'attachments'	=> $myattachments,
				'id'			=> $ticket['id'],
                'time_worked'   => $ticket['time_worked'],
                'last_reply_by' => $ticket['repliername'],
				);

				// 2. Add custom fields to the array
				foreach ($hesk_settings['custom_fields'] as $k => $v)
				{
					$info[$k] = $v['use'] ? $ticket[$k] : '';
				}

				// 3. Make sure all values are properly formatted for email
				$ticket = hesk_ticketToPlain($info, 1, 0);

				/* Get email functions */
				require(HESK_PATH . 'inc/email_functions.inc.php');

				/* Format email subject and message for staff */
				$subject = hesk_getEmailSubject('new_note',$ticket);
				$message = hesk_getEmailMessage('new_note',$ticket,1);

				/* Send email to staff */
				hesk_mail($owner['email'], $subject, $message);
			}
        }
	}

	header('Location: admin_ticket.php?track='.$trackingID.'&Refresh='.mt_rand(10000,99999));
	exit();
}

/* Update time worked */
if ($hesk_settings['time_worked'] && ($can_reply || $can_edit) && isset($_POST['h']) && isset($_POST['m']) && isset($_POST['s']) && hesk_token_check('POST'))
{
	$h = intval( hesk_POST('h') );
	$m = intval( hesk_POST('m') );
	$s = intval( hesk_POST('s') );

	/* Get time worked in proper format */
    $time_worked = hesk_getTime($h . ':' . $m . ':' . $s);

	/* Update database */
    $revision = sprintf($hesklang['thist14'],hesk_date(),$time_worked,$_SESSION['name'].' ('.$_SESSION['user'].')');
	hesk_dbQuery("UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` SET `time_worked`='" . hesk_dbEscape($time_worked) . "', `history`=CONCAT(`history`,'" . hesk_dbEscape($revision) . "') WHERE `trackid`='" . hesk_dbEscape($trackingID) . "'");

	/* Show ticket */
	hesk_process_messages($hesklang['twu'],'admin_ticket.php?track='.$trackingID.'&Refresh='.mt_rand(10000,99999),'SUCCESS');
}

/* Delete attachment action */
if (isset($_GET['delatt']) && hesk_token_check())
{
	if ( ! $can_delete || ! $can_edit)
    {
		hesk_process_messages($hesklang['no_permission'],'admin_ticket.php?track='.$trackingID.'&Refresh='.mt_rand(10000,99999));
    }

	$att_id = intval( hesk_GET('delatt') ) or hesk_error($hesklang['inv_att_id']);

	$reply = intval( hesk_GET('reply', 0) );
	if ($reply < 1)
	{
		$reply = 0;
	}

	$note = intval( hesk_GET('note', 0) );
	if ($note < 1)
	{
		$note = 0;
	}

	/* Get attachment info */
	$res = hesk_dbQuery("SELECT * FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."attachments` WHERE `att_id`='".intval($att_id)."' LIMIT 1");
	if (hesk_dbNumRows($res) != 1)
	{
		hesk_process_messages($hesklang['id_not_valid'].' (att_id)','admin_ticket.php?track='.$trackingID.'&Refresh='.mt_rand(10000,99999));
	}
	$att = hesk_dbFetchAssoc($res);

	/* Is ticket ID valid for this attachment? */
	if ($att['ticket_id'] != $trackingID)
	{
		hesk_process_messages($hesklang['trackID_not_found'],'admin_ticket.php?track='.$trackingID.'&Refresh='.mt_rand(10000,99999));
	}

	/* Delete file from server */
	hesk_unlink(HESK_PATH.$hesk_settings['attach_dir'].'/'.$att['saved_name']);

	/* Delete attachment from database */
	hesk_dbQuery("DELETE FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."attachments` WHERE `att_id`='".intval($att_id)."'");

	/* Update ticket or reply in the database */
    $revision = sprintf($hesklang['thist12'],hesk_date(),$att['real_name'],$_SESSION['name'].' ('.$_SESSION['user'].')');
	if ($reply)
	{
		hesk_dbQuery("UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."replies` SET `attachments`=REPLACE(`attachments`,'".hesk_dbEscape($att_id.'#'.$att['real_name']).",','') WHERE `id`='".intval($reply)."'");
		hesk_dbQuery("UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` SET `history`=CONCAT(`history`,'".hesk_dbEscape($revision)."') WHERE `id`='".intval($ticket['id'])."'");
	}
	elseif ($note)
	{
		hesk_dbQuery("UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."notes` SET `attachments`=REPLACE(`attachments`,'".hesk_dbEscape($att_id.'#'.$att['real_name']).",','') WHERE `id`={$note}");
	}
	else
	{
		hesk_dbQuery("UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` SET `attachments`=REPLACE(`attachments`,'".hesk_dbEscape($att_id.'#'.$att['real_name']).",',''), `history`=CONCAT(`history`,'".hesk_dbEscape($revision)."') WHERE `id`='".intval($ticket['id'])."'");
	}

	hesk_process_messages($hesklang['kb_att_rem'],'admin_ticket.php?track='.$trackingID.'&Refresh='.mt_rand(10000,99999),'SUCCESS');
}

/* Print header */
require_once(HESK_PATH . 'inc/header.inc.php');

/* List of categories */
if ($can_change_cat)
{
    $result = hesk_dbQuery("SELECT `id`,`name` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."categories` ORDER BY `cat_order` ASC");
}
else
{
    $result = hesk_dbQuery("SELECT `id`,`name` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."categories` WHERE ".hesk_myCategories('id')." ORDER BY `cat_order` ASC");
}
$categories_options='';
while ($row=hesk_dbFetchAssoc($result))
{
    $categories_options.='<option value="'.$row['id'].'" '.($row['id'] == $ticket['category'] ? 'selected' : '').'>'.$row['name'].'</option>';
}

/* List of users */
$admins = array();
$result = hesk_dbQuery("SELECT `id`,`name`,`isadmin`,`categories`,`heskprivileges` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."users` ORDER BY `name` ASC");
while ($row=hesk_dbFetchAssoc($result))
{
	/* Is this an administrator? */
	if ($row['isadmin'])
    {
	    $admins[$row['id']]=$row['name'];
	    continue;
    }

	/* Not admin, is user allowed to view tickets? */
	if (strpos($row['heskprivileges'], 'can_view_tickets') !== false)
	{
		/* Is user allowed to access this category? */
		$cat=substr($row['categories'], 0);
		$row['categories']=explode(',',$cat);
		if (in_array($ticket['category'],$row['categories']))
		{
			$admins[$row['id']]=$row['name'];
			continue;
		}
	}
}

/* Get replies */
if ($ticket['replies'])
{
	$reply = '';
	$result = hesk_dbQuery("SELECT * FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."replies` WHERE `replyto`='".intval($ticket['id'])."' ORDER BY `id` " . ($hesk_settings['new_top'] ? 'DESC' : 'ASC') );
}
else
{
	$reply = false;
}

// Demo mode
if ( defined('HESK_DEMO') )
{
	$ticket['email'] = 'hidden@demo.com';
	$ticket['ip']	 = '127.0.0.1';
}

/* Print admin navigation */
require_once(HESK_PATH . 'inc/show_admin_nav.inc.php');

/* This will handle error, success and notice messages */
hesk_handle_messages();

// Prepare special custom fields
foreach ($hesk_settings['custom_fields'] as $k=>$v)
{
	if ($v['use'] && hesk_is_custom_field_in_category($k, $ticket['category']) )
	{
		switch ($v['type'])
		{
			case 'date':
				$ticket[$k] = hesk_custom_date_display_format($ticket[$k], $v['value']['date_format']);
				break;
		}
	}
}

/* Do we need or have any canned responses? */
$can_options = hesk_printCanned();

$options = array(
    0 => '<option value="0" '.($ticket['priority'] == 0 ? 'selected' : '').'>'.$hesklang['critical'].'</option>',
    1 => '<option value="1" '.($ticket['priority'] == 1 ? 'selected' : '').'>'.$hesklang['high'].'</option>',
    2 => '<option value="2" '.($ticket['priority'] == 2 ? 'selected' : '').'>'.$hesklang['medium'].'</option>',
    3 => '<option value="3" '.($ticket['priority'] == 3 ? 'selected' : '').'>'.$hesklang['low'].'</option>'
);
?>
<div class="main__content ticket">
    <div class="ticket__body" <?php echo ($hesk_settings['limit_width'] ? 'style="max-width:'.$hesk_settings['limit_width'].'px"' : ''); ?>>
        <?php
        /* Reply form on top? */
        if ($can_reply && $hesk_settings['reply_top'] == 1)
        {
            hesk_printReplyForm();
        }

        if ($hesk_settings['new_top'])
        {
            $i = hesk_printTicketReplies() ? 0 : 1;
        }
        else
        {
            $i = 1;
        }
        ?>
        <article class="ticket__body_block original-message">
            <h3>
                <?php if ($ticket['archive']): ?>
                    <div class="tooltype right out-close">
                        <svg class="icon icon-tag">
                            <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-tag"></use>
                        </svg>
                        <div class="tooltype__content">
                            <div class="tooltype__wrapper">
                                <?php echo $hesklang['archived']; ?>
                            </div>
                        </div>
                    </div>
                <?php
                endif;
                if ($ticket['locked']):
                ?>
                    <div class="tooltype right out-close">
                        <svg class="icon icon-lock">
                            <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-lock"></use>
                        </svg>
                        <div class="tooltype__content">
                            <div class="tooltype__wrapper">
                                <?php echo $hesklang['loc'].' - '.$hesklang['isloc']; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                <?php echo $ticket['subject']; ?>
            </h3>
            <div class="block--head">
                <div class="contact">
                    <span><?php echo $hesklang['contact']; ?>:</span>
                    <div class="dropdown left out-close">
                        <label>
                            <span><?php echo $ticket['name']; ?></span>
                            <svg class="icon icon-chevron-down">
                                <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-chevron-down"></use>
                            </svg>
                        </label>
                        <ul class="dropdown-list">
                            <?php
                            if ($ticket['email'] != '')
                            {
                                ?>
                                <li class="noclose">
                                    <span class="title"><?php echo $hesklang['email']; ?>:</span>
                                    <span class="value"><a href="mailto:<?php echo $ticket['email']; ?>"><?php echo $ticket['email']; ?></a></span>
                                </li>
                                <?php
                            }
                            ?>
                            <li class="noclose">
                                <span class="title"><?php echo $hesklang['ip']; ?>:</span>
                                <span class="value">
                                    <?php
                                    if ($ticket['ip'] == '' || $ticket['ip'] == 'Unknown' || $ticket['ip'] == $hesklang['unknown']) {
                                        echo $hesklang['unknown'];
                                    } else {
                                    ?>
                                        <a href="../ip_whois.php?ip=<?php echo urlencode($ticket['ip']); ?>"><?php echo $ticket['ip']; ?></a>
                                    <?php } ?>
                                </span>
                            </li>
                            <li class="separator"></li>
                            <?php
                            if ($ticket['email'] != '' && $can_ban_emails) {
                                echo '<li>';
                                if ( $email_id = hesk_isBannedEmail($ticket['email']) ) {
                                    if ($can_unban_emails) {
                                        echo '
                                            <svg class="icon icon-eye-close">
                                                <use xlink:href="../img/sprite.svg#icon-eye-close"></use>
                                            </svg>
                                            <a href="banned_emails.php?a=unban&amp;track='.$trackingID.'&amp;id='.intval($email_id).'&amp;token='.hesk_token_echo(0).'">'.$hesklang['unban_email'].'</a>
                                        ';
                                    } else {
                                        echo $hesklang['eisban'];
                                    }
                                } else {
                                    echo '
                                        <svg class="icon icon-eye-open">
                                            <use xlink:href="../img/sprite.svg#icon-eye-open"></use>
                                        </svg>
                                        <a href="banned_emails.php?a=ban&amp;track='.$trackingID.'&amp;email='.urlencode($ticket['email']).'&amp;token='.hesk_token_echo(0).'">'.$hesklang['savebanemail'].'</a>
                                    ';
                                }
                                echo '</li>';
                            }

                            // Format IP for lookup
                            if ($ticket['ip'] != '' && $ticket['ip'] != 'Unknown' && $ticket['ip'] != $hesklang['unknown']) {
                                echo '<li>';
                                if ($can_ban_ips) {
                                    if ( $ip_id = hesk_isBannedIP($ticket['ip']) ) {
                                        if ($can_unban_ips) {
                                            echo '
                                                <svg class="icon icon-eye-close">
                                                    <use xlink:href="../img/sprite.svg#icon-eye-close"></use>
                                                </svg>
                                                <a href="banned_ips.php?a=unban&amp;track='.$trackingID.'&amp;id='.intval($ip_id).'&amp;token='.hesk_token_echo(0).'">'.$hesklang['unban_ip'].'</a>
                                            ';
                                        } else {
                                            echo $hesklang['ipisban'];
                                        }
                                    } else {
                                        echo '
                                            <svg class="icon icon-eye-open">
                                                <use xlink:href="../img/sprite.svg#icon-eye-open"></use>
                                            </svg>
                                            <a href="banned_ips.php?a=ban&amp;track='.$trackingID.'&amp;ip='.urlencode($ticket['ip']).'&amp;token='.hesk_token_echo(0).'">'.$hesklang['savebanip'].'</a>
                                        ';
                                    }
                                }
                                echo '</li>';
                            }
                            ?>
                        </ul>
                    </div>
                </div>
                <time class="timeago tooltip" datetime="<?php echo date("c", strtotime($ticket['dt'])) ; ?>" title="<?php echo hesk_date($ticket['dt'], true); ?>"><?php echo hesk_date($ticket['dt'], true); ?></time>
            </div>
            <?php
            foreach ($hesk_settings['custom_fields'] as $k=>$v)
            {
                if ($v['use'] && $v['place']==0 && hesk_is_custom_field_in_category($k, $ticket['category']) )
                {

                    switch ($v['type'])
                    {
                        case 'email':
                            $ticket[$k] = '<a href="mailto:'.$ticket[$k].'">'.$ticket[$k].'</a>';
                            break;
                    }

                    echo '
					<div>
                        <span style="color: #959eb0">'.$v['name:'].'</span>
                        <span>'.$ticket[$k].'</span>
					</div>';
                }
            }

            if ($ticket['message'] != '')
            {
                ?>
                <div class="block--description">
                    <p><?php echo $ticket['message']; ?></p>
                </div>
                <?php
            }

            /* custom fields after message */
            foreach ($hesk_settings['custom_fields'] as $k=>$v)
            {
                if ($v['use'] && $v['place'] && hesk_is_custom_field_in_category($k, $ticket['category']) )
                {
                    switch ($v['type'])
                    {
                        case 'email':
                            $ticket[$k] = '<a href="mailto:'.$ticket[$k].'">'.$ticket[$k].'</a>';
                            break;
                    }

                    echo '
					<div>
                        <span style="color: #959eb0">'.$v['name:'].'</span>
                        <span>'.$ticket[$k].'</span>
					</div>';
                }
            }

            /* Print attachments */
            hesk_listAttachments($ticket['attachments'], 0 , $i);

            // Show suggested KB articles
            if ($hesk_settings['kb_enable'] && $hesk_settings['kb_recommendanswers'] && strlen($ticket['articles']) )
            {
                $suggested = array();
                $suggested_list = '';

                // Get article info from the database
                $articles = hesk_dbQuery("SELECT `id`,`subject` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_articles` WHERE `id` IN (".preg_replace('/[^0-9\,]/', '', $ticket['articles']).")");
                while ($article=hesk_dbFetchAssoc($articles))
                {
                    $suggested[$article['id']] = '<a href="../knowledgebase.php?article='.$article['id'].'">'.$article['subject'].'</a>';
                }

                // Loop through the IDs to preserve the order they were suggested in
                $articles = explode(',', $ticket['articles']);
                foreach ($articles as $article)
                {
                    if ( isset($suggested[$article]) )
                    {
                        $suggested_list .= $suggested[$article];
                    }
                }

                // Finally print suggested articles
                if ( strlen($suggested_list) )
                {
                    ?>
                    <div class="block--suggested">
                        <b><?php echo $hesklang['taws']; ?></b>
                        <?php
                        if ($_SESSION['show_suggested']){
                            echo $suggested_list;
                        } else {
                            echo '<a href="Javascript:void(0)" onclick="hesk_toggleLayerDisplay(\'suggested_articles\')">'.$hesklang['sska'].'</a>
                                        <span id="suggested_articles" style="display:none">'.$suggested_list.'</span>';
                        }
                        ?>
                    </div>
                    <?php
                }
            }
            ?>

            <div class="block--notes">
                <?php
                $res = hesk_dbQuery("SELECT t1.*, t2.`name` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."notes` AS t1 LEFT JOIN `".hesk_dbEscape($hesk_settings['db_pfix'])."users` AS t2 ON t1.`who` = t2.`id` WHERE `ticket`='".intval($ticket['id'])."' ORDER BY t1.`id` " . ($hesk_settings['new_top'] ? 'DESC' : 'ASC') );
                while ($note = hesk_dbFetchAssoc($res)) {
                    ?>
                    <div class="note">
                        <div class="note__head">
                            <div class="name">
                                <?php echo $hesklang['noteby']; ?>
                                <b><?php echo ($note['name'] ? $note['name'] : $hesklang['e_udel']); ?></b>
                                &raquo;
                                <time class="timeago tooltip" datetime="<?php echo date("c", strtotime($note['dt'])) ; ?>" title="<?php echo hesk_date($note['dt'], true); ?>"><?php echo hesk_date($note['dt'], true); ?></time>
                            </div>
                            <?php
                            if ($can_del_notes || $note['who'] == $_SESSION['id'])
                            {
                            ?>
                            <div class="actions">
                                <a class="tooltip" href="edit_note.php?track=<?php echo $trackingID; ?>&amp;Refresh=<?php echo mt_rand(10000,99999); ?>&amp;note=<?php echo $note['id']; ?>&amp;token=<?php hesk_token_echo(); ?>" title="<?php echo $hesklang['ednote']; ?>">
                                    <svg class="icon icon-edit-ticket">
                                        <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-edit-ticket"></use>
                                    </svg>
                                </a>
                                <a class="tooltip" href="admin_ticket.php?track=<?php echo $trackingID; ?>&amp;Refresh=<?php echo mt_rand(10000,99999); ?>&amp;delnote=<?php echo $note['id']; ?>&amp;token=<?php hesk_token_echo(); ?>" onclick="return hesk_confirmExecute('<?php echo hesk_makeJsString($hesklang['delnote']).'?'; ?>');" title="<?php echo $hesklang['delnote']; ?>">
                                    <svg class="icon icon-delete">
                                        <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-delete"></use>
                                    </svg>
                                </a>
                            </div>
                            <?php } ?>
                        </div>
                        <div class="note__description">
                            <p><?php echo $note['message']; ?></p>
                        </div>
                        <div class="note__attachments" style="color: #9c9c9c;">
                            <?php
                            // Attachments
                            if ( $hesk_settings['attachments']['use'] && strlen($note['attachments']) )
                            {
                                echo strlen($note['message']) ? '<br>' : '';

                                $att = explode(',', substr($note['attachments'], 0, -1) );
                                $num = count($att);
                                foreach ($att as $myatt)
                                {
                                    list($att_id, $att_name) = explode('#', $myatt);

                                    // Can edit and delete note (attachments)?
                                    if ($can_del_notes || $note['who'] == $_SESSION['id'])
                                    {
                                        // If this is the last attachment and no message, show "delete ticket" link
                                        if ($num == 1 && strlen($note['message']) == 0)
                                        {
                                            echo '<a class="tooltip" data-ztt_vertical_offset="0" style="margin-right: 8px;" href="admin_ticket.php?delnote='.$note['id'].'&amp;track='.$trackingID.'&amp;Refresh='.mt_rand(10000,99999).'&amp;token='.hesk_token_echo(0).'" onclick="return hesk_confirmExecute(\''.hesk_makeJsString($hesklang['pda']).'\');" title="'.$hesklang['dela'].'">
                                                    <svg class="icon icon-delete" style="text-decoration: none; vertical-align: text-bottom;">
                                                        <use xlink:href="'. HESK_PATH .'img/sprite.svg#icon-delete"></use>
                                                    </svg>                                            
                                                </a> &raquo;';
                                        }
                                        // Show "delete attachment" link
                                        else
                                        {
                                            echo '<a class="tooltip" data-ztt_vertical_offset="0" style="margin-right: 8px;" href="admin_ticket.php?delatt='.$att_id.'&amp;note='.$note['id'].'&amp;track='.$trackingID.'&amp;Refresh='.mt_rand(10000,99999).'&amp;token='.hesk_token_echo(0).'" onclick="return hesk_confirmExecute(\''.hesk_makeJsString($hesklang['pda']).'\');" title="'.$hesklang['dela'].'">
                                                    <svg class="icon icon-delete" style="vertical-align: text-bottom;">
                                                        <use xlink:href="'. HESK_PATH .'img/sprite.svg#icon-delete"></use>
                                                    </svg>
                                                </a> &raquo;';
                                        }
                                    }

                                    echo '
				<a href="../download_attachment.php?att_id='.$att_id.'&amp;track='.$trackingID.'" title="'.$hesklang['dnl'].' '.$att_name.'">
				    <svg class="icon icon-attach" style="vertical-align: text-bottom;">
                        <use xlink:href="'. HESK_PATH .'img/sprite.svg#icon-attach"></use>
                    </svg>
                </a>
				<a class="underline" href="../download_attachment.php?att_id='.$att_id.'&amp;track='.$trackingID.'" title="'.$hesklang['dnl'].' '.$att_name.'">'.$att_name.'</a><br>
				';
                                }
                            }
                            ?>
                        </div>
                    </div>
                    <?php
                }
                ?>
                <div id="notesform" style="display:<?php echo isset($_SESSION['note_message']) ? 'block' : 'none'; ?>; margin-top: 20px">
                    <form method="post" action="admin_ticket.php" class="form" enctype="multipart/form-data">
                        <textarea class="form-control" name="notemsg" rows="6" cols="60" style="height: auto; resize: vertical; transition: none;"><?php echo isset($_SESSION['note_message']) ? stripslashes(hesk_input($_SESSION['note_message'])) : ''; ?></textarea>
                        <?php
                        // attachments
                        if ($hesk_settings['attachments']['use'])
                        {
                            echo '<br>';
                            for ($i=1;$i<=$hesk_settings['attachments']['max_number'];$i++)
                            {
                                echo '<input type="file" name="attachment['.$i.']" size="50"><br>';
                            }
                            echo '<br>';
                        }
                        ?>
                        <button type="submit" class="btn btn-full">
                            <?php echo $hesklang['s']; ?>
                        </button>
                        <input type="hidden" name="track" value="<?php echo $trackingID; ?>">
                        <i><?php echo $hesklang['nhid']; ?></i>
                        <input type="hidden" name="token" value="<?php hesk_token_echo(); ?>">
                    </form>
                </div>
                <button class="btn btn--blue-border" type="button" onclick="hesk_toggleLayerDisplay('notesform')">
                    <svg class="icon icon-note">
                        <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-note"></use>
                    </svg>&nbsp;&nbsp;
                    <?php echo $hesklang['addnote']; ?>
                </button>
            </div>
        </article>
        <?php

        if ( ! $hesk_settings['new_top'])
        {
            hesk_printTicketReplies();
        }

        /* Reply form on bottom? */
        if ($can_reply && ! $hesk_settings['reply_top'])
        {
            hesk_printReplyForm();
        }

        $random=rand(10000,99999);

        // Prepare one-click action to open/resolve a ticket
        $status_action = '';
        if ($ticket['status'] == 3)
        {
            if ($can_reply)
            {
                $status_action = '[<a href="change_status.php?track='.$trackingID.'&amp;s=1&amp;Refresh='.$random.'&amp;token='.hesk_token_echo(0).'">'.$hesklang['open_action'].'</a>]';
            }
        }
        elseif ($can_resolve)
        {
            $status_action = '[<a href="change_status.php?track='.$trackingID.'&amp;s=3&amp;Refresh='.$random.'&amp;token='.hesk_token_echo(0).'">'.$hesklang['close_action'].'</a>]';
        }
        ?>
    </div>
    <div class="ticket__params">
        <section class="params--bar" style="padding-left: 0">
            <?php echo hesk_getAdminButtons(); ?>
        </section>
        <section class="params--block params">
            <!-- Ticket status -->
            <div class="row ts" id="ticket-status-div" <?php echo strlen($status_action) ? 'style="margin-bottom: 10px;"' : ''; ?>>
                <div class="title"><label for="select_s"><?php echo $hesklang['ticket_status']; ?>:</label></div>
                <?php if ($can_reply): ?>
                <div class="value dropdown-select center out-close">
                    <form action="change_status.php" method="post">
                        <select id="select_s" name="s" onchange="this.form.submit()">
                            <?php echo hesk_get_status_select('', $can_resolve, $ticket['status']); ?>
                        </select>
                        <input type="hidden" name="track" value="<?php echo $trackingID; ?>">
                        <input type="hidden" name="token" value="<?php hesk_token_echo(); ?>">
                    </form>
                </div>
                <?php else: ?>
                <div class="value center">
                    <?php echo hesk_get_admin_ticket_status($ticket['status']); ?>
                </div>
                <?php
                endif;
                ?>
            </div>

            <!-- Ticket one click open/resolve -->
            <?php if (strlen($status_action)): ?>
            <div class="row">
                <div class="title">&nbsp;</div>
                <div class="value center out-close">
                    <?php echo $status_action; ?>
                </div>
            </div>
            <?php
            endif;
            ?>

            <!-- Ticket category -->
            <div class="row">
                <div class="title">
                    <label for="select_category">
                        <?php echo $hesklang['category']; ?>:
                    </label>
                </div>
                <?php if (strlen($categories_options) && ($can_change_cat || $can_change_own_cat)): ?>
                <form action="move_category.php" method="post">
                    <div class="value dropdown-select center out-close">
                        <select id="select_category" name="category" onchange="this.form.submit()">
                            <?php echo $categories_options; ?>
                        </select>
                    </div>
                    <input type="hidden" name="track" value="<?php echo $trackingID; ?>">
                    <input type="hidden" name="token" value="<?php hesk_token_echo(); ?>">
                </form>
                <?php else: ?>
                <div class="value center out-close">
                    <?php echo $category['name']; ?>
                </div>
                <?php
                endif;
                ?>
            </div>

            <!-- Ticket priority -->
            <div class="row">
                <div class="title">
                    <label for="select_priority">
                        <?php echo $hesklang['priority']; ?>:
                    </label>
                </div>
                <?php if ($can_reply): ?>
                <form action="priority.php" method="post">
                    <div class="dropdown-select center out-close priority">
                        <select id="select_priority" name="priority" onchange="this.form.submit()">
                            <?php echo implode('', $options); ?>
                        </select>
                    </div>
                    <input type="hidden" name="track" value="<?php echo $trackingID; ?>">
                    <input type="hidden" name="token" value="<?php hesk_token_echo(); ?>">
                </form>
                <?php else: ?>
                <div class="value center out-close">
                    <?php if ($ticket['priority'] == 0): ?>
                    <span class="priority0"><?php echo $hesklang['critical']; ?></span>
                    <?php elseif ($ticket['priority'] == 1): ?>
                    <span class="priority1"><?php echo $hesklang['high']; ?></span>
                    <?php elseif ($ticket['priority'] == 2): ?>
                    <span class="priority2"><?php echo $hesklang['medium']; ?></span>
                    <?php else: ?>
                    <span class="priority3"><?php echo $hesklang['low']; ?></span>
                    <?php endif; ?>
                </div>
                <?php
                endif;
                ?>
            </div>

            <!-- Ticket assigned to -->
            <div class="row">
                <div class="title">
                    <label for="select_owner">
                        <?php echo $hesklang['assigned_to']; ?>:
                    </label>
                </div>
                <?php if (hesk_checkPermission('can_assign_others',0)): ?>
                <form action="assign_owner.php" method="post">
                    <div class="value dropdown-select center out-close">
                        <select id="select_owner" name="owner" onchange="this.form.submit()">
                            <option value="-1"> &gt; <?php echo $hesklang['unas']; ?> &lt; </option>
                            <?php
                            foreach ($admins as $k=>$v)
                            {
                                echo '<option value="'.$k.'" '.($k == $ticket['owner'] ? 'selected' : '').'>'.$v.'</option>';
                            }
                            ?>
                        </select>
                        <input type="hidden" name="track" value="<?php echo $trackingID; ?>">
                        <input type="hidden" name="token" value="<?php hesk_token_echo(); ?>">
                        <?php
                        if (!$ticket['owner'])
                        {
                            echo '<input type="hidden" name="unassigned" value="1">';
                        }
                        ?>
                    </div>
                </form>
                <?php else: ?>
                <div class="value center out-close">
                    <?php
                    echo isset($admins[$ticket['owner']]) ? '<b>'.$admins[$ticket['owner']].'</b>' : '<b>'.$hesklang['unas'].'</b>';
                    ?>
                </div>
                <?php
                endif;
                ?>
            </div>

            <!-- Ticket one click assign to self -->
            <?php if (!$ticket['owner'] && $can_assign_self): ?>
            <div class="row">
                <div class="title">&nbsp;</div>
                <div class="value center out-close">
                    <?php echo '[<a class="link" href="assign_owner.php?track='.$trackingID.'&amp;owner='.$_SESSION['id'].'&amp;token='.hesk_token_echo(0).'&amp;unassigned=1">'.$hesklang['asss'].'</a>]'; ?>
                </div>
            </div>
            <?php
            endif;
            ?>
        </section>
        <section class="params--block details accordion visible">
            <h4 class="accordion-title">
                <span><?php echo $hesklang['ticket_details']; ?></span>
                <svg class="icon icon-chevron-down">
                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-chevron-down"></use>
                </svg>
            </h4>
            <div class="accordion-body" style="display:block">
                <div class="row">
                    <div class="title"><?php echo $hesklang['trackID']; ?>:</div>
                    <div class="value"><?php echo $trackingID; ?></div>
                </div>
                <?php
                if ($hesk_settings['sequential'])
                {
                    ?>
                    <div class="row">
                        <div class="title"><?php echo $hesklang['seqid']; ?>:</div>
                        <div class="value"><?php echo $ticket['id']; ?></div>
                    </div>
                    <?php
                }
                ?>
                <div class="row">
                    <div class="title"><?php echo $hesklang['created_on']; ?>:</div>
                    <div class="value"><?php echo hesk_date($ticket['dt'], true); ?></div>
                </div>
                <div class="row">
                    <div class="title"><?php echo $hesklang['last_update']; ?>:</div>
                    <div class="value"><?php echo hesk_date($ticket['lastchange'], true); ?></div>
                </div>
                <div class="row">
                    <div class="title"><?php echo $hesklang['replies']; ?>:</div>
                    <div class="value"><?php echo $ticket['replies']; ?></div>
                </div>
                <div class="row">
                    <div class="title"><?php echo $hesklang['last_replier']; ?>:</div>
                    <div class="value"><?php echo $ticket['repliername']; ?></div>
                </div>
                <?php
                if ($hesk_settings['time_worked'])
                {
                ?>
                <div class="row">
                    <div class="title"><?php echo $hesklang['ts']; ?>:</div>
                    <?php
                    if ($can_reply || $can_edit)
                    {
                        ?>
                    <div class="value">
                        <a href="javascript:" onclick="hesk_toggleLayerDisplay('modifytime')">
                            <?php echo $ticket['time_worked']; ?>
                        </a>

                        <?php $t = hesk_getHHMMSS($ticket['time_worked']); ?>

                        <div id="modifytime" style="display:none">
                            <form class="form" method="post" action="admin_ticket.php">
                                <div class="form-group">
                                    <label for="hours"><?php echo $hesklang['hh']; ?></label>
                                    <input class="form-control" type="text" id="hours" name="h" value="<?php echo $t[0]; ?>">
                                </div>
                                <div class="form-group">
                                    <label for="minutes"><?php echo $hesklang['mm']; ?></label>
                                    <input class="form-control" type="text" id="minutes" name="m" value="<?php echo $t[1]; ?>">
                                </div>
                                <div class="form-group">
                                    <label for="seconds"><?php echo $hesklang['ss']; ?></label>
                                    <input class="form-control" type="text" id="seconds" name="s" value="<?php echo $t[2]; ?>">
                                </div>

                                <button style="display: inline-flex; width: auto; height: 48px; padding: 0 16px" class="btn btn-full" type="submit"><?php echo $hesklang['save']; ?></button>
                                <a class="btn btn--blue-border" href="Javascript:void(0)" onclick="Javascript:hesk_toggleLayerDisplay('modifytime')"><?php echo $hesklang['cancel']; ?></a>
                                <input type="hidden" name="track" value="<?php echo $trackingID; ?>" />
                                <input type="hidden" name="token" value="<?php hesk_token_echo(); ?>" />
                            </form>
                        </div>
                    </div>
                        <?php
                    }
                    else
                    {
                        echo '<div class="value">' . $ticket['time_worked'] . '</div>';
                    }
                    ?>
                </div>
                <?php
                }
                ?>
            </div>
        </section>
        <?php
        /* Display ticket history */
        if (strlen($ticket['history']))
        {
            $history_pieces = explode('</li>', $ticket['history'], -1);

            ?>
            <section class="params--block history accordion">
                <h4 class="accordion-title">
                    <span><?php echo $hesklang['thist']; ?></span>
                    <svg class="icon icon-chevron-down">
                        <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-chevron-down"></use>
                    </svg>
                </h4>
                <div class="accordion-body">
                    <?php
                    foreach ($history_pieces as $history_piece) {
                        $history_piece = str_replace('<li class="smaller">', '', $history_piece);
                        $date_and_contents = explode(' | ', $history_piece);
                    ?>
                    <div class="row">
                        <div class="title"><?php echo $date_and_contents[0]; ?></div>
                        <div class="value"><?php echo $date_and_contents[1]; ?></div>
                    </div>
                    <?php } ?>
                </div>
            </section>
            <?php
        }
        ?>
    </div>
</div>

<a href="#" class="back-to-top"><?php echo $hesklang['btt']; ?></a>

<?php
/* Clear unneeded session variables */
hesk_cleanSessionVars('ticket_message');
hesk_cleanSessionVars('time_worked');
hesk_cleanSessionVars('note_message');

$hesk_settings['print_status_select_box_jquery'] = true;

require_once(HESK_PATH . 'inc/footer.inc.php');


/*** START FUNCTIONS ***/


function hesk_listAttachments($attachments='', $reply=0, $white=1)
{
	global $hesk_settings, $hesklang, $trackingID, $can_edit, $can_delete;

	/* Attachments disabled or not available */
	if ( ! $hesk_settings['attachments']['use'] || ! strlen($attachments) )
    {
    	return false;
    }

	/* List attachments */
	$att=explode(',',substr($attachments, 0, -1));
    echo '<div class="block--uploads" style="display: block; color: #9c9c9c;">';
	foreach ($att as $myatt)
	{
		list($att_id, $att_name) = explode('#', $myatt);

        /* Can edit and delete tickets? */
        if ($can_edit && $can_delete)
        {
        	echo '<a class="tooltip" data-ztt_vertical_offset="0" style="margin-right: 8px;" title="'.$hesklang['dela'].'" href="admin_ticket.php?delatt='.$att_id.'&amp;reply='.$reply.'&amp;track='.$trackingID.'&amp;Refresh='.mt_rand(10000,99999).'&amp;token='.hesk_token_echo(0).'" onclick="return hesk_confirmExecute(\''.hesk_makeJsString($hesklang['pda']).'\');">
        	    <svg class="icon icon-delete" style="width: 16px; height: 16px; vertical-align: text-bottom;">
                    <use xlink:href="'. HESK_PATH .'img/sprite.svg#icon-delete"></use>
                </svg>
            </a> &raquo;';
        }

		echo '
		<a title="'.$hesklang['dnl'].' '.$att_name.'" href="../download_attachment.php?att_id='.$att_id.'&amp;track='.$trackingID.'">
            <svg class="icon icon-attach" style="width: 16px; height: 16px; margin-right: 0px; vertical-align: text-bottom;">
                <use xlink:href="'. HESK_PATH .'img/sprite.svg#icon-attach"></use>
            </svg>
        </a>
		<a class="underline" title="'.$hesklang['dnl'].' '.$att_name.'" href="../download_attachment.php?att_id='.$att_id.'&amp;track='.$trackingID.'">'.$att_name.'</a><br />
        ';
	}
    echo '</div>';

    return true;
} // End hesk_listAttachments()


function hesk_getAdminButtons($isReply=0,$white=1)
{
	global $hesk_settings, $hesklang, $ticket, $reply, $trackingID, $can_edit, $can_archive, $can_delete, $can_resolve, $can_privacy, $can_export;

	$buttons = array();

    // Edit
    if ($can_edit)
    {
        $tmp = $isReply ? '&amp;reply='.$reply['id'] : '';
        if ($isReply) {
            $buttons['more']['edit'] = '
        <a id="editreply'.$reply['id'].'" href="edit_post.php?track='.$trackingID.$tmp.'" title="'.$hesklang['btn_edit'].'" style="margin-right: 15px">
            <svg class="icon icon-edit-ticket">
                <use xlink:href="'. HESK_PATH . 'img/sprite.svg#icon-edit-ticket"></use>
            </svg>
            '.$hesklang['btn_edit'].'
        </a>';
        } else {
            $buttons[] = '
        <a id="editticket" href="edit_post.php?track='.$trackingID.$tmp.'" title="'.$hesklang['btn_edit'].'">
            <svg class="icon icon-edit-ticket">
                <use xlink:href="'. HESK_PATH . 'img/sprite.svg#icon-edit-ticket"></use>
            </svg>
            '.$hesklang['btn_edit'].'
        </a>';
        }

    }


    if (!$isReply) {
        // Print ticket button
        $buttons[] = '
        <a href="../print.php?track='.$trackingID.'" title="'.$hesklang['btn_print'].'">
            <svg class="icon icon-print">
                <use xlink:href="' . HESK_PATH .'img/sprite.svg#icon-print"></use>
            </svg>
            '.$hesklang['btn_print'].'
        </a>';
    }


    // Lock ticket button
	if (!$isReply && $can_resolve) {
		if ($ticket['locked']) {
			$des = $hesklang['tul'] . ' - ' . $hesklang['isloc'];
            $buttons['more'][] = '
            <a id="unlock" href="lock.php?track='.$trackingID.'&amp;locked=0&amp;Refresh='.mt_rand(10000,99999).'&amp;token='.hesk_token_echo(0).'" title="'.$des.'">
                <svg class="icon icon-lock">
                    <use xlink:href="' . HESK_PATH . 'img/sprite.svg#icon-lock"></use>
                </svg> 
                '.$hesklang['btn_unlock'].'
            </a>';
		} else {
			$des = $hesklang['tlo'] . ' - ' . $hesklang['isloc'];
            $buttons['more'][] = '
            <a id="lock" href="lock.php?track='.$trackingID.'&amp;locked=1&amp;Refresh='.mt_rand(10000,99999).'&amp;token='.hesk_token_echo(0).'" title="'.$des.'">
                <svg class="icon icon-lock">
                    <use xlink:href="' . HESK_PATH . 'img/sprite.svg#icon-lock"></use>
                </svg>  
                '.$hesklang['btn_lock'].'
            </a>';
		}
	}

	// Tag ticket button
	if (!$isReply && $can_archive) {
		if ($ticket['archive']) {
        	$buttons['more'][] = '
        	<a id="untag" href="archive.php?track='.$trackingID.'&amp;archived=0&amp;Refresh='.mt_rand(10000,99999).'&amp;token='.hesk_token_echo(0).'" title="'.$hesklang['remove_archive'].'">
        	    <svg class="icon icon-tag">
                    <use xlink:href="'. HESK_PATH .'img/sprite.svg#icon-tag"></use>
                </svg> 
                '.$hesklang['btn_untag'].'
            </a>';
		} else {
        	$buttons['more'][] = '
        	<a id="tag" href="archive.php?track='.$trackingID.'&amp;archived=1&amp;Refresh='.mt_rand(10000,99999).'&amp;token='.hesk_token_echo(0).'" title="'.$hesklang['add_archive'].'">
        	    <svg class="icon icon-tag">
                    <use xlink:href="'. HESK_PATH .'img/sprite.svg#icon-tag"></use>
                </svg> 
                '.$hesklang['btn_tag'].'
            </a>';
		}
	}

	// Resend email notification button
	$buttons['more'][] = '
	<a id="resendemail" href="resend_notification.php?track='.$trackingID.'&amp;reply='.(isset($reply['id']) ? intval($reply['id']) : 0).'&amp;Refresh='.mt_rand(10000,99999).'&amp;token='.hesk_token_echo(0).'" title="'.$hesklang['btn_resend'].'">
	    <svg class="icon icon-mail-small">
            <use xlink:href="'. HESK_PATH .'img/sprite.svg#icon-mail-small"></use>
        </svg>
	    '.$hesklang['btn_resend'].'
    </a>';

	// Import to knowledgebase button
	if (!$isReply && $hesk_settings['kb_enable'] && hesk_checkPermission('can_man_kb',0))
	{
		$buttons['more'][] = '
		<a id="addtoknow" href="manage_knowledgebase.php?a=import_article&amp;track='.$trackingID.'" title="'.$hesklang['import_kb'].'">
		    <svg class="icon icon-knowledge">
                <use xlink:href="'. HESK_PATH .'img/sprite.svg#icon-knowledge"></use>
            </svg>
		    '.$hesklang['btn_import_kb'].'
        </a>';
	}

    // Export ticket
    if (!$isReply && $can_export)
    {
        $buttons['more'][] = '
        <a id="exportticket" href="export_ticket.php?track='.$trackingID.'&amp;Refresh='.mt_rand(10000,99999).'&amp;token='.hesk_token_echo(0).'" title="'.$hesklang['btn_export'].'">
            <svg class="icon icon-export">
                <use xlink:href="'. HESK_PATH .'img/sprite.svg#icon-export"></use>
            </svg> 
            '.$hesklang['btn_export'].'
        </a>';
    }

    // Anonymize ticket
    if (!$isReply && $can_privacy)
    {
		$buttons['more'][] = '
		<a id="anonymizeticket" href="anonymize_ticket.php?track='.$trackingID.'&amp;Refresh='.mt_rand(10000,99999).'&amp;token='.hesk_token_echo(0).'" onclick="return hesk_confirmExecute(\''.hesk_makeJsString($hesklang['confirm_anony']).'?\\n\\n'.hesk_makeJsString($hesklang['privacy_anon_info']).'\');" title="'.$hesklang['confirm_anony'].'">
		    <svg class="icon icon-anonymize">
                <use xlink:href="'. HESK_PATH .'img/sprite.svg#icon-anonymize"></use>
            </svg>
             '.$hesklang['btn_anony'].'
        </a>';
    }

	// Delete ticket or reply
	if ($can_delete)
	{
		if ($isReply)
		{
			$url = 'admin_ticket.php';
			$tmp = 'delete_post='.$reply['id'];
			$txt = $hesklang['btn_delr'];
		}
		else
		{
			$url = 'delete_tickets.php';
			$tmp = 'delete_ticket=1';
			$txt = $hesklang['btn_delt'];
		}
		$buttons['more'][] = '
		<a id="deleteticket" href="'.$url.'?track='.$trackingID.'&amp;'.$tmp.'&amp;Refresh='.mt_rand(10000,99999).'&amp;token='.hesk_token_echo(0).'" onclick="return hesk_confirmExecute(\''.hesk_makeJsString($txt).'?\');" title="'.$txt.'">
		    <svg class="icon icon-delete">
                <use xlink:href="'. HESK_PATH .'img/sprite.svg#icon-delete"></use>
            </svg>
		    '.$txt.'
        </a>';
	}

    // Format and return the HTML for buttons
    $button_code = '';

    foreach ($buttons as $button) {
        if (is_array($button)) {
            $more_class = $isReply ? 'more ' : '';
            $label = '
            <label>
                <span>
                    <svg class="icon icon-chevron-down">
                        <use xlink:href="' . HESK_PATH . 'img/sprite.svg#icon-chevron-down"></use>
                    </svg>
                </span>
            </label>
            ';

            if ($isReply) {
                $label = '
                <label>
                    <span>' . $hesklang['btn_more'] . '</span>
                    <svg class="icon icon-chevron-down">
                        <use xlink:href="'.HESK_PATH.'img/sprite.svg#icon-chevron-down"></use>
                    </svg>
                </label>';
            }

            $button_code .= '<div class="'.$more_class.'dropdown right out-close">';
            if (isset($button['edit']))
            {
                $button_code .= $button['edit'];
                unset($button['edit']);
            }

            $button_code .= $label.'<ul class="dropdown-list">';

            foreach ($button as $sub_button) {
                $button_code .= '<li>'.$sub_button.'</li>';
            }

            $button_code .= '</ul></div>';
        } else {
            $button_code .= $button;
        }
    }

    $button_code .= '';

    return $button_code;

} // END hesk_getAdminButtons()


function print_form()
{
	global $hesk_settings, $hesklang;
    global $trackingID;

	/* Print header */
	require_once(HESK_PATH . 'inc/header.inc.php');

	/* Print admin navigation */
	require_once(HESK_PATH . 'inc/show_admin_nav.inc.php');
	?>

    <div class="main__content categories">
        <div class="table-wrap">
            <?php
            /* This will handle error, success and notice messages */
            hesk_handle_messages();
            ?>
            <h3><?php echo $hesklang['view_existing']; ?></h3>
            <form action="admin_ticket.php" method="get" class="form">
                <div class="form-group">
                    <label for="find_ticket_track"><?php echo $hesklang['ticket_trackID']; ?></label>
                    <input id="find_ticket_track" class="form-control" type="text" name="track" maxlength="20" value="<?php echo $trackingID; ?>">
                </div>
                <div class="form-group">
                    <input type="submit" value="<?php echo $hesklang['view_ticket']; ?>" class="btn btn-full">
                    <input type="hidden" name="Refresh" value="<?php echo rand(10000,99999); ?>">
                </div>
            </form>
        </div>
    </div>

	<?php
	require_once(HESK_PATH . 'inc/footer.inc.php');
	exit();
} // End print_form()


function hesk_printTicketReplies() {
	global $hesklang, $hesk_settings, $result, $reply, $ticket;

	$i = $hesk_settings['new_top'] ? 0 : 1;

	if ($reply === false)
	{
		return $i;
	}

    $replies = array();
    $collapsed_replies = array();
    $displayed_replies = array();
	$last_staff_reply_index = -1;
	$i = 0;
	while ($reply = hesk_dbFetchAssoc($result)) {
	    $replies[] = $reply;
        if ($reply['staffid'] && ( ! $hesk_settings['new_top'] || $last_staff_reply_index === -1)) {
	        $last_staff_reply_index = $i;
        }
	    $i++;
    }

    // Hide ticket replies?
    $i = 0;
    foreach ($replies as $reply) {
        // Show the last staff reply and any subsequent customer replies
        if ($hesk_settings['hide_replies'] == -1) {
            if ($hesk_settings['new_top']) {
                if ($i <= $last_staff_reply_index) {
                    $displayed_replies[] = $reply;
                } else {
                    $collapsed_replies[] = $reply;
                }
            } else {
                if ($i < $last_staff_reply_index) {
                    $collapsed_replies[] = $reply;
                } else {
                    $displayed_replies[] = $reply;
                }
            }
        // Hide all replies except the last X
        } elseif ($hesk_settings['hide_replies'] > 0) {
            if ($hesk_settings['new_top']) {
                if ($i >= $hesk_settings['hide_replies']) {
                    $collapsed_replies[] = $reply;
                } else {
                    $displayed_replies[] = $reply;
                }
            } else {
                if ($i < ($ticket['replies'] - $hesk_settings['hide_replies'])) {
                    $collapsed_replies[] = $reply;
                } else {
                    $displayed_replies[] = $reply;
                }
            }
        // Never, always show all replies
        } else {
            $displayed_replies[] = $reply;
        }
        $i++;
    }

    $start_previous_replies = true;
    for ($j = 0; $j < count($collapsed_replies) && $hesk_settings['new_top'] == 0; $j++) {
        $reply = $collapsed_replies[$j];
        if ($start_previous_replies):
            $start_previous_replies = false;
            ?>
            <section class="ticket__replies">
                <div class="ticket__replies_link">
                    <span><?php echo $hesklang['show_previous_replies']; ?></span>
                    <b><?php echo count($collapsed_replies); ?></b>
                    <svg class="icon icon-chevron-down">
                        <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-chevron-down"></use>
                    </svg>
                </div>
                <div class="ticket__replies_list">
            <?php
        endif;
        ?>
        <article class="ticket__body_block <?php echo $reply['staffid'] ? 'response' : ''; ?>">
            <div class="block--head">
                <div class="contact">
                    <?php echo $hesklang['reply_by']; ?>
                    <b><?php echo $reply['name']; ?></b>
                    &raquo;
                    <time class="timeago tooltip" datetime="<?php echo date("c", strtotime($reply['dt'])) ; ?>" title="<?php echo hesk_date($reply['dt'], true); ?>"><?php echo hesk_date($reply['dt'], true); ?></time>
                </div>
                <?php echo hesk_getAdminButtons(1, $i); ?>
            </div>
            <div class="block--description">
                <p><?php echo $reply['message']; ?></p>
            </div>
            <?php

            /* Attachments */
            hesk_listAttachments($reply['attachments'], $reply['id'], $i);

            /* Staff rating */
            if ($hesk_settings['rating'] && $reply['staffid']) {
                if ($reply['rating'] == 1) {
                    echo '<p class="rate">' . $hesklang['rnh'] . '</p>';
                } elseif ($reply['rating'] == 5) {
                    echo '<p class="rate">' . $hesklang['rh'] . '</p>';
                }
            }

            /* Show "unread reply" message? */
            if ($reply['staffid'] && !$reply['read']) {
                echo '<p class="rate">' . $hesklang['unread'] . '</p>';
            }

            ?>
        </article>
        <?php
        if (!$start_previous_replies && $j == count($collapsed_replies) - 1) {
            echo '</div>
            </section>';
        }
    }

    for ($j = 0; $j < count($displayed_replies); $j++) {
        $reply = $displayed_replies[$j];
        ?>
        <article class="ticket__body_block <?php echo $reply['staffid'] ? 'response' : ''; ?>">
            <div class="block--head">
                <div class="contact">
                    <?php echo $hesklang['reply_by']; ?>
                    <b><?php echo $reply['name']; ?></b>
                    &raquo;
                    <time class="timeago tooltip" datetime="<?php echo date("c", strtotime($reply['dt'])) ; ?>" title="<?php echo hesk_date($reply['dt'], true); ?>"><?php echo hesk_date($reply['dt'], true); ?></time>
                </div>
                <?php echo hesk_getAdminButtons(1,$i); ?>
            </div>
            <div class="block--description">
                <p><?php echo $reply['message']; ?></p>
            </div>
            <?php
            /* Attachments */
            hesk_listAttachments($reply['attachments'],$reply['id'],$i);

            /* Staff rating */
            if ($hesk_settings['rating'] && $reply['staffid'])
            {
                if ($reply['rating']==1)
                {
                    echo '<p class="rate">'.$hesklang['rnh'].'</p>';
                }
                elseif ($reply['rating']==5)
                {
                    echo '<p class="rate">'.$hesklang['rh'].'</p>';
                }
            }

            /* Show "unread reply" message? */
            if ($reply['staffid'] && ! $reply['read'])
            {
                echo '<p class="rate">'.$hesklang['unread'].'</p>';
            }
            ?>
        </article>
        <?php
    }

    $start_previous_replies = true;
    for ($j = 0; $j < count($collapsed_replies) && $hesk_settings['new_top']; $j++) {
        $reply = $collapsed_replies[$j];
        if ($start_previous_replies):
            $start_previous_replies = false;
            ?>
            <section class="ticket__replies">
                <div class="ticket__replies_link">
                    <span><?php echo $hesklang['show_previous_replies']; ?></span>
                    <b><?php echo count($collapsed_replies); ?></b>
                    <svg class="icon icon-chevron-down">
                        <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-chevron-down"></use>
                    </svg>
                </div>
                <div class="ticket__replies_list">
            <?php
        endif;
        ?>
        <article class="ticket__body_block <?php echo $reply['staffid'] ? 'response' : ''; ?>">
        <div class="block--head">
            <div class="contact">
                <?php echo $hesklang['reply_by']; ?>
                <b><?php echo $reply['name']; ?></b>
                &raquo;
                <time class="timeago tooltip" datetime="<?php echo date("c", strtotime($reply['dt'])) ; ?>" title="<?php echo hesk_date($reply['dt'], true); ?>"><?php echo hesk_date($reply['dt'], true); ?></time>
            </div>
            <?php echo hesk_getAdminButtons(1, $i); ?>
        </div>
        <div class="block--description">
            <p><?php echo $reply['message']; ?></p>
        </div>
        <?php

        /* Attachments */
        hesk_listAttachments($reply['attachments'], $reply['id'], $i);

        /* Staff rating */
        if ($hesk_settings['rating'] && $reply['staffid']) {
            if ($reply['rating'] == 1) {
                echo '<p class="rate">' . $hesklang['rnh'] . '</p>';
            } elseif ($reply['rating'] == 5) {
                echo '<p class="rate">' . $hesklang['rh'] . '</p>';
            }
        }

        /* Show "unread reply" message? */
        if ($reply['staffid'] && !$reply['read']) {
            echo '<p class="rate">' . $hesklang['unread'] . '</p>';
        }

        ?>
        </article>
        <?php
        if (!$start_previous_replies && $j == count($collapsed_replies) - 1) {
            echo '</div>
            </section>';
        }
    }

    return $i;

} // End hesk_printTicketReplies()

function hesk_printReplyForm() {
	global $hesklang, $hesk_settings, $ticket, $admins, $can_options, $options, $can_assign_self, $can_resolve;

    // Force assigning a ticket before allowing to reply?
    if ($hesk_settings['require_owner'] && ! $ticket['owner'])
    {
        hesk_show_notice($hesklang['atbr'].($can_assign_self ? '<br /><br /><a href="assign_owner.php?track='.$ticket['trackid'].'&amp;owner='.$_SESSION['id'].'&amp;token='.hesk_token_echo(0).'&amp;unassigned=1">'.$hesklang['attm'].'</a>' : ''), $hesklang['owneed']);
        return '';
    }
?>
<!-- START REPLY FORM -->
<article class="ticket__body_block">
    <form method="post" class="form" action="admin_reply_ticket.php" enctype="multipart/form-data" name="form1" onsubmit="force_stop();return true;">
        <?php
        /* Ticket assigned to someone else? */
        if ($ticket['owner'] && $ticket['owner'] != $_SESSION['id'] && isset($admins[$ticket['owner']])) {
            hesk_show_notice($hesklang['nyt'] . ' ' . $admins[$ticket['owner']]);
        }

        /* Ticket locked? */
        if ($ticket['locked']) {
            hesk_show_notice($hesklang['tislock']);
        }

        if ($hesk_settings['time_worked'] && strlen($can_options)) {
            ?>
            <div class="time-and-canned">
            <?php
        }
        // Track time worked?
        if ($hesk_settings['time_worked']) {
            ?>
            <section class="block--timer">
                <span>
                    <label for="time_worked">
                        <?php echo $hesklang['ts']; ?>:
                    </label>
                </span>
                <div class="form-group short" style="margin-left: 8px; margin-bottom: 0">
                    <input type="text" class="form-control short" name="time_worked" id="time_worked" size="10" value="<?php echo ( isset($_SESSION['time_worked']) ? hesk_getTime($_SESSION['time_worked']) : '00:00:00'); ?>" />
                </div>

                <a href="javascript:" class="tooltip" id="pause_btn" title="<?php echo $hesklang['start']; ?>">
                    <svg class="icon icon-pause">
                        <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-pause"></use>
                    </svg>
                </a>
                <a href="javascript:" class="tooltip" id="reset_btn" title="<?php echo $hesklang['reset']; ?>">
                    <svg class="icon icon-refresh">
                        <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-refresh"></use>
                    </svg>
                </a>
                <script>
                    $('#pause_btn').click(function() {
                        ss();
                        updatePauseButton();
                    });

                    $('#reset_btn').click(function() {
                        $('#pause_btn').find('svg').css('fill', '#002d73');
                        r();
                    });

                    function updatePauseButton() {
                        if (!timer_running()) {
                            $('#pause_btn').find('svg').css('fill', '#002d73');
                        } else {
                            $('#pause_btn').find('svg').css('fill', '#959eb0');
                        }
                    }

                    $(document).ready(function() {
                        setTimeout(updatePauseButton, 1000);
                    })
                </script>
            </section>
            <?php
        }

        /* Do we have any canned responses? */
        if (strlen($can_options))
        {
            ?>
        <section class="block--timer canned-options">
            <div class="canned-header">
                <?php echo $hesklang['saved_replies']; ?>
            </div>
            <div class="options" style="text-align: left">
                <div>
                    <div class="radio-custom">
                        <input type="radio" name="mode" id="modeadd"
                               value="1" checked>
                        <label for="modeadd">
                            <?php echo $hesklang['madd']; ?>
                        </label>
                    </div>
                    <div class="radio-custom">
                        <input type="radio" name="mode" id="moderep"
                               value="0">
                        <label for="moderep">
                            <?php echo $hesklang['mrep']; ?>
                        </label>
                    </div>
                </div>
                <div class="form-group">
                    <label><?php echo $hesklang['select_saved']; ?></label>
                        <select name="saved_replies" id="saved_replies" onchange="setMessage(this.value)">
                            <option value="0"> - <?php echo $hesklang['select_empty']; ?> - </option>
                            <?php echo $can_options; ?>
                        </select>
                        <script>
                            $('#saved_replies').selectize();
                        </script>
                </div>
            </div>
        </section>
            <?php
        }

        if ($hesk_settings['time_worked'] && strlen($can_options)) {
        ?>
            </div>
                <?php
                }
        ?>

            <div class="block--message" id="message-block">
                <textarea name="message" id="message" placeholder="<?php echo $hesklang['type_your_message']; ?>"><?php

                    // Do we have any message stored in session?
                    if ( isset($_SESSION['ticket_message']) )
                    {
                        echo stripslashes( hesk_input( $_SESSION['ticket_message'] ) );
                    }
                    // Perhaps a message stored in reply drafts?
                    else
                    {
                        $res = hesk_dbQuery("SELECT `message` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."reply_drafts` WHERE `owner`=".intval($_SESSION['id'])." AND `ticket`=".intval($ticket['id'])." LIMIT 1");
                        if (hesk_dbNumRows($res) == 1)
                        {
                            echo hesk_dbResult($res);
                        }
                    }

                ?></textarea>
            </div>

        <?php
        /* attachments */
        if ($hesk_settings['attachments']['use'])
        {
            ?>
            <div class="block--attach">
                <svg class="icon icon-attach">
                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-attach"></use>
                </svg>
                <div>
                    <?php echo $hesklang['attachments'] . ' (<a class="link" href="Javascript:void(0)" onclick="hesk_window(\'../file_limits.php\',250,500);return false;">' . $hesklang['ful'] . '</a>):<br>'; ?>
                </div>
            </div>
            <?php
            for ($i=1;$i<=$hesk_settings['attachments']['max_number'];$i++)
            {
                echo '<input type="file" name="attachment['.$i.']" size="50" /><br />';
            }
            ?>
            <?php
        }
        ?>

        <section class="block--checkboxs">
            <?php
            if ($ticket['owner'] != $_SESSION['id'] && $can_assign_self)
            {
                echo '<div class="checkbox-custom">';
                if (empty($ticket['owner']))
                {
                    echo '<input type="checkbox" id="assign_self" name="assign_self" value="1" checked="checked">';
                }
                else
                {
                    echo '<input type="checkbox" id="assign_self" name="assign_self" value="1">';
                }
                echo '<label for="assign_self">'.$hesklang['asss2'].'</label>';
                echo '</div>';
            }
            ?>

            <div class="checkbox-custom">
                <input type="checkbox" id="signature" name="signature" value="1" checked="checked">
                <label for="signature">
                    <?php echo $hesklang['attach_sign']; ?>
                    (<a class="link" href="profile.php"><?php echo $hesklang['profile_settings']; ?></a>)
                </label>
            </div>

            <div class="checkbox-custom">
                <input type="checkbox" id="set_priority" name="set_priority" value="1">
                <label for="set_priority"><?php echo $hesklang['change_priority']; ?></label>

                <div class="dropdown-select center out-close" data-value="low">
                    <select id="replypriority" name="priority">
                        <?php echo implode('',$options); ?>
                    </select>
                </div>
            </div>
            <div class="checkbox-custom">
                <input type="checkbox" id="no_notify" name="no_notify" value="1" <?php echo $_SESSION['notify_customer_reply'] ? '' : 'checked'; ?>>
                <label for="no_notify"><?php echo $hesklang['dsen']; ?></label>
            </div>
        </section>
        <section class="block--submit">
            <input type="hidden" name="orig_id" value="<?php echo $ticket['id']; ?>">
            <input type="hidden" name="token" value="<?php hesk_token_echo(); ?>">
            <input class="btn btn-full" ripple="ripple" type="submit" value="<?php echo $hesklang['submit_reply']; ?>">
            &nbsp;
            <input class="btn btn-border" ripple="ripple" type="submit" name="save_reply" value="<?php echo $hesklang['sacl']; ?>">
            <?php
            // If ticket is not locked, show additional submit options
            if ( ! $ticket['locked']) {
                ?>
                <input type="hidden" id="submit_as_name" value="1" name="">
                <div class="submit-us dropdown-select out-close" data-value="">
                    <select onchange="document.getElementById('submit_as_name').name = this.value;this.form.submit()">
                        <option value="" selected><?php echo rtrim($hesklang['submit_as'], ':'); ?></option>
                        <option value="submit_as_customer"><?php echo $hesklang['sasc']; ?></option>
                        <?php if ($can_resolve): ?>
                        <option value="submit_as_resolved"><?php echo $hesklang['closed']; ?></option>
                        <?php endif; ?>
                        <option value="submit_as_in_progress"><?php echo $hesklang['in_progress']; ?></option>
                        <option value="submit_as_on_hold"><?php echo $hesklang['on_hold']; ?></option>
                    </select>
                </div>
                <?php
            }
            ?>

        </section>
    </form>
</article>

<!-- END REPLY FORM -->
<?php
} // End hesk_printReplyForm()


function hesk_printCanned()
{
	global $hesklang, $hesk_settings, $can_reply, $ticket, $admins;

	/* Can user reply to tickets? */
	if ( ! $can_reply)
    {
    	return '';
    }

	/* Get canned replies from the database */
	$res = hesk_dbQuery("SELECT * FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."std_replies` ORDER BY `reply_order` ASC");

	/* If no canned replies return empty */
    if ( ! hesk_dbNumRows($res) )
    {
    	return '';
    }

	/* We do have some replies, print the required Javascript and select field options */
	$can_options = '';
	?>
	<script language="javascript" type="text/javascript"><!--
    // -->
    var myMsgTxt = new Array();
	myMsgTxt[0]='';

	<?php
	while ($mysaved = hesk_dbFetchRow($res))
	{
	    $can_options .= '<option value="' . $mysaved[0] . '">' . $mysaved[1]. "</option>\n";
	    echo 'myMsgTxt['.$mysaved[0].']=\''.str_replace("\r\n","\\r\\n' + \r\n'", addslashes($mysaved[2]))."';\n";
	}

	?>

	function setMessage(msgid)
    {
		var myMsg=myMsgTxt[msgid];

        if (myMsg == '')
        {
            if (document.form1.mode[1].checked)
            {
                document.getElementById('message').value = '';
                $('.ticket .block--message .placeholder').click();
                return true;
            }
            return true;
        }

		myMsg = myMsg.replace(/%%HESK_ID%%/g, '<?php echo hesk_jsString($ticket['id']); ?>');
		myMsg = myMsg.replace(/%%HESK_TRACKID%%/g, '<?php echo hesk_jsString($ticket['trackid']); ?>');
		myMsg = myMsg.replace(/%%HESK_TRACK_ID%%/g, '<?php echo hesk_jsString($ticket['trackid']); ?>');
		myMsg = myMsg.replace(/%%HESK_NAME%%/g, '<?php echo hesk_jsString($ticket['name']); ?>');
        myMsg = myMsg.replace(/%%HESK_FIRST_NAME%%/g, '<?php echo hesk_jsString(hesk_full_name_to_first_name($ticket['name'])); ?>');
		myMsg = myMsg.replace(/%%HESK_EMAIL%%/g, '<?php echo hesk_jsString($ticket['email']); ?>');
		myMsg = myMsg.replace(/%%HESK_OWNER%%/g, '<?php echo hesk_jsString( isset($admins[$ticket['owner']]) ? $admins[$ticket['owner']] : ''); ?>');

		<?php
        for ($i=1; $i<=50; $i++)
		{
        	echo 'myMsg = myMsg.replace(/%%HESK_custom'.$i.'%%/g, \''.hesk_jsString($ticket['custom'.$i]).'\');';
		}
		?>

	    if (document.getElementById)
        {
            if (document.getElementById('moderep').checked)
            {
                document.getElementById('message-block').innerHTML = '<textarea name="message" id="message" placeholder="<?php echo $hesklang['type_your_message']; ?>">' + myMsg + '</textarea>';
            } else {
                var oldMsg = document.getElementById('message').value;
                document.getElementById('message-block').innerHTML = '<textarea name="message" id="message" placeholder="<?php echo $hesklang['type_your_message']; ?>">' + oldMsg + myMsg + '</textarea>';
            }
            $('.ticket .block--message .placeholder').click();
	    } else {
            if (document.form1.mode[0].checked) {
                document.form1.message.value = myMsg;
            } else {
                var oldMsg = document.form1.message.value;
                document.form1.message.value = oldMsg + myMsg;
            }
	    }
	}
	//-->
	</script>
    <?php

    /* Return options for select box */
    return $can_options;

} // End hesk_printCanned()
?>
