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
define('HESK_NO_ROBOTS',1);

/* Get all the required files and functions */
require(HESK_PATH . 'hesk_settings.inc.php');
define('TEMPLATE_PATH', HESK_PATH . "theme/{$hesk_settings['site_theme']}/");
require(HESK_PATH . 'inc/common.inc.php');

// Are we in maintenance mode?
hesk_check_maintenance();

hesk_load_database_functions();
hesk_session_start();

$hesk_error_buffer = array();
$do_remember = '';
$display = 'none';

/* A message from ticket reminder? */
if ( ! empty($_GET['remind']) )
{
    $display = 'block';
    $my_email = hesk_emailCleanup( hesk_validateEmail( hesk_GET('e'), 'ERR' , 0) );
    print_form();
}

// Do we have parameters in query string? If yes, store them in session and redirect
if ( isset($_GET['track']) || isset($_GET['e']) || isset($_GET['f']) || isset($_GET['r']) )
{
    $_SESSION['t_track'] = hesk_GET('track');
    $_SESSION['t_email'] = hesk_getCustomerEmail(1);
    $_SESSION['t_form']  = hesk_GET('f');
    $_SESSION['t_remember'] = strlen($do_remember) ? 'Y' : hesk_GET('r');

    header('Location: ticket.php');
    die();
}

/* Was this accessed by the form or link? */
$is_form = hesk_SESSION('t_form');

/* Get the tracking ID */
$trackingID = hesk_cleanID('', hesk_SESSION('t_track'));

/* Email required to view ticket? */
$my_email = hesk_getCustomerEmail(1, 't_email', 1);

/* Remember email address? */
$do_remember = strlen($do_remember) || strlen(hesk_SESSION('t_remember'));

/* Clean ticket parameters from the session data, we don't need them anymore */
hesk_cleanSessionVars( array('t_track', 't_email', 't_form', 't_remember') );

/* Any errors? Show the form */
if ($is_form)
{
	if ( empty($trackingID) )
    {
    	$hesk_error_buffer[] = $hesklang['eytid'];
    }

    if ($hesk_settings['email_view_ticket'] && empty($my_email) )
    {
    	$hesk_error_buffer[] = $hesklang['enter_valid_email'];
    }

    $tmp = count($hesk_error_buffer);
    if ($tmp == 1)
    {
    	$hesk_error_buffer = implode('',$hesk_error_buffer);
		hesk_process_messages($hesk_error_buffer,'NOREDIRECT');
        print_form();
    }
    elseif ($tmp == 2)
    {
    	$hesk_error_buffer = $hesklang['pcer'].'<br /><br /><ul><li>'.$hesk_error_buffer[0].'</li><li>'.$hesk_error_buffer[1].'</li></ul>';
		hesk_process_messages($hesk_error_buffer,'NOREDIRECT');
        print_form();
    }
}
elseif ( empty($trackingID) || ( $hesk_settings['email_view_ticket'] && empty($my_email) ) )
{
	print_form();
}

/* Connect to database */
hesk_dbConnect();

/* Limit brute force attempts */
hesk_limitBfAttempts();

// Load custom fields
require_once(HESK_PATH . 'inc/custom_fields.inc.php');

// Load statuses
require_once(HESK_PATH . 'inc/statuses.inc.php');

/* Get ticket info */
$res = hesk_dbQuery( "SELECT `t1`.* , `t2`.name AS `repliername` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` AS `t1` LEFT JOIN `".hesk_dbEscape($hesk_settings['db_pfix'])."users` AS `t2` ON `t1`.`replierid` = `t2`.`id` WHERE `trackid`='".hesk_dbEscape($trackingID)."' LIMIT 1");

