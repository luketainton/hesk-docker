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

/* Check if this is a valid include */
if (!defined('IN_SCRIPT')) {die('Invalid attempt');} 

// Load custom fields
require_once(HESK_PATH . 'inc/custom_fields.inc.php');

// Load statuses
require_once(HESK_PATH . 'inc/statuses.inc.php');

// Prepare total counts that we will use later
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

// Let's check some permissions
$can_view_unassigned = hesk_checkPermission('can_view_unassigned',0);
$can_view_ass_others = hesk_checkPermission('can_view_ass_others',0);
$can_view_ass_by = hesk_checkPermission('can_view_ass_by',0);

// Is this a quick link?
$is_quick_link = hesk_GET('ql', false);

// This will get number of ALL tickets this user has access to
$sql = "SELECT COUNT(*) AS `cnt`, IF (`status` = 3, 1, 0) AS `is_resolved`
        FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets`
        WHERE ".hesk_myCategories()." AND ".hesk_myOwnership()."
        GROUP BY `is_resolved`";
$res = hesk_dbQuery($sql);

while ($row = hesk_dbFetchAssoc($res))
{
    // Total tickets found
    $totals['all'] += $row['cnt'];

    // Total by status
    if ($row['is_resolved'])
    {
        $totals['resolved'] += $row['cnt'];
    }
    else
    {
        $totals['open'] = $row['cnt'];
    }
}

$sql_final = ''; // SQL that fetches ticket data from the database
$sql_count = ''; // SQL that runs a quick count of tickets by status, due date and ownership

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

// This is common SQL for all queries
$sql = "";

// Some default settings
$archive = array(1=>0,2=>0);
$s_my = array(1=>1,2=>1);
$s_ot = array(1=>1,2=>1);
$s_un = array(1=>1,2=>1);

// For some specific quick links we will ignore some filters
$ignore_category = false;
$ignore_status = false;
$ignore_owner = false;
$ignore_archive = false;
$ignore_category = false;

// -> All tickets
if ($is_quick_link == 'all')
{
    $ignore_category = true;
    $ignore_status = true;
    $ignore_owner = true;
    $ignore_archive = true;
    $ignore_category = true;
}
// -> All open tickets
elseif ($is_quick_link == 'alo')
{
    $ignore_category = true;
    $ignore_owner = true;
    $ignore_archive = true;
    $ignore_category = true;
}

// --> TICKET CATEGORY
$category = intval( hesk_GET('category', 0) );
if ( ! $ignore_category && $category && hesk_okCategory($category, 0) )
{
    $sql .= " AND `category`='{$category}' ";
}

// Show only tagged tickets?
if ( ! $ignore_archive && ! empty($_GET['archive']) )
{
	$archive[1]=1;
	$sql .= " AND `archive`='1' ";
}

$sql_count .= $sql;

// Ticket owner preferences
$fid = 1;
require(HESK_PATH . 'inc/assignment_search.inc.php');

// --> TICKET STATUS
$status = $hesk_settings['statuses'];

// Process statuses unless overridden with "s_all" variable
if ( ! hesk_GET('s_all') )
{
	foreach ($status as $k => $v)
	{
		if (empty($_GET['s'.$k]))
		{
			unset($status[$k]);
	    }
	}
}

// How many statuses are we pulling out of the database?
$tmp = count($status);

// Do we need to search by status?
if ( $tmp < count($hesk_settings['statuses']) )
{
	// If no statuses selected, show default (all except RESOLVED)
	if ($tmp == 0)
	{
		$status = $hesk_settings['statuses'];
		unset($status[3]);
	}

	// Add to the SQL
	$sql .= " AND `status` IN ('" . implode("','", array_keys($status) ) . "') ";
    $sql_count .= " AND `status` IN ('" . implode("','", array_keys($status) ) . "') ";
}

// --> TICKET PRIORITY
$possible_priority = array(
0 => 'CRITICAL',
1 => 'HIGH',
2 => 'MEDIUM',
3 => 'LOW',
);

$priority = $possible_priority;

foreach ($priority as $k => $v)
{
	if (empty($_GET['p'.$k]))
    {
    	unset($priority[$k]);
    }
}

// How many priorities are we pulling out of the database?
$tmp = count($priority);

// Create the SQL based on the number of priorities we need
if ($tmp == 0 || $tmp == 4)
{
	// Nothing or all selected, no need to modify the SQL code
    $priority = $possible_priority;
}
else
{
	// A custom selection of priorities
	$sql .= " AND `priority` IN ('" . implode("','", array_keys($priority) ) . "') ";
    $sql_count .= " AND `priority` IN ('" . implode("','", array_keys($priority) ) . "') ";
}

// Due date
if ($is_quick_link == 'due')
{
    $sql .= " AND `status` != 3 AND `due_date` BETWEEN NOW() AND (NOW() + INTERVAL ".intval($hesk_settings['due_soon'])." DAY) ";
}
elseif ($is_quick_link == 'ovr')
{
    $sql .= " AND `status` != 3 AND `due_date` < NOW() ";
}

// That's all the SQL we need for count
$sql = $sql_final . $sql;

// Prepare variables used in search and forms
require(HESK_PATH . 'inc/prepare_ticket_search.inc.php');

// We need to group the count SQL by parameters to be able to extract different totals
$sql_count .= " GROUP BY `assigned_to`, `due`, `status` ";

// List tickets?
if (!isset($_SESSION['hide']['ticket_list']))
{
	require(HESK_PATH . 'inc/ticket_list.inc.php');
}
