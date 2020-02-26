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

/* Group tickets into tables */
if ($group == 'owner')
{
	if ($ticket['owner'] != $group_tmp)
	{
		$group_tmp = $ticket['owner'];

		if ($is_table)
		{
			echo '</table></div>';
		}

		if ($space)
		{
			echo '&nbsp;<br />';
		}

		if (empty($group_tmp) || ! isset($admins[$group_tmp]))
		{
			echo '<p>'.$hesklang['gbou'].'</p>';
			$space++;
		}
		else
		{
			if ($group_tmp == $_SESSION['id'])
			{
				echo '<p>'.$hesklang['gbom'].'</p>';
				$space++;
			}
			else
			{
				echo '<p>'.sprintf($hesklang['gboo'],$admins[$group_tmp]).'</p>';
				$space++;
			}
		}

		hesk_print_list_head();
		$is_table = 1;
	}
} // END if 'owner'

elseif ($group == 'priority')
{
	switch ($ticket['priority'])
	{
		case 0:
			$tmp = $hesklang['critical'];
			break;
		case 1:
			$tmp = $hesklang['high'];
			break;
		case 2:
			$tmp = $hesklang['medium'];
			break;
		default:
			$tmp = $hesklang['low'];
	}

	if ($ticket['priority'] != $group_tmp)
	{
		$group_tmp = $ticket['priority'];

		if ($is_table)
		{
			echo '</table></div>';
		}

		if ($space)
		{
			echo '&nbsp;<br />';
		}

		echo '<p>'.$hesklang['priority'].': <b>'.$tmp.'</b></p>';
		$space++;

		hesk_print_list_head();
		$is_table = 1;
	}
} // END elseif 'priority'

else
{
	if ($ticket['category'] != $group_tmp)
	{
		$group_tmp = $ticket['category'];

		if ($is_table)
		{
			echo '</table></div>';
		}

		if ($space)
		{
			echo '&nbsp;<br />';
		}


        $tmp = isset($hesk_settings['categories'][$group_tmp]) ? $hesk_settings['categories'][$group_tmp] : '('.$hesklang['unknown'].')';

		echo '<p>'.$hesklang['category'].': <b>'.$tmp.'</b></p>';
		$space++;

		hesk_print_list_head();
		$is_table = 1;
	}
} // END else ('category')
