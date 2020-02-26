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

$formatted_tickets = array();
foreach ($tickets as $ticket) {
    // Ticket priority
    switch ($ticket['priority'])
    {
        case 0:
            $ticket['priority']='<b>'.$hesklang['critical'].'</b>';
            break;
        case 1:
            $ticket['priority']='<b>'.$hesklang['high'].'</b>';
            break;
        case 2:
            $ticket['priority']=$hesklang['medium'];
            break;
        default:
            $ticket['priority']=$hesklang['low'];
    }

    // Set last replier name
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

    // Other variables that need processing
    $ticket['dt'] = hesk_date($ticket['dt'], true);
    $ticket['lastchange'] = hesk_date($ticket['lastchange'], true);
    $random=mt_rand(10000,99999);

    $ticket['status'] = hesk_get_status_name($ticket['status']);

    if ($ticket['owner'] && ! empty($_SESSION['id']) )
    {
        $ticket['owner'] = hesk_getOwnerName($ticket['owner']);
    } else {
        $ticket['owner'] = '';
    }

    // Custom fields
    $custom_fields = array();
    foreach ($hesk_settings['custom_fields'] as $k=>$v) {
        if (($v['use'] == 1 || (!empty($_SESSION['id']) && $v['use'] == 2)) && hesk_is_custom_field_in_category($k, $ticket['category'])) {
            if ($v['type'] == 'date') {
                $ticket[$k] = hesk_custom_date_display_format($ticket[$k], $v['value']['date_format']);
            }

            $custom_fields[] = array(
                'name' => $v['name:'],
                'value' => hesk_unhortenUrl($ticket[$k])
            );
        }
    }
    $ticket['custom_fields'] = $custom_fields;

    // Initial ticket message
    if ($ticket['message'] != '')
    {
        $ticket['message'] = hesk_unhortenUrl($ticket['message']);
    }

    // Replies
    $replies = array();
    while ($reply = hesk_dbFetchAssoc($ticket['replies'])) {
        $reply['dt'] = hesk_date($reply['dt'], true);
        $reply['message'] = hesk_unhortenUrl($reply['message']);

        $replies[] = $reply;
    }
    $ticket['replies'] = $replies;

    $formatted_tickets[] = $ticket;
}

$hesk_settings['render_template'](TEMPLATE_PATH . 'print-ticket.php', array(
    'tickets' => $formatted_tickets,
    'showStaffOnlyFields' => !empty($_SESSION['id'])
), true, true);
