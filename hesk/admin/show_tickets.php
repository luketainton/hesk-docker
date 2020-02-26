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
define('AUTO_RELOAD',1);

/* Check permissions for this feature */
hesk_checkPermission('can_view_tickets');

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
$header_text = '
    <section style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px">
        <h2 style="font-size: 18px; font-weight: bold">'. $hesklang['tickets'] .' (%%HESK_TICKET_COUNT%%)</h2>
        <div class="checkbox-custom">
            <input type="checkbox" id="reloadCB" onclick="toggleAutoRefresh(this);">
            <label for="reloadCB">'. $hesklang['arp'] .'</label>&nbsp;<span id="timer"></span>
            <script type="text/javascript">heskCheckReloading();</script>
        </div>
    </section>';

/* Print the list of tickets */
$is_search = 1;
require_once(HESK_PATH . 'inc/print_tickets.inc.php');

/* Update staff default settings? */
if ( ! empty($_GET['def']))
{
	hesk_updateStaffDefaults();
}
?>

&nbsp;<br />

<?php
/* Print forms for listing and searching tickets */
require_once(HESK_PATH . 'inc/show_search_form.inc.php');
?>

<p>&nbsp;</p>
<?php

/* Print footer */
require_once(HESK_PATH . 'inc/footer.inc.php');
exit();

?>
