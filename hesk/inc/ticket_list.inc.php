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

// List of staff and check their permissions
$admins = array();
$can_assign_to = array();
$res2 = hesk_dbQuery("SELECT `id`,`name`,`isadmin`,`heskprivileges` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."users` ORDER BY `name` ASC");
while ($row = hesk_dbFetchAssoc($res2))
{
    $admins[$row['id']] = $row['name'];

    if ($row['isadmin'] || strpos($row['heskprivileges'], 'can_view_tickets') !== false)
    {
        $can_assign_to[$row['id']] = $row['name'];
    }
}

/* List of categories */
if ( ! isset($hesk_settings['categories']))
{
    $hesk_settings['categories'] = array();
    $res2 = hesk_dbQuery('SELECT `id`, `name` FROM `'.hesk_dbEscape($hesk_settings['db_pfix']).'categories` WHERE ' . hesk_myCategories('id') . ' ORDER BY `cat_order` ASC');
    while ($row=hesk_dbFetchAssoc($res2))
    {
        $hesk_settings['categories'][$row['id']] = $row['name'];
    }
}

/* Current MySQL time */
$mysql_time = hesk_dbTime();

/* Get number of tickets and page number */
$result = hesk_dbQuery($sql_count);
$total  = hesk_dbResult($result);

// $header_text should come from the page including this (i.e. admin_main.php, show_tickets.php)
echo str_replace('%%HESK_TICKET_COUNT%%', $total, $header_text);

if ($total > 0)
{

	/* This query string will be used to browse pages */
	if ($href == 'show_tickets.php')
	{
		#$query  = 'status='.$status;

        $query = '';
        $query .= 's' . implode('=1&amp;s',array_keys($status)) . '=1';
        $query .= '&amp;p' . implode('=1&amp;p',array_keys($priority)) . '=1';

		$query .= '&amp;category='.$category;
		$query .= '&amp;sort='.$sort;
		$query .= '&amp;asc='.$asc;
		$query .= '&amp;limit='.$maxresults;
		$query .= '&amp;archive='.$archive[1];
		$query .= '&amp;s_my='.$s_my[1];
		$query .= '&amp;s_ot='.$s_ot[1];
		$query .= '&amp;s_un='.$s_un[1];

		$query .= '&amp;cot='.$cot;
		$query .= '&amp;g='.$group;

		$query .= '&amp;page=';
	}
	else
	{
		$query  = 'q='.$q;
	    $query .= '&amp;what='.$what;
		$query .= '&amp;category='.$category;
        $query .= '&amp;owner='.$owner_input;
		$query .= '&amp;dt='.urlencode($date_input);
		$query .= '&amp;sort='.$sort;
		$query .= '&amp;asc='.$asc;
		$query .= '&amp;limit='.$maxresults;
		$query .= '&amp;archive='.$archive[2];
		$query .= '&amp;s_my='.$s_my[2];
		$query .= '&amp;s_ot='.$s_ot[2];
		$query .= '&amp;s_un='.$s_un[2];
		$query .= '&amp;page=';
	}
	$query_for_pagination = $query;

	$pages = ceil($total/$maxresults) or $pages = 1;
	if ($page > $pages)
	{
		$page = $pages;
	}
	$limit_down = ($page * $maxresults) - $maxresults;

	$prev_page = ($page - 1 <= 0) ? 0 : $page - 1;
	$next_page = ($page + 1 > $pages) ? 0 : $page + 1;

	/* We have the full SQL query now, get tickets */
	$sql .= " LIMIT ".hesk_dbEscape($limit_down)." , ".hesk_dbEscape($maxresults)." ";
	$result = hesk_dbQuery($sql);

    /* Uncomment for debugging */
    # echo "SQL: $sql\n<br>";

	/* This query string will be used to order and reverse display */
	if ($href == 'show_tickets.php')
	{
		#$query  = 'status='.$status;

        $query = '';
        $query .= 's' . implode('=1&amp;s',array_keys($status)) . '=1';
        $query .= '&amp;p' . implode('=1&amp;p',array_keys($priority)) . '=1';

		$query .= '&amp;category='.$category;
		#$query .= '&amp;asc='.(isset($is_default) ? 1 : $asc_rev);
		$query .= '&amp;limit='.$maxresults;
		$query .= '&amp;archive='.$archive[1];
		$query .= '&amp;s_my='.$s_my[1];
		$query .= '&amp;s_ot='.$s_ot[1];
		$query .= '&amp;s_un='.$s_un[1];
		$query .= '&amp;page=1';
		#$query .= '&amp;sort=';

		$query .= '&amp;cot='.$cot;
		$query .= '&amp;g='.$group;

	}
	else
	{
		$query  = 'q='.$q;
	    $query .= '&amp;what='.$what;
		$query .= '&amp;category='.$category;
        $query .= '&amp;owner='.$owner_input;
		$query .= '&amp;dt='.urlencode($date_input);
		#$query .= '&amp;asc='.$asc;
		$query .= '&amp;limit='.$maxresults;
		$query .= '&amp;archive='.$archive[2];
		$query .= '&amp;s_my='.$s_my[2];
		$query .= '&amp;s_ot='.$s_ot[2];
		$query .= '&amp;s_un='.$s_un[2];
		$query .= '&amp;page=1';
		#$query .= '&amp;sort=';
	}

    $query .= '&amp;asc=';

	/* Print the table with tickets */
	$random=rand(10000,99999);

	$modal_id = hesk_generate_delete_modal($hesklang['confirm'],
        $hesklang['confirm_execute'],
    "javascript:document.getElementById('delete-tickets-form').submit()",
        $hesklang['confirm']);
	?>

	<form name="form1" id="delete-tickets-form" action="delete_tickets.php" method="post">

    <?php
    if (empty($group))
    {
		hesk_print_list_head();
    }

	$checkall = '
    <div class="checkbox-custom">
        <input type="checkbox" id="ticket_checkall" onclick="hesk_changeAll()">
        <label for="ticket_checkall">&nbsp;</label>
    </div>';

    $group_tmp = '';
	$is_table = 0;
	$space = 0;

	while ($ticket=hesk_dbFetchAssoc($result))
	{
		// Are we grouping tickets?
		if ($group)
        {
			require(HESK_PATH . 'inc/print_group.inc.php');
        }

		// Set owner (needed for row title)
		$owner = '';
        $first_line = '(' . $hesklang['unas'] . ')'." \n\n";
		if ($ticket['owner'] == $_SESSION['id'])
		{
			$owner = '<svg class="icon icon-assign" style="margin-right: 3px">
                    <use xlink:href="'. HESK_PATH . 'img/sprite.svg#icon-assign"></use>
                </svg>';
            $first_line = $hesklang['tasy2'] . " \n\n";
		}
		elseif ($ticket['owner'])
		{
        	if (!isset($admins[$ticket['owner']]))
            {
            	$admins[$ticket['owner']] = $hesklang['e_udel'];
            }
			$owner = '<svg class="icon icon-assign-plus" style="margin-right: 3px">
                    <use xlink:href="'. HESK_PATH . 'img/sprite.svg#icon-assign-plus"></use>
                </svg>';
            $first_line = $hesklang['taso3'] . ' ' . $admins[$ticket['owner']] . " \n\n";
		}

		// Prepare ticket priority
		switch ($ticket['priority'])
		{
			case 0:
				$ticket['priority'] = 'critical';
				break;
			case 1:
                $ticket['priority'] = 'high';
				break;
			case 2:
				$ticket['priority'] = 'medium';
				break;
			default:
				$ticket['priority'] = 'low';
		}		

		// Set message (needed for row title)
		$ticket['message'] = $first_line . hesk_mb_substr(strip_tags($ticket['message']),0,200).'...';

		// Start ticket row
		echo '
		<tr title="'.$ticket['message'].'"'.($ticket['owner'] ? '' : ' class="new"').'>
		<td class="table__first_th sindu_handle">
            <div class="checkbox-custom">
                <input type="checkbox" id="ticket_check_'.$ticket['id'].'" name="id[]" value="'.$ticket['id'].'">
                <label for="ticket_check_'.$ticket['id'].'">&nbsp;</label>
            </div>
        </td>
		';

		// Print sequential ID and link it to the ticket page
		if ( hesk_show_column('id') )
		{
			echo '<td><a href="admin_ticket.php?track='.$ticket['trackid'].'&amp;Refresh='.$random.'">'.$ticket['id'].'</a></td>';
		}

		// Print tracking ID and link it to the ticket page
		if ( hesk_show_column('trackid') )
		{
			echo '<td class="trackid">
                <div class="table__td-id">
                    <a class="link" href="admin_ticket.php?track='.$ticket['trackid'].'&amp;Refresh='.$random.'">'.$ticket['trackid'].'</a>
                </div>
            </td>';
		}

		// Print date submitted
		if ( hesk_show_column('dt') )
		{
			switch ($hesk_settings['submittedformat'])
			{
	        	case 1:
					$ticket['dt'] = hesk_formatDate($ticket['dt']);
					break;
				case 2:
					$ticket['dt'] = hesk_time_lastchange($ticket['dt']);
					break;
				default:
					$ticket['dt'] = hesk_time_since( strtotime($ticket['dt']) );
			}
			echo '<td>'.$ticket['dt'].'</td>';
		}

		// Print last modified
		if ( hesk_show_column('lastchange') )
		{
			switch ($hesk_settings['updatedformat'])
			{
	        	case 1:
					$ticket['lastchange'] = hesk_formatDate($ticket['lastchange']);
					break;
				case 2:
					$ticket['lastchange'] = hesk_time_lastchange($ticket['lastchange']);
					break;
				default:
					$ticket['lastchange'] = hesk_time_since( strtotime($ticket['lastchange']) );
			}
			echo '<td>'.$ticket['lastchange'].'</td>';
		}

		// Print ticket category
		if ( hesk_show_column('category') )
		{
			$ticket['category'] = isset($hesk_settings['categories'][$ticket['category']]) ? $hesk_settings['categories'][$ticket['category']] : $hesklang['catd'];
			echo '<td>'.$ticket['category'].'</td>';
		}

		// Print customer name
		if ( hesk_show_column('name') )
		{
			echo '<td>'.$ticket['name'].'</td>';
		}

		// Print customer email
		if ( hesk_show_column('email') )
		{
			echo '<td><a href="mailto:'.$ticket['email'].'">'.$hesklang['clickemail'].'</a></td>';
		}

		// Print subject and link to the ticket page
		if ( hesk_show_column('subject') )
		{
			echo '<td class="subject">'.($ticket['archive'] ? '<svg class="icon icon-tag" style="margin-right: 3px">
                        <use xlink:href="'. HESK_PATH .'img/sprite.svg#icon-tag"></use>
                    </svg>' : '').$owner.'<a class="link" href="admin_ticket.php?track='.$ticket['trackid'].'&amp;Refresh='.$random.'">'.$ticket['subject'].'</a></td>';
		}

		// Print ticket status
		if ( hesk_show_column('status') )
		{
            echo '<td>' . hesk_get_admin_ticket_status($ticket['status']) . '&nbsp;</td>';
		}

		// Print ticket owner
		if ( hesk_show_column('owner') )
		{
			if ($ticket['owner'])
			{
				$ticket['owner'] = isset($admins[$ticket['owner']]) ? $admins[$ticket['owner']] : $hesklang['unas'];
			}
			else
			{
				$ticket['owner'] = $hesklang['unas'];
			}
			echo '<td>'.$ticket['owner'].'</td>';
		}

		// Print number of all replies
		if ( hesk_show_column('replies') )
		{
			echo '<td>'.$ticket['replies'].'</td>';
		}

		// Print number of staff replies
		if ( hesk_show_column('staffreplies') )
		{
			echo '<td>'.$ticket['staffreplies'].'</td>';
		}

		// Print last replier
		if ( hesk_show_column('lastreplier') )
		{
			if ($ticket['lastreplier'])
			{
				$ticket['repliername'] = isset($admins[$ticket['replierid']]) ? $admins[$ticket['replierid']] : $hesklang['staff'];
			}
			else
            {
				$ticket['repliername'] = $ticket['name'];
			}
			echo '<td>'.$ticket['repliername'].'</td>';
		}

		// Print time worked
		if ( hesk_show_column('time_worked') )
		{
			echo '<td>'.$ticket['time_worked'].'</td>';
		}

		// Print custom fields
		foreach ($hesk_settings['custom_fields'] as $key => $value)
		{
			if ($value['use'] && hesk_show_column($key) )
            {
				echo '<td>'.($value['type'] == 'date' ? hesk_custom_date_display_format($ticket[$key], $value['value']['date_format']) : $ticket[$key]).'</td>';
            }
		}

		// End ticket row
		echo '
		<td>
		    <div class="dropdown priority" data-value="' . $ticket['priority'] . '" style="cursor: default">
                <div class="label" style="cursor: default">
                    <span>' . $hesklang[$ticket['priority']] . '</span>
                </div>
            </div>
		</td>
		</tr>
		';

	} // End while
	?>
    </tbody>
	</table>
	</div>
    <div class="pagination-wrap">
        <div class="pagination">
            <?php
            if ($pages > 1) {
                /* List pages */
                if ($pages > 7) {
                    if ($page > 2) {
                        echo '
                        <a href="'.$href.'?'.$query_for_pagination.'1" class="btn pagination__nav-btn">
                            <svg class="icon icon-chevron-left">
                              <use xlink:href="'. HESK_PATH .'img/sprite.svg#icon-chevron-left"></use>
                            </svg>
                            <svg class="icon icon-chevron-left">
                              <use xlink:href="'. HESK_PATH .'img/sprite.svg#icon-chevron-left"></use>
                            </svg>
                            '.$hesklang['pager_first'].'
                        </a>';
                    }

                    if ($prev_page)
                    {
                        echo '
                        <a href="'.$href.'?'.$query_for_pagination.$prev_page.'" class="btn pagination__nav-btn">
                            <svg class="icon icon-chevron-left">
                              <use xlink:href="'. HESK_PATH .'img/sprite.svg#icon-chevron-left"></use>
                            </svg>
                            '.$hesklang['pager_previous'].'
                        </a>';
                    }
                }

                echo '<ul class="pagination__list">';
                for ($i=1; $i<=$pages; $i++)
                {
                    if ($i <= ($page+5) && $i >= ($page-5))
                    {
                        if ($i == $page)
                        {
                            echo '
                            <li class="pagination__item is-current">
                              <a href="javascript:" class="pagination__link">'.$i.'</a>
                            </li>';
                        }
                        else
                        {
                            echo '
                            <li class="pagination__item ">
                              <a href="'.$href.'?'.$query_for_pagination.$i.'" class="pagination__link">'.$i.'</a>
                            </li>';
                        }
                    }
                }
                echo '</ul>';

                if ($pages > 7)
                {
                    if ($next_page)
                    {
                        echo '
                        <a href="'.$href.'?'.$query_for_pagination.$next_page.'" class="btn pagination__nav-btn">
                            '.$hesklang['pager_next'].'
                            <svg class="icon icon-chevron-right">
                              <use xlink:href="'. HESK_PATH .'img/sprite.svg#icon-chevron-right"></use>
                            </svg>
                        </a>';
                    }

                    if ($page < ($pages - 1))
                    {
                        echo '
                        <a href="'.$href.'?'.$query_for_pagination.$pages.'" class="btn pagination__nav-btn">
                            '.$hesklang['pager_last'].'
                            <svg class="icon icon-chevron-right">
                              <use xlink:href="'. HESK_PATH .'img/sprite.svg#icon-chevron-right"></use>
                            </svg>
                            <svg class="icon icon-chevron-right">
                              <use xlink:href="'. HESK_PATH .'img/sprite.svg#icon-chevron-right"></use>
                            </svg>
                        </a>';
                    }
                }
            } // end PAGES > 1
            ?>
        </div>
        <p class="pagination__amount"><?php echo sprintf($hesklang['tickets_on_pages'],$total,$pages); ?></p>
    </div>

    <section class="tickets__legend">
        <div>
            <?php
            if (hesk_checkPermission('can_add_archive',0))
            {
                ?>
                <div>
                    <svg class="icon icon-tag">
                        <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-tag"></use>
                    </svg>
                    <?php echo $hesklang['archived2']; ?>
                </div>
                <?php
            }
            ?>
            <div>
                <svg class="icon icon-assign">
                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-assign"></use>
                </svg> <?php echo $hesklang['tasy2']; ?>
            </div>
            <?php
            if (hesk_checkPermission('can_view_ass_others',0) || hesk_checkPermission('can_view_ass_by',0))
            {
                ?>
                <div>
                    <svg class="icon icon-assign-plus">
                        <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-assign-plus"></use>
                    </svg> <?php echo $hesklang['taso2']; ?>
                </div>
                <?php
            }
            ?>
        </div>
        <div class="bulk-actions">
            <?php echo $hesklang['with_selected']; ?>
            <div style="display: inline-block; vertical-align: bottom">
                <select name="a">
                    <option value="low" selected="selected"><?php echo $hesklang['set_pri_to'].' '.$hesklang['low']; ?></option>
                    <option value="medium"><?php echo $hesklang['set_pri_to'].' '.$hesklang['medium']; ?></option>
                    <option value="high"><?php echo $hesklang['set_pri_to'].' '.$hesklang['high']; ?></option>
                    <option value="critical"><?php echo $hesklang['set_pri_to'].' '.$hesklang['critical']; ?></option>
                    <?php
                    if ( hesk_checkPermission('can_resolve', 0) )
                    {
                        ?>
                        <option value="close"><?php echo $hesklang['close_selected']; ?></option>
                        <?php
                    }

                    if ( hesk_checkPermission('can_add_archive', 0) )
                    {
                        ?>
                        <option value="tag"><?php echo $hesklang['add_archive_quick']; ?></option>
                        <option value="untag"><?php echo $hesklang['remove_archive_quick']; ?></option>
                        <?php
                    }

                    ?>
                    <option value="print"><?php echo $hesklang['print_selected']; ?></option>
                    <?php

                    if ( ! defined('HESK_DEMO') )
                    {

                        if ( hesk_checkPermission('can_merge_tickets', 0) )
                        {
                            ?>
                            <option value="merge"><?php echo $hesklang['mer_selected']; ?></option>
                            <?php
                        }
                        if ( hesk_checkPermission('can_export', 0) )
                        {
                            ?>
                            <option value="export"><?php echo $hesklang['export_selected']; ?></option>
                            <?php
                        }
                        if ( hesk_checkPermission('can_privacy', 0) )
                        {
                            ?>
                            <option value="anonymize"><?php echo $hesklang['anon_selected']; ?></option>
                            <?php
                        }
                        if ( hesk_checkPermission('can_del_tickets', 0) )
                        {
                            ?>
                            <option value="delete"><?php echo $hesklang['del_selected']; ?></option>
                            <?php
                        }

                    } // End demo
                    ?>
                </select>
            </div>
            <input type="hidden" name="token" value="<?php hesk_token_echo(); ?>" />
            <button onclick="document.getElementById('action-type').value = 'bulk'" type="button" class="btn btn-full" ripple="ripple" data-modal="[data-modal-id='<?php echo $modal_id; ?>']">
                <?php echo $hesklang['execute']; ?>
            </button>

            <?php
            if (hesk_checkPermission('can_assign_others',0))
            {
                ?>
                <br>

                <?php echo $hesklang['assign_selected']; ?>
                <div style="display: inline-block; vertical-align: bottom">
                    <select name="owner">
                        <option value="" selected="selected"><?php echo $hesklang['select']; ?></option>
                        <option value="-1"> &gt; <?php echo $hesklang['unas']; ?> &lt; </option>
                        <?php
                        foreach ($can_assign_to as $k=>$v)
                        {
                            echo '<option value="'.$k.'">'.$v.'</option>';
                        }
                        ?>
                    </select>
                </div>
                <button type="button" name="assign" class="btn btn-full" data-modal="[data-modal-id='<?php echo $modal_id; ?>']"
                    onclick="document.getElementById('action-type').value = 'assi'">
                    <?php echo $hesklang['assi']; ?>
                </button>
                <?php
            }
            ?>
        </div>
    </section>
    <input id="action-type" name="action-type" type="hidden" value="-1">
	</form>
	<?php

} // END ticket list if total > 0
else
{
    if (isset($is_search) || $href == 'find_tickets.php')
    {
        hesk_show_notice($hesklang['no_tickets_crit']);
    }
    else
    {
        echo '<p>&nbsp;<br />&nbsp;<b><i>'.$hesklang['no_tickets_open'].'</i></b><br />&nbsp;</p>';
    }
}


function hesk_print_list_head()
{
	global $hesk_settings, $href, $query, $sort_possible, $hesklang;
	?>
    <div class="table-wrap">
	<table class="table sindu-table ticket-list sindu_origin_table" id="default-table">
    <thead>
    <tr>
        <th class="table__first_th sindu_handle">
            <div class="checkbox-custom">
                <input type="checkbox" id="ticket_checkall" name="checkall" value="2" onclick="hesk_changeAll(this)">
                <label for="ticket_checkall">&nbsp;</label>
            </div>
        </th>
        <?php
        $sort = hesk_GET('sort', 'status');
        $sort_direction = '';
        if (isset($_GET['asc'])) {
            $sort_direction = intval($_GET['asc']) == 0 ? 'desc' : 'asc';
        }
        foreach ($hesk_settings['ticket_list'] as $field)
        {
            echo '<th class="sindu-handle '.($sort == $field ? $sort_direction : '').' '.($field == 'trackid' ? 'trackid' : '').'">
                <a href="' . $href . '?' . $query . $sort_possible[$field] . '&amp;sort=' . $field . '">
                    <div class="sort">
                        <span>' . $hesk_settings['possible_ticket_list'][$field] . '</span>
                        <i class="handle"></i>
                    </div>
                </a>
            </th>';
        }
        ?>
        <th class="sindu-handle <?php echo $sort == 'priority' ? $sort_direction : ''; ?>">
            <a href="<?php echo $href . '?' . $query . $sort_possible['priority'] . '&amp;sort='; ?>priority">
                <div class="sort">
                    <span><?php echo $hesklang['priority']; ?></span>
                    <i class="handle"></i>
                </div>
            </a>
        </th>
    </tr>
    </thead>
    <tbody>
	<?php
} // END hesk_print_list_head()


function hesk_time_since($original)
{
	global $hesk_settings, $hesklang, $mysql_time;

    /* array of time period chunks */
    $chunks = array(
        array(60 * 60 * 24 * 365 , $hesklang['abbr']['year']),
        array(60 * 60 * 24 * 30 , $hesklang['abbr']['month']),
        array(60 * 60 * 24 * 7, $hesklang['abbr']['week']),
        array(60 * 60 * 24 , $hesklang['abbr']['day']),
        array(60 * 60 , $hesklang['abbr']['hour']),
        array(60 , $hesklang['abbr']['minute']),
        array(1 , $hesklang['abbr']['second']),
    );

	/* Invalid time */
    if ($mysql_time < $original)
    {
    	// DEBUG return "T: $mysql_time (".date('Y-m-d H:i:s',$mysql_time).")<br>O: $original (".date('Y-m-d H:i:s',$original).")";
        return "0".$hesklang['abbr']['second'];
    }

    $since = $mysql_time - $original;

    // $j saves performing the count function each time around the loop
    for ($i = 0, $j = count($chunks); $i < $j; $i++) {

        $seconds = $chunks[$i][0];
        $name = $chunks[$i][1];

        // finding the biggest chunk (if the chunk fits, break)
        if (($count = floor($since / $seconds)) != 0) {
            // DEBUG print "<!-- It's $name -->\n";
            break;
        }
    }

    $print = "$count{$name}";

    if ($i + 1 < $j) {
        // now getting the second item
        $seconds2 = $chunks[$i + 1][0];
        $name2 = $chunks[$i + 1][1];

        // add second item if it's greater than 0
        if (($count2 = floor(($since - ($seconds * $count)) / $seconds2)) != 0) {
            $print .= "$count2{$name2}";
        }
    }
    return $print;
} // END hesk_time_since()


function hesk_time_lastchange($original)
{
	global $hesk_settings, $hesklang;

	// Save time format setting so we can restore it later
	$copy = $hesk_settings['timeformat'];

	// We need this time format for this function
	$hesk_settings['timeformat'] = 'Y-m-d H:i:s';

	// Get HESK time-adjusted start of today if not already
	if ( ! defined('HESK_TIME_TODAY') )
	{
		// Adjust for HESK time and define constants for alter use
		define('HESK_TIME_TODAY',		date('Y-m-d 00:00:00', hesk_date(NULL, false, false, false) ) );
		define('HESK_TIME_YESTERDAY',	date('Y-m-d 00:00:00', strtotime(HESK_TIME_TODAY)-86400) ) ;
	}

	// Adjust HESK time difference and get day name
	$ticket_time = hesk_date($original, true);

	if ($ticket_time >= HESK_TIME_TODAY)
	{
		// For today show HH:MM
		$day = substr($ticket_time, 11, 5);
	}
	elseif ($ticket_time >= HESK_TIME_YESTERDAY)
	{
		// For yesterday show word "Yesterday"
		$day = $hesklang['r2'];
	}
	else
	{
		// For other days show DD MMM YY
		list($y, $m, $d) = explode('-', substr($ticket_time, 0, 10) );
		$day = '<span style="white-space: nowrap;">' . $d . ' ' . $hesklang['ms'.$m] . ' ' . substr($y, 2) . '</span>';
	}

	// Restore original time format setting
	$hesk_settings['timeformat'] = $copy;

	// Return value to display
	return $day;

} // END hesk_time_lastchange()
