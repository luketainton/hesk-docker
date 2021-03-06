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

define('CALENDAR',1);
$_SESSION['hide']['ticket_list'] = true;

/* Check permissions for this feature */
hesk_checkPermission('can_view_tickets');

$_SERVER['PHP_SELF'] = './admin_main.php';

// Load custom fields
require_once(HESK_PATH . 'inc/custom_fields.inc.php');

// Load statuses
require_once(HESK_PATH . 'inc/statuses.inc.php');

/* Print header */
require_once(HESK_PATH . 'inc/header.inc.php');

/* Print admin navigation */
require_once(HESK_PATH . 'inc/show_admin_nav.inc.php');
?>
<div class="main__content tickets">
    <div style="margin-left: -16px; margin-right: -24px;">
        <?php

        /* This will handle error, success and notice messages */
        hesk_handle_messages();
        ?>
    </div>
<?php
// This SQL code will be used to retrieve results
$sql_final = "SELECT
`id`,
`trackid`,
`name`,
`email`,
`category`,
`priority`,
`subject`,
LEFT(`message`, 400) AS `message`,
`dt`,
`lastchange`,
`firstreply`,
`closedat`,
`status`,
`openedby`,
`firstreplyby`,
`closedby`,
`replies`,
`staffreplies`,
`owner`,
`time_worked`,
`due_date`,
`lastreplier`,
`replierid`,
`archive`,
`locked`
";

foreach ($hesk_settings['custom_fields'] as $k=>$v)
{
	if ($v['use'])
	{
		$sql_final .= ", `".$k."`";
	}
}

$sql_final.= " FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` WHERE ".hesk_myCategories()." AND ".hesk_myOwnership();