/* Ticket found? */
if (hesk_dbNumRows($res) != 1)
{
	/* Ticket not found, perhaps it was merged with another ticket? */
	$res = hesk_dbQuery("SELECT * FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` WHERE `merged` LIKE '%#".hesk_dbEscape($trackingID)."#%' LIMIT 1");

	if (hesk_dbNumRows($res) == 1)
	{
    	/* OK, found in a merged ticket. Get info */
     	$ticket = hesk_dbFetchAssoc($res);

		/* If we require e-mail to view tickets check if it matches the one from merged ticket */
		if ( hesk_verifyEmailMatch($ticket['trackid'], $my_email, $ticket['email'], 0) )
        {
        	hesk_process_messages( sprintf($hesklang['tme'], $trackingID, $ticket['trackid']) ,'NOREDIRECT','NOTICE');
            $trackingID = $ticket['trackid'];
        }
        else
        {
        	hesk_process_messages( sprintf($hesklang['tme1'], $trackingID, $ticket['trackid']) . '<br /><br />' . sprintf($hesklang['tme2'], $ticket['trackid']) ,'NOREDIRECT','NOTICE');
            $trackingID = $ticket['trackid'];
            print_form();
        }
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

	/* If we require e-mail to view tickets check if it matches the one in database */
	hesk_verifyEmailMatch($trackingID, $my_email, $ticket['email']);
}

/* Ticket exists, clean brute force attempts */
hesk_cleanBfAttempts();

/* Remember email address? */
if ($is_form)
{
	if ($do_remember)
	{
		hesk_setcookie('hesk_myemail', $my_email, strtotime('+1 year'));
	}
	elseif ( isset($_COOKIE['hesk_myemail']) )
	{
		hesk_setcookie('hesk_myemail', '');
	}
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

// If IP is unknown (tickets via email pipe/pop3 fetching) assume current visitor IP as customer IP
if ($ticket['ip'] == '' || $ticket['ip'] == 'Unknown' || $ticket['ip'] == $hesklang['unknown'])
{
	hesk_dbQuery("UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` SET `ip` = '".hesk_dbEscape(hesk_getClientIP())."' WHERE `id`=".intval($ticket['id']));
}

/* Get category name and ID */
$result = hesk_dbQuery("SELECT `name` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."categories` WHERE `id`='".intval($ticket['category'])."' LIMIT 1");

/* If this category has been deleted use the default category with ID 1 */
if (hesk_dbNumRows($result) != 1)
{
	$result = hesk_dbQuery("SELECT `name` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."categories` WHERE `id`='1' LIMIT 1");
}

$category = hesk_dbFetchAssoc($result);

/* Get replies */
$result  = hesk_dbQuery("SELECT * FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."replies` WHERE `replyto`='".intval($ticket['id'])."' ORDER BY `id` ".($hesk_settings['new_top'] ? 'DESC' : 'ASC') );
$replies = hesk_dbNumRows($result);
$repliesArray = array();
$unread_replies = array();
while ($row = hesk_dbFetchAssoc($result)) {
    if ($row['staffid'] && !$row['read'])
    {
        $unread_replies[] = $row['id'];
    }
    $repliesArray[] = $row;
}
/* If needed update unread replies as read for staff to know */
if (count($unread_replies))
{
    hesk_dbQuery("UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."replies` SET `read` = '1' WHERE `id` IN ('".implode("','", $unread_replies)."')");
}

// Demo mode
if ( defined('HESK_DEMO') )
{
	$ticket['email'] = 'hidden@demo.com';
}

$messages = hesk_get_messages();

$custom_fields_before_message = array();
$custom_fields_after_message = array();
foreach ($hesk_settings['custom_fields'] as $k=>$v) {
    if ($v['use']==1 && hesk_is_custom_field_in_category($k, $ticket['category']))
    {
        $custom_field = array(
            'name' => $v['name'],
            'name:' => $v['name:'],
            'value' => $ticket[$k],
            'type' => $v['type']
        );

        if ($v['type'] == 'date') {
            $custom_field['date_format'] = $v['value']['date_format'];
        }


        if ($v['place'] == 1) {
            $custom_fields_after_message[] = $custom_field;
        } else {
            $custom_fields_before_message[] = $custom_field;
        }
    }
}

$hesk_settings['render_template'](TEMPLATE_PATH . 'customer/view-ticket/view-ticket.php', array(
    'messages' => $messages,
    'ticketJustReopened' => isset($_SESSION['force_form_top']),
    'ticket' => $ticket,
    'trackingID' => $trackingID,
    'numberOfReplies' => $replies,
    'replies' => $repliesArray,
    'category' => $category,
    'email' => $my_email,
    'customFieldsBeforeMessage' => $custom_fields_before_message,
    'customFieldsAfterMessage' => $custom_fields_after_message
));
unset($_SESSION['force_form_top']);

/* Clear unneeded session variables */
hesk_cleanSessionVars('ticket_message');

/*** START FUNCTIONS ***/

function print_form()
{
	global $hesk_settings, $hesklang;
    global $hesk_error_buffer, $my_email, $trackingID, $do_remember, $display;

	/* Print header */
	$hesk_settings['tmp_title'] = $hesk_settings['hesk_title'] . ' - ' . $hesklang['view_ticket'];

	$messages = hesk_get_messages();

	$hesk_settings['render_template'](TEMPLATE_PATH . 'customer/view-ticket/form.php', array(
        'messages' => $messages,
        'trackingId' => $trackingID,
        'email' => $my_email,
        'rememberEmail' => $do_remember,
        'displayForgotTrackingIdForm' => !empty($_GET['forgot']),
        'submittedForgotTrackingIdForm' => $display === 'block'
    ));

exit();
} // End print_form()
