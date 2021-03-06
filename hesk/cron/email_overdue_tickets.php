#!/usr/bin/php -q
<?php

define('IN_SCRIPT',1);
define('HESK_PATH', dirname(dirname(__FILE__)) . '/');

// Do not send out the default UTF-8 HTTP header
define('NO_HTTP_HEADER',1);

// Get required files and functions
require(HESK_PATH . 'hesk_settings.inc.php');
require(HESK_PATH . 'inc/common.inc.php');
require(HESK_PATH . 'inc/email_functions.inc.php');

// Do we require a key if not accessed over CLI?
hesk_authorizeNonCLI();

hesk_load_database_functions();
hesk_dbConnect();
$hesk_settings['simulate_overdue_tickets'] = isset($_GET['simulate']);

if (defined('HESK_DEMO')) {
    $hesk_settings['simulate_overdue_tickets'] = true;

    hesk_overdue_ticket_log("\n** {$hesklang['sdemo']} **");
}

if ($hesk_settings['simulate_overdue_tickets']) {
    hesk_overdue_ticket_log("\n** {$hesklang['overdue_sim']} **\n");
}

if (hesk_check_maintenance(false)) {
    // If Debug mode is ON show "Maintenance mode" message
    hesk_overdue_ticket_log($hesklang['mm1'], true);
}

hesk_overdue_ticket_log($hesklang['overdue_starting']);


$current_date = hesk_date();

$sql = "SELECT `ticket`.`id` AS `id`, `ticket`.`trackid` AS `trackid`, `ticket`.`name` AS `name`, `ticket`.`subject` AS `subject`,
    `ticket`.`message` AS `message`, `ticket`.`message_html` AS `message_html`, `ticket`.`category` AS `category`, `ticket`.`priority` AS `priority`,
    `ticket`.`owner` AS `owner`, `ticket`.`status` AS `status`, `ticket`.`email` AS `email`, `ticket`.`dt` AS `dt`,
    `ticket`.`lastchange` AS `lastchange`, `ticket`.`due_date` AS `due_date`, `user`.`language` AS `user_language`, `user`.`email` AS `user_email`,
    `ticket`.`time_worked` AS `time_worked`, `ticket`.`lastreplier` AS `lastreplier`, `ticket`.`replierid` AS `replierid`,
    `ticket`.`custom1` AS `custom1`, `ticket`.`custom2` AS `custom2`, `ticket`.`custom3` AS `custom3`, `ticket`.`custom4` AS `custom4`,
    `ticket`.`custom5` AS `custom5`, `ticket`.`custom6` AS `custom6`, `ticket`.`custom7` AS `custom7`, `ticket`.`custom8` AS `custom8`,
    `ticket`.`custom9` AS `custom9`, `ticket`.`custom10` AS `custom10`, `ticket`.`custom11` AS `custom11`, `ticket`.`custom12` AS `custom12`,
    `ticket`.`custom13` AS `custom13`, `ticket`.`custom14` AS `custom14`, `ticket`.`custom15` AS `custom15`, `ticket`.`custom16` AS `custom16`,
    `ticket`.`custom17` AS `custom17`, `ticket`.`custom18` AS `custom19`, `ticket`.`custom19` AS `custom19`, `ticket`.`custom20` AS `custom20`,
    `ticket`.`custom21` AS `custom21`, `ticket`.`custom22` AS `custom22`, `ticket`.`custom23` AS `custom23`, `ticket`.`custom24` AS `custom24`,
    `ticket`.`custom25` AS `custom25`, `ticket`.`custom26` AS `custom26`, `ticket`.`custom27` AS `custom27`, `ticket`.`custom28` AS `custom28`,
    `ticket`.`custom29` AS `custom29`, `ticket`.`custom30` AS `custom30`, `ticket`.`custom31` AS `custom31`, `ticket`.`custom32` AS `custom32`,
    `ticket`.`custom33` AS `custom33`, `ticket`.`custom34` AS `custom34`, `ticket`.`custom35` AS `custom35`, `ticket`.`custom36` AS `custom36`,
    `ticket`.`custom37` AS `custom37`, `ticket`.`custom38` AS `custom38`, `ticket`.`custom39` AS `custom39`, `ticket`.`custom40` AS `custom40`,
    `ticket`.`custom41` AS `custom41`, `ticket`.`custom42` AS `custom42`, `ticket`.`custom43` AS `custom43`, `ticket`.`custom44` AS `custom44`,
    `ticket`.`custom45` AS `custom45`, `ticket`.`custom46` AS `custom46`, `ticket`.`custom47` AS `custom47`, `ticket`.`custom48` AS `custom48`,
    `ticket`.`custom49` AS `custom49`, `ticket`.`custom50` AS `custom50`
    FROM `" . hesk_dbEscape($hesk_settings['db_pfix']) . "tickets` AS `ticket`
    LEFT JOIN `" . hesk_dbEscape($hesk_settings['db_pfix']) . "users` AS `user`
        ON `ticket`.`owner` = `user`.`id`
    WHERE `due_date` IS NOT NULL
        AND `due_date` <= '{$current_date}'
        AND `status` <> 3
        AND `overdue_email_sent` = '0'";

