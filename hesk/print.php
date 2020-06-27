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

/* Get all the required files and functions */
require(HESK_PATH . 'hesk_settings.inc.php');
define('TEMPLATE_PATH', HESK_PATH . "theme/{$hesk_settings['site_theme']}/");
require(HESK_PATH . 'inc/common.inc.php');
hesk_load_database_functions();

hesk_session_start();

// Do we have parameters in query string? If yes, store them in session and redirect
if ( isset($_GET['track']) || isset($_GET['e']) )
{
    $_SESSION['p_track'] = hesk_GET('track');
    $_SESSION['p_email'] = hesk_GET('e');

    header('Location: print.php');
    die();
}

/* Get the tracking ID */
$trackingID = hesk_cleanID('p_track') or die("$hesklang[int_error]: $hesklang[no_trackID]");

/* Connect to database */
hesk_dbConnect();

// Load custom fields
require_once(HESK_PATH . 'inc/custom_fields.inc.php');

// Load statuses
require_once(HESK_PATH . 'inc/statuses.inc.php');

// Perform additional checks for customers
if ( empty($_SESSION['id']) )
{
	// Are we in maintenance mode?
	hesk_check_maintenance();

	// Verify email address match
    $my_email = hesk_getCustomerEmail(0, 'p_email');
    hesk_verifyEmailMatch($trackingID, $my_email);
}

/* Clean ticket parameters from the session data, we don't need them anymore */
hesk_cleanSessionVars( array('p_track', 'p_email') );

/* Get ticket info */
$res = hesk_dbQuery("SELECT `t1`.* , `t2`.name AS `repliername`
					FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` AS `t1` LEFT JOIN `".hesk_dbEscape($hesk_settings['db_pfix'])."users` AS `t2` ON `t1`.`replierid` = `t2`.`id`
					WHERE `trackid`='".hesk_dbEscape($trackingID)."' LIMIT 1");

if (hesk_dbNumRows($res) != 1)
{
	hesk_error($hesklang['ticket_not_found']);
}
$ticket = hesk_dbFetchAssoc($res);

// Demo mode
if ( defined('HESK_DEMO') )
{
	$ticket['email'] = 'hidden@demo.com';
	$ticket['ip']	 = '127.0.0.1';
}

/* Get category name and ID */
$res = hesk_dbQuery("SELECT * FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."categories` WHERE `id`='{$ticket['category']}' LIMIT 1");

/* If this category has been deleted use the default category with ID 1 */
if (hesk_dbNumRows($res) != 1)
{
	$res = hesk_dbQuery("SELECT * FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."categories` WHERE `id`='1' LIMIT 1");
}
$category = hesk_dbFetchAssoc($res);

/* Get replies */
$res  = hesk_dbQuery("SELECT * FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."replies` WHERE `replyto`='{$ticket['id']}' ORDER BY `id` ASC");

/* Get notes */
$notes = array();
if (!empty($_SESSION['id']))
{
    $res2 = hesk_dbQuery("SELECT t1.*, t2.`name` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."notes` AS t1 LEFT JOIN `".hesk_dbEscape($hesk_settings['db_pfix'])."users` AS t2 ON t1.`who` = t2.`id` WHERE `ticket`='{$ticket['id']}' ORDER BY t1.`id`");
    while ($note = hesk_dbFetchAssoc($res2))
    {
        $notes[] = $note;
    }
}

$ticket['notes'] = $notes;
$ticket['replies'] = $res;
$ticket['categoryName'] = $category['name'];

$tickets = array($ticket);
require_once(HESK_PATH . 'inc/print_template.inc.php');

