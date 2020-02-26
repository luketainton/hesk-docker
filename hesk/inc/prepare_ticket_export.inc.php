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

/* Acceptable $sort values and default asc(1)/desc(0) setting */
$sort_possible = array(
'trackid' 		=> 1,
'lastchange' 	=> 0,
'name' 			=> 1,
'subject' 		=> 1,
'status' 		=> 1,
'lastreplier' 	=> 1,
'priority' 		=> 1,
'category' 		=> 1,
'dt' 			=> 0,
'id' 			=> 1,
);

// These values should have collate appended in SQL
$sort_collation = array(
'name',
'subject',
);

// DATE
$sql .= " AND DATE(`dt`) BETWEEN '" . hesk_dbEscape($date_from) . "' AND '" . hesk_dbEscape($date_to) . "' ";


// Start the order by part of the SQL query
$sql .= " ORDER BY ";

/* Sort by which field? */
if (isset($_GET['sort']) && ! is_array($_GET['sort']) && isset($sort_possible[$_GET['sort']]))
{
	$sort = hesk_input($_GET['sort']);

    $sql .= ' `'.hesk_dbEscape($sort).'` ';

    // Need to set MySQL collation?
    if ( in_array($_GET['sort'], $sort_collation) )
    {
    	$sql .= " COLLATE '" . hesk_dbCollate() . "' ";
    }
}
else
{
	/* Default sorting by ticket status */
    $sql .= ' `id` ';
    $sort = 'id';
}

/* Ascending or Descending? */
if (isset($_GET['asc']) && intval($_GET['asc'])==0)
{
    $sql .= ' DESC ';
    $asc = 0;
    $asc_rev = 1;

    $sort_possible[$sort] = 1;
}
else
{
    $sql .= ' ASC ';
    $asc = 1;
    $asc_rev = 0;
    if (!isset($_GET['asc']))
    {
    	$is_default = 1;
    }

    $sort_possible[$sort] = 0;
}

# Uncomment for debugging purposes
# echo "SQL: $sql<br>";