$successful_emails = 0;
$failed_emails = 0;
$rs = hesk_dbQuery($sql);
$number_of_tickets = hesk_dbNumRows($rs);


hesk_overdue_ticket_log(sprintf($hesklang['overdue_ticket_count'], $number_of_tickets));

if (!$number_of_tickets) {
    exit();
}

$user_rs = hesk_dbQuery("SELECT `id`, `isadmin`, `categories`, `email`, `name`,
    CASE WHEN `heskprivileges` LIKE '%can_view_unassigned%' THEN 1 ELSE 0 END AS `can_view_unassigned`
    FROM `" . hesk_dbEscape($hesk_settings['db_pfix']) . "users` 
    WHERE (`notify_overdue_unassigned` = '1' OR `notify_overdue_my` = '1')
        AND (`heskprivileges` LIKE '%can_view_tickets%' OR `isadmin` = '1')");

$users = array();
while ($row = hesk_dbFetchAssoc($user_rs)) {
    $users[$row['id']] = $row;
}

$tickets_to_flag = array();
$tickets_log_sql = array();
while ($ticket = hesk_dbFetchAssoc($rs)) {
    hesk_overdue_ticket_log("=======================");

    // Make sure all values are properly formatted for email
    $ticket['dt'] = hesk_date($ticket['dt'], true);
    $ticket['lastchange'] = hesk_date($ticket['lastchange'], true);
    $ticket['last_reply_by'] = hesk_getReplierName($ticket);
    $ticket['due_date'] = hesk_format_due_date($ticket['due_date']);
    $ticket = hesk_ticketToPlain($ticket, 1, 0);

    $owner_email = isset($users[$ticket['owner']]) ? $users[$ticket['owner']]['email'] : $hesklang['unas'];
    if (!$hesk_settings['simulate_overdue_tickets']) {
        if (hesk_sendOverdueTicketReminder($ticket, $users)) {
            $tickets_to_flag[] = $ticket['id'];
            $tickets_log_sql[] = "('".intval($ticket['id'])."', '".intval($ticket['category'])."', '".intval($ticket['priority'])."', '".intval($ticket['status'])."', '".intval($ticket['owner'])."', '".hesk_dbEscape($ticket['due_date'])."')";
            $successful_emails++;

            hesk_overdue_ticket_log("[{$hesklang['success']}]\n{$hesklang['trackID']}: {$ticket['trackid']}\n{$hesklang['email']}: {$owner_email}");

            // Let's force flag/insert into log every 1000 tickets to make sure we don't hit the max_allowed_packet limit, and to free some memory
            if ($successful_emails % 1000 == 0) {
                hesk_dbQuery("INSERT INTO `".hesk_dbEscape($hesk_settings['db_pfix'])."log_overdue` (`ticket`, `category`, `priority`, `status`, `owner`, `due_date`) VALUES " . implode(',', $tickets_log_sql));
                hesk_dbQuery("UPDATE `" . hesk_dbEscape($hesk_settings['db_pfix']) . "tickets` SET `overdue_email_sent` = '1' WHERE `id` IN (" . implode(',', $tickets_to_flag) . ")");
                $tickets_to_flag = array();
                $tickets_log_sql = array();
            }
        } else {
            $failed_emails++;
            hesk_overdue_ticket_log("[{$hesklang['error']}]\n{$hesklang['trackID']}: {$ticket['trackid']}\n{$hesklang['email']}: {$owner_email}");
        }
    } else {
        hesk_overdue_ticket_log("{$hesklang['trackID']}: {$ticket['trackid']}\n{$hesklang['email']}: {$owner_email}");
    }
    hesk_overdue_ticket_log("=======================");
}

// Flag/insert any remaning tickets
if (count($tickets_to_flag) > 0 && !$hesk_settings['simulate_overdue_tickets']) {
    hesk_dbQuery("INSERT INTO `".hesk_dbEscape($hesk_settings['db_pfix'])."log_overdue` (`ticket`, `category`, `priority`, `status`, `owner`, `due_date`) VALUES " . implode(',', $tickets_log_sql));
    hesk_dbQuery("UPDATE `" . hesk_dbEscape($hesk_settings['db_pfix']) . "tickets` SET `overdue_email_sent` = '1' WHERE `id` IN (" . implode(',', $tickets_to_flag) . ")");
}

hesk_overdue_ticket_log(sprintf($hesklang['overdue_finished'], $successful_emails, $failed_emails));

function hesk_overdue_ticket_log($message, $do_die=false) {
    global $hesk_settings;

    if ($hesk_settings['debug_mode']) {
        echo $message . "\n";
    }

    if ($do_die) {
        die();
    }
} // END hesk_overdue_ticket_log
