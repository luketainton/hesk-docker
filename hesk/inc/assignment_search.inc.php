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

/* Assignment */
// -> SELF
$s_my[$fid] = empty($_GET['s_my']) ? 0 : 1;
// -> OTHERS
$s_ot[$fid] = empty($_GET['s_ot']) ? 0 : 1;
// -> UNASSIGNED
$s_un[$fid] = empty($_GET['s_un']) ? 0 : 1;

// Overwrite by quick links? Ignore for ticket searches
if ( ! isset($is_quick_link))
{
    $is_quick_link = false;
}
// Quick link: assigned to me
elseif ($is_quick_link == 'my')
{
    $s_my[$fid] = 1;
    $s_ot[$fid] = 0;
    $s_un[$fid] = 0;
}
// Quick link: assigned to other
elseif ($is_quick_link == 'ot')
{
    $s_my[$fid] = 0;
    $s_ot[$fid] = 1;
    $s_un[$fid] = 0;
}
// Quick link: unassigned
elseif ($is_quick_link == 'un')
{
    $s_my[$fid] = 0;
    $s_ot[$fid] = 0;
    $s_un[$fid] = 1;
}

// Is assignment selection the same as a quick link?
if ($is_quick_link === false)
{
    if ($s_my[$fid] == 1 && $s_ot[$fid] == 0 && $s_un[$fid] == 0)
    {
        $is_quick_link = 'my';
    }
    elseif ($s_my[$fid] == 0 && $s_ot[$fid] == 1 && $s_un[$fid] == 0)
    {
        $is_quick_link = 'ot';
    }
    elseif ($s_my[$fid] == 0 && $s_ot[$fid] == 0 && $s_un[$fid] == 1)
    {
        $is_quick_link = 'un';
    }
}

// -> Setup SQL based on selected ticket assignments

/* Make sure at least one is chosen */
if ( ! $s_my[$fid] && ! $s_ot[$fid] && ! $s_un[$fid])
{
	$s_my[$fid] = 1;
	$s_ot[$fid] = 1;
	$s_un[$fid] = 1;
	if (!defined('MAIN_PAGE'))
	{
		hesk_show_notice($hesklang['e_nose']);
	}
}

// Can view tickets assigned by him/her?
$s_by[$fid] = hesk_checkPermission('can_view_ass_by',0) ? $s_ot[$fid] : 0;

/* If the user doesn't have permission to view assigned to others block those */
if ( ! hesk_checkPermission('can_view_ass_others',0))
{
	$s_ot[$fid] = 0;
}

/* If the user doesn't have permission to view unassigned tickets block those */
if ( ! hesk_checkPermission('can_view_unassigned',0))
{
	$s_un[$fid] = 0;
}

/* Process assignments */
if ( ! $s_my[$fid] || ! $s_ot[$fid] || ! $s_un[$fid])
{
	if ($s_my[$fid] && $s_ot[$fid])
    {
    	// All but unassigned
    	$sql .= " AND `owner` > 0 ";
    }
    elseif ( ! $s_ot[$fid] && $s_by[$fid])
    {
        // Can't view tickets assigned to others, but can see tickets he/she assigned to someone
        if ($s_my[$fid] && $s_un[$fid])
        {
            $sql .= " AND (`owner` IN ('0', '" . intval($_SESSION['id']) . "') OR `assignedby` = " . intval($_SESSION['id']) . ") ";
        }
        elseif($s_my[$fid])
        {
            $sql .= " AND (`owner` = '" . intval($_SESSION['id']) . "' OR `assignedby` = " . intval($_SESSION['id']) . ") ";
        }
        elseif($s_un[$fid])
        {
            $sql .= " AND (`owner` = 0 OR `assignedby` = " . intval($_SESSION['id']) . ") ";
        }
        else
        {
            $sql .= " AND `assignedby` = " . intval($_SESSION['id']) . " ";
        }

        $s_ot[$fid] = 1;
    }
    elseif ($s_my[$fid] && $s_un[$fid])
    {
    	// My tickets + unassigned
    	$sql .= " AND `owner` IN ('0', '" . intval($_SESSION['id']) . "') ";
    }
    elseif ($s_ot[$fid] && $s_un[$fid])
    {
    	// Assigned to others + unassigned
    	$sql .= " AND `owner` != '" . intval($_SESSION['id']) . "' ";
    }
    elseif ($s_my[$fid])
    {
    	// Assigned to me only
    	$sql .= " AND `owner` = '" . intval($_SESSION['id']) . "' ";
    }
    elseif ($s_ot[$fid])
    {
    	// Assigned to others
    	$sql .= " AND `owner` NOT IN ('0', '" . intval($_SESSION['id']) . "') ";
    }
    elseif ($s_un[$fid])
    {
    	// Only unassigned
    	$sql .= " AND `owner` = 0 ";
    }
}
