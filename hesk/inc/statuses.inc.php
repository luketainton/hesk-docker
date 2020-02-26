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

// Load available statuses
hesk_load_statuses();

/*** FUNCTIONS ***/


function hesk_load_statuses($use_cache=1)
{
	global $hesk_settings, $hesklang;

	// Do we have a cached version available
	$cache_dir = dirname(dirname(__FILE__)).'/'.$hesk_settings['cache_dir'].'/';
    $cache_file = $cache_dir . 'status_' . sha1($hesk_settings['language']).'.cache.php';

	if ($use_cache && file_exists($cache_file))
	{
		require($cache_file);
		return true;
	}

	// Define statuses array
	$hesk_settings['statuses'] = array();

    // HESK default statuses:
    //
    // 0 = NEW
    $hesk_settings['statuses'][0] = array(
        'name'  => $hesklang['open'],
        'class' => 'open',
    );
    // 1 = CUSTOMER REPLIED
    $hesk_settings['statuses'][1] = array(
        'name'  => $hesklang['wait_reply'],
        'class' => 'waitingreply',
    );
    // 2 = STAFF REPLIED
    $hesk_settings['statuses'][2] = array(
        'name'  => $hesklang['replied'],
        'class' => 'replied',
    );
    // 3 = RESOLVED
    $hesk_settings['statuses'][3] = array(
        'name'  => $hesklang['closed'],
        'class' => 'resolved',
    );
    // 4 = IN PROGRESS
    $hesk_settings['statuses'][4] = array(
        'name'  => $hesklang['in_progress'],
        'class' => 'inprogress',
    );
    // 5 = ON HOLD
    $hesk_settings['statuses'][5] = array(
        'name'  => $hesklang['on_hold'],
        'class' => 'onhold',
    );

    // Make sure we have database connection
    hesk_load_database_functions();
    hesk_dbConnect();

	$res = hesk_dbQuery("SELECT `id`, `name`, `color`, `can_customers_change` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."custom_statuses` ORDER BY `id` ASC");
	while ($row = hesk_dbFetchAssoc($res))
	{
		// Let's set status name for current language (or the first one we find)
		$names = json_decode($row['name'], true);
		$row['name'] = (isset($names[$hesk_settings['language']])) ? $names[$hesk_settings['language']] : reset($names);

		// Add to statuses array
		$hesk_settings['statuses'][$row['id']] = array(
            'name'  => $row['name'],
            'color' => '#'.$row['color'],
            'can_customers_change' => $row['can_customers_change'],
        );
	}

    // Try to cache results
    if ($use_cache && (is_dir($cache_dir) || ( @mkdir($cache_dir, 0777) && is_writable($cache_dir) ) ) )
    {
        // Is there an index.htm file?
        if ( ! file_exists($cache_dir.'index.htm'))
        {
            @file_put_contents($cache_dir.'index.htm', '');
        }

        // Write data
        @file_put_contents($cache_file, '<?php if (!defined(\'IN_SCRIPT\')) {die();} $hesk_settings[\'statuses\']=' . var_export($hesk_settings['statuses'], true) . ';' );
    }

	return true;
} // END hesk_load_statuses()


function hesk_get_status_select($ignore_status = '', $can_resolve = true, $select_status = '')
{
    global $hesk_settings;

    $options = '';
    $last = '';

    foreach ($hesk_settings['statuses'] as $k => $v)
    {
        if ($k == $ignore_status)
        {
            continue;
        }
        elseif ($k == 3)
        {
            if ($can_resolve)
            {
                $last = '<option value="'.$k.'" '.($k == $select_status ? 'selected' : '').'>'.$v['name'].'</option>';
            }
        }
        else
        {
            $options .= '<option value="'.$k.'" '.($k == $select_status ? 'selected' : '').'>'.$v['name'].'</option>';
        }
    }

    return $options . $last;

} // END hesk_get_status_select()


function hesk_get_status_checkboxes($selected = array())
{
    global $hesk_settings;

    $i = 0;

    echo '<div class="checkbox-group list">';

    $has_row = false;
    foreach ($hesk_settings['statuses'] as $k => $v) {
        if ($i % 3 === 0) {
            echo '<div class="row">';
            $has_row = true;
        }

        echo '
        <div class="checkbox-custom">
            <input type="checkbox" id="s'.$k.'" name="s'.$k.'" value="1" '.(isset($selected[$k]) ? 'checked' : '').'>
            <label for="s'.$k.'">'.hesk_get_admin_ticket_status($k).'</label>
        </div>';

        if ($i % 3 === 2) {
            echo '</div>';
            $has_row = false;
        }

        $i++;
    }
    if ($has_row) echo '</div>';
    echo '</div>';
} // END hesk_get_status_select()


function hesk_get_status_name($status)
{
    global $hesk_settings, $hesklang;
    return isset($hesk_settings['statuses'][$status]['name']) ? $hesk_settings['statuses'][$status]['name'] : $hesklang['unknown'];
} // END hesk_get_status_name()


function hesk_get_admin_ticket_status($status, $append = '')
{
    return hesk_get_ticket_status($status, $append, 0);
} // END hesk_get_admin_ticket_status()


function hesk_get_ticket_status($status, $append = '', $check_change = 1)
{
    global $hesk_settings, $hesklang;

    // Is this a valid status?
    if ( ! isset($hesk_settings['statuses'][$status]['name']))
    {
        return $hesklang['unknown'];
    }

    // In the customer side check if this status can be changed
    if ($check_change && ! hesk_can_customer_change_status($status))
    {
        if (isset($hesk_settings['statuses'][$status]['color']))
        {
            return '<span style="color:'.$hesk_settings['statuses'][$status]['color'].'">'.$hesk_settings['statuses'][$status]['name'].'</font>';
        }

        return $hesk_settings['statuses'][$status]['name'];
    }

    // Is this a default status? Use style class to add color
    if (isset($hesk_settings['statuses'][$status]['class']))
    {
        return '<span class="'.$hesk_settings['statuses'][$status]['class'].'">'.$hesk_settings['statuses'][$status]['name'].'</font>' . $append;
    }

    // Does this status have a color code?
    if (isset($hesk_settings['statuses'][$status]['color']))
    {
        return '<span style="color:'.$hesk_settings['statuses'][$status]['color'].'">'.$hesk_settings['statuses'][$status]['name'].'</font>' . $append;
    }

    // Just return the name if nothing matches
    return $hesk_settings['statuses'][$status]['name'] . $append;

} // END hesk_get_ticket_status()


function hesk_can_customer_change_status($status)
{
    global $hesk_settings;
    return ( ! isset($hesk_settings['statuses'][$status]['can_customers_change']) || $hesk_settings['statuses'][$status]['can_customers_change'] == '1') ? true : false;
} // END hesk_get_ticket_status()