// This code will be used to count number of results for this specific search
$sql_count = " SELECT COUNT(*) AS `cnt`, `status`,
                      IF (`owner` = " . intval($_SESSION['id']) . ", 1, IF (`owner` = 0, 0, IF (`assignedby` = " . intval($_SESSION['id']) . ", 3, 2) ) ) AS `assigned_to`,
                      IF (`due_date` < NOW(), 2, IF (`due_date` BETWEEN NOW() AND (NOW() + INTERVAL ".intval($hesk_settings['due_soon'])." DAY), 1, 0) ) AS `due`
                FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets`
                WHERE ".hesk_myCategories()." AND ".hesk_myOwnership();

// This is common SQL for both queries
$sql = "";

// Some default settings
$archive = array(1=>0,2=>0);
$s_my = array(1=>1,2=>1);
$s_ot = array(1=>1,2=>1);
$s_un = array(1=>1,2=>1);

// Is this a quick link?
$is_quick_link = hesk_GET('ql', false);

// --> TICKET CATEGORY
$category = intval( hesk_GET('category', 0) );

// Make sure user has access to this category
if ($category && hesk_okCategory($category, 0) )
{
    $sql .= " AND `category`='{$category}' ";
}

// Show only tagged tickets?
if ( ! empty($_GET['archive']) )
{
	$archive[2]=1;
	$sql .= " AND `archive`='1' ";
}

$sql_count .= $sql;

// Ticket owner preferences
$fid = 2;
require(HESK_PATH . 'inc/assignment_search.inc.php');

$hesk_error_buffer = '';
$no_query = 0;

// Search query
$q = hesk_input( hesk_GET('q', '') );

// No query entered?
if ( ! strlen($q) )
{
	$hesk_error_buffer .= $hesklang['fsq'];
	$no_query = 1;
}

// What field are we searching in
$what = hesk_GET('what', '') or $hesk_error_buffer .= '<br />' . $hesklang['wsel'];

// Sequential ID supported?
if ($what == 'seqid' && ! $hesk_settings['sequential'])
{
	$what = 'trackid';
}

// Setup SQL based on searching preferences
if ( ! $no_query)
{
    $sql_previous = $sql;
    $sql = " AND ";

	switch ($what)
	{
		case 'trackid':
		    $sql  .= " ( `trackid` = '".hesk_dbEscape($q)."' OR `merged` LIKE '%#".hesk_dbEscape($q)."#%' ) ";
		    break;
		case 'name':
		    $sql  .= "`name` LIKE '%".hesk_dbEscape( hesk_dbLike($q) )."%' COLLATE '" . hesk_dbCollate() . "' ";
		    break;
		case 'email':
	         $sql  .= "`email` LIKE '%".hesk_dbEscape($q)."%' ";
			 break;
		case 'subject':
		    $sql  .= "`subject` LIKE '%".hesk_dbEscape( hesk_dbLike($q) )."%' COLLATE '" . hesk_dbCollate() . "' ";
		    break;
		case 'message':
		    $sql  .= " ( `message` LIKE '%".hesk_dbEscape( hesk_dbLike($q) )."%' COLLATE '" . hesk_dbCollate() . "'
            		OR
                    `id` IN (
            		SELECT DISTINCT `replyto`
                	FROM   `".hesk_dbEscape($hesk_settings['db_pfix'])."replies`
                	WHERE  `message` LIKE '%".hesk_dbEscape( hesk_dbLike($q) )."%' COLLATE '" . hesk_dbCollate() . "' )
                    )
                    ";
		    break;
		case 'seqid':
	         $sql  .= "`id` = '".intval($q)."' ";
			 break;
		case 'notes':
		    $sql  .= "`id` IN (
            		SELECT DISTINCT `ticket`
                	FROM   `".hesk_dbEscape($hesk_settings['db_pfix'])."notes`
                	WHERE  `message` LIKE '%".hesk_dbEscape( hesk_dbLike($q) )."%' COLLATE '" . hesk_dbCollate() . "' )
                	";
		    break;
		case 'ip':
	         $sql  .= "`ip` LIKE '".preg_replace('/[^0-9\.\%]/', '', $q)."' ";
			 break;
		default:
	    	if (isset($hesk_settings['custom_fields'][$what]) && $hesk_settings['custom_fields'][$what]['use'])
	        {
	        	$sql .= "`".hesk_dbEscape($what)."` LIKE '%".hesk_dbEscape($q)."%' COLLATE '" . hesk_dbCollate() . "' ";
	        }
	        else
	        {
	        	$hesk_error_buffer .= '<br />' . $hesklang['invalid_search'];
	        }
	}

    $sql_count .= $sql;
    $sql = $sql_previous . $sql;
}

// Owner
if ( $tmp = intval( hesk_GET('owner', 0) ) )
{
	$sql .= " AND `owner`={$tmp} ";
    $sql_count .= " AND `owner`={$tmp} ";
	$owner_input = $tmp;
	$hesk_error_buffer = str_replace($hesklang['fsq'],'',$hesk_error_buffer);
}
else
{
	$owner_input = 0;
}

/* Date */
/* -> Check for compatibility with old date format */
if (preg_match("/(\d{4})-(\d{2})-(\d{2})/", hesk_GET('dt'), $m))
{
	$_GET['dt']=$m[2].$m[3].$m[1];
}

/* -> Now process the date value */
$dt = preg_replace('/[^0-9]/','', hesk_GET('dt') );
if (strlen($dt) == 8)
{
	$date = substr($dt,4,4) . '-' . substr($dt,0,2) . '-' . substr($dt,2,2);
	$date_input= substr($dt,0,2) . '/' . substr($dt,2,2) . '/' . substr($dt,4,4);

	/* This search is valid even if no query is entered */
	if ($no_query)
	{
		$hesk_error_buffer = str_replace($hesklang['fsq'],'',$hesk_error_buffer);
	}

	$sql .= " AND `dt` BETWEEN '{$date} 00:00:00' AND '{$date} 23:59:59' ";
    $sql_count .= " AND `dt` BETWEEN '{$date} 00:00:00' AND '{$date} 23:59:59' ";
}
else
{
	$date = '';
    $date_input = '';
}

/* Any errors? */
if (strlen($hesk_error_buffer))
{
	hesk_process_messages($hesk_error_buffer,'NOREDIRECT');
}

/* This will handle error, success and notice messages */
$handle = hesk_handle_messages();

// Due date
if ($is_quick_link == 'due')
{
    $sql .= " AND `status` != 3 AND `due_date` BETWEEN NOW() AND (NOW() + INTERVAL ".intval($hesk_settings['due_soon'])." DAY) ";
}
elseif ($is_quick_link == 'ovr')
{
    $sql .= " AND `status` != 3 AND `due_date` < NOW() ";
}

// Complete the required SQL queries
$sql = $sql_final . $sql;
$sql_count .= " GROUP BY `assigned_to`, `due`, `status` ";

// Strip extra slashes
$q = stripslashes($q);

/* Prepare variables used in search and forms */
require_once(HESK_PATH . 'inc/prepare_ticket_search.inc.php');

/* If there has been an error message skip searching for tickets */
if ($handle !== FALSE)
{
    $totals = array(
        'all' => 0,
        'open' => 0,
        'resolved' => 0,
        'filtered' => array(
            'all' => 0,
            'open' => 0,
            'assigned_to_me' => 0,
            'assigned_to_others' => 0,
            'assigned_to_others_by_me' => 0,
            'unassigned' => 0,
            'due_soon' => 0,
            'overdue' => 0,
            'by_status' => array()
        ),
    );

    $can_view_unassigned = hesk_checkPermission('can_view_unassigned',0);
    $can_view_ass_others = hesk_checkPermission('can_view_ass_others',0);
    $can_view_ass_by = hesk_checkPermission('can_view_ass_by',0);

	$href = 'find_tickets.php';
	require_once(HESK_PATH . 'inc/ticket_list.inc.php');
}

/* Clean unneeded session variables */
hesk_cleanSessionVars('hide');

/* Show the search form */
require_once(HESK_PATH . 'inc/show_search_form.inc.php');

/* Print footer */
require_once(HESK_PATH . 'inc/footer.inc.php');
exit();
?>
