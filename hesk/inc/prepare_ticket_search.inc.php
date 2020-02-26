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

$tmp = intval( hesk_GET('limit') );
$maxresults = ($tmp > 0) ? $tmp : $hesk_settings['max_listings'];

$tmp = intval( hesk_GET('page', 1) );
$page = ($tmp > 1) ? $tmp : 1;

/* Acceptable $sort values and default asc(1)/desc(0) setting */
$sort_possible = array();
foreach (array_keys($hesk_settings['possible_ticket_list']) as $key)
{
	$sort_possible[$key] = 1;
}
$sort_possible['priority'] = 1;
$sort_possible['dt'] = 0;
$sort_possible['lastchange'] = 0;

/* These values should have collate appended in SQL */
$sort_collation = array(
'name',
'subject',
'custom1',
'custom2',
'custom3',
'custom4',
'custom5',
'custom6',
'custom7',
'custom8',
'custom9',
'custom10',
'custom11',
'custom12',
'custom13',
'custom14',
'custom15',
'custom16',
'custom17',
'custom18',
'custom19',
'custom20',
'custom21',
'custom22',
'custom23',
'custom24',
'custom25',
'custom26',
'custom27',
'custom28',
'custom29',
'custom30',
'custom31',
'custom32',
'custom33',
'custom34',
'custom35',
'custom36',
'custom37',
'custom38',
'custom39',
'custom40',
'custom41',
'custom42',
'custom43',
'custom44',
'custom45',
'custom46',
'custom47',
'custom48',
'custom49',
'custom50',
);

/* Acceptable $group values and default asc(1)/desc(0) setting */
$group_possible = array(
'owner' 		=> 1,
'priority' 		=> 1,
'category' 		=> 1,
'custom1'		=> 1,
'custom2'		=> 1,
'custom3'		=> 1,
'custom4'		=> 1,
'custom5'		=> 1,
'custom6'		=> 1,
'custom7'		=> 1,
'custom8'		=> 1,
'custom9'		=> 1,
'custom10'		=> 1,
'custom11'		=> 1,
'custom12'		=> 1,
'custom13'		=> 1,
'custom14'		=> 1,
'custom15'		=> 1,
'custom16'		=> 1,
'custom17'		=> 1,
'custom18'		=> 1,
'custom19'		=> 1,
'custom20'		=> 1,
'custom21'		=> 1,
'custom22'		=> 1,
'custom23'		=> 1,
'custom24'		=> 1,
'custom25'		=> 1,
'custom26'		=> 1,
'custom27'		=> 1,
'custom28'		=> 1,
'custom29'		=> 1,
'custom30'		=> 1,
'custom31'		=> 1,
'custom32'		=> 1,
'custom33'		=> 1,
'custom34'		=> 1,
'custom35'		=> 1,
'custom36'		=> 1,
'custom37'		=> 1,
'custom38'		=> 1,
'custom39'		=> 1,
'custom40'		=> 1,
'custom41'		=> 1,
'custom42'		=> 1,
'custom43'		=> 1,
'custom44'		=> 1,
'custom45'		=> 1,
'custom46'		=> 1,
'custom47'		=> 1,
'custom48'		=> 1,
'custom49'		=> 1,
'custom50'		=> 1,
);

/* Start the order by part of the SQL query */
$sql .= " ORDER BY ";

// Group parameter
$group = hesk_GET('g');
if ( ! isset($group_possible[$group]))
{
    $group = '';
}

// Sort parameter
$sort = hesk_GET('sort', 'status');
if ( ! isset($sort_possible[$sort]))
{
    $sort = 'status';
}

// Group tickets?
if ($group != '')
{
    if ($group == 'priority' && $sort == 'priority')
    {
		// No need to group by priority if we are already sorting by priority
    }
    elseif ($group == 'owner')
    {
		// If group by owner place own tickets on top
		$sql .= " CASE WHEN `owner` = '".intval($_SESSION['id'])."' THEN 1 ELSE 0 END DESC, `owner` ASC, ";
    }
    elseif ($group == 'category' && $sort == 'category')
    {
        // No need to group by category if we are already sorting by category
    }
    elseif ($group == 'category')
    {
        // Get list of categories
        $hesk_settings['categories'] = array();
        $res2 = hesk_dbQuery('SELECT `id`, `name` FROM `'.hesk_dbEscape($hesk_settings['db_pfix']).'categories` WHERE ' . hesk_myCategories('id') . ' ORDER BY `cat_order` ASC');
        while ($row=hesk_dbFetchAssoc($res2))
        {
            $hesk_settings['categories'][$row['id']] = $row['name'];
        }

        // Make sure categories are in correct order
        $sql .= ' FIELD(`category`, ' . preg_replace('/[^0-9,]/', '', implode(',' , array_keys($hesk_settings['categories']))) . '), ';
    }
    else
    {
	    $sql .= ' `'.hesk_dbEscape($group).'` ';
	    $sql .= $group_possible[$group] ? 'ASC, ' : 'DESC, ';
    }
}

// Show critical tickets always on top? Default: yes
$cot = hesk_GET('cot') == 1 ? 1 : 0;
if (!$cot)
{
	$sql .= " CASE WHEN `priority` = '0' THEN 1 ELSE 0 END DESC , ";
}

// Prepare sorting
if ($sort == 'category')
{
    // Get list of categories
    $hesk_settings['categories'] = array();
    $res2 = hesk_dbQuery('SELECT `id`, `name` FROM `'.hesk_dbEscape($hesk_settings['db_pfix']).'categories` WHERE ' . hesk_myCategories('id') . ' ORDER BY `cat_order` ASC');
    while ($row=hesk_dbFetchAssoc($res2))
    {
        $hesk_settings['categories'][$row['id']] = $row['name'];
    }

    // Make sure categories are in correct order
    $sql .= ' FIELD(`category`, ' . preg_replace('/[^0-9,]/', '', implode(',' , array_keys($hesk_settings['categories']))) . ') ';
}
else
{
    $sql .= $sort == 'lastreplier' ? " CASE WHEN `lastreplier` = '0' THEN 0 ELSE 1 END DESC, COALESCE(`replierid`, NULLIF(`lastreplier`, '0'), `name`) " : ' `'.hesk_dbEscape($sort).'` ';

    // Need to set MySQL collation?
    if ( in_array($sort, $sort_collation) )
    {
    	$sql .= " COLLATE '" . hesk_dbCollate() . "' ";
    }
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

/* In the end same results should always be sorted by priority */
if ($sort != 'priority')
{
	$sql .= ' , `priority` ASC ';
}

# Uncomment for debugging purposes
# echo "SQL: $sql<br>";
