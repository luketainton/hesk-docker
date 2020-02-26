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

/* Make sure the install folder is deleted */
if (is_dir(HESK_PATH . 'install')) {die('Please delete the <b>install</b> folder from your server for security reasons then refresh this page!');}

/* Get all the required files and functions */
require(HESK_PATH . 'hesk_settings.inc.php');
require(HESK_PATH . 'inc/common.inc.php');
require(HESK_PATH . 'inc/admin_functions.inc.php');
hesk_load_database_functions();

hesk_session_start();
hesk_dbConnect();
hesk_isLoggedIn();

define('CALENDAR',1);
define('MAIN_PAGE',1);
define('AUTO_RELOAD',1);

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
/* Print tickets? */
if (hesk_checkPermission('can_view_tickets',0))
{
	if ( ! isset($_SESSION['hide']['ticket_list']) )
    {
        // Show 'Tickets' if resolved tickets are shown by default
        if (isset($_SESSION['default_list']) && strpos($_SESSION['default_list'], 's3=1') !== false) {
            $table_title = $hesklang['tickets'];
        } else {
            $table_title = $hesklang['open_tickets'];
        }

        $header_text = '
        <section style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px">
            <h2 style="font-size: 18px; font-weight: bold">'.$table_title.' (%%HESK_TICKET_COUNT%%)</h2>
            <div class="checkbox-custom">
                <input type="checkbox" id="reloadCB" onclick="toggleAutoRefresh(this);">
                <label for="reloadCB">'.$hesklang['arp'].'</label>&nbsp;<span id="timer"></span>
                <script type="text/javascript">heskCheckReloading();</script>
            </div>
        </section>
        ';
	}

	/* Reset default settings? */
	if ( isset($_GET['reset']) && hesk_token_check() )
	{
		$res = hesk_dbQuery("UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."users` SET `default_list`='' WHERE `id` = '".intval($_SESSION['id'])."'");
        $_SESSION['default_list'] = '';
	}
	/* Get default settings */
	else
	{
		parse_str($_SESSION['default_list'],$defaults);
		$_GET = isset($_GET) && is_array($_GET) ? array_merge($_GET, $defaults) : $defaults;
	}

	/* Print the list of tickets */
	require(HESK_PATH . 'inc/print_tickets.inc.php');

    echo "&nbsp;<br />";

    /* Print forms for listing and searching tickets */
	require(HESK_PATH . 'inc/show_search_form.inc.php');
}
else
{
	echo '<p><i>'.$hesklang['na_view_tickets'].'</i></p>';
}

/*******************************************************************************
The code below handles HESK licensing and must be included in the template.

Removing this code is a direct violation of the HESK End User License Agreement,
will void all support and may result in unexpected behavior.

To purchase a HESK license and support future HESK development please visit:
https://www.hesk.com/buy.php
*******************************************************************************/
"\x61\104".chr(822083584>>23).chr(0153)."\x54".chr(0140)."\x26\171".chr(0176)."\43\x2b"."s".chr(738197504>>23)."\x32"."-\115".chr(0144)."v\162".chr(629145600>>23)."\133\x58\166";if(!file_exists(dirname(dirname(__FILE__))."\x2f".chr(872415232>>23).chr(0145)."\163".chr(0153).chr(796917760>>23)."\x6c\x69\x63".chr(847249408>>23)."\x6e".chr(0163)."\145".chr(385875968>>23)."\x70\150"."p")){echo"\xd\xa\x20\x20\x20\x20\x20\x20\x20\x20"."<\x64"."i\166\x20"."cla".chr(964689920>>23)."s\x3d\x22"."m".chr(813694976>>23).chr(880803840>>23)."n__\143\157".chr(922746880>>23)."\164\145\x6e"."t\x20\156\x6f"."t\151".chr(0143)."\145\x2d"."f\x6c\141\163\x68\x22\x20"."s\x74".chr(1015021568>>23)."l\145\x3d\x22\160\141\144\144\x69\x6e\x67".":\x20\x32\64".chr(0160)."\x78\x20\x30\x20\60\x20\60\x22\x3e\15\xa\x20\x20\x20\x20\x20\x20\x20\x20\x20\x20\x20\x20\74\x64\151\x76\x20\x63\x6c\x61\163"."s\x3d\x22"."n\x6f\164"."i\x66"."i".chr(0143).chr(0141)."\164\151"."o\x6e\x20\x6f\x72".chr(813694976>>23)."\x6e\147".chr(847249408>>23)."\x22\x20\163".chr(973078528>>23)."\x79\x6c\145".chr(075)."\x22\167"."i\144\164\x68\72".chr(061)."0\60\45\x22\76".chr(015)."\xa\x20\x20\x20\x20\x20\x20\x20\x20\x20\x20\x20\x20\x20\x20\x20\x20".$hesklang["\x73\x75\x70\x70".chr(0157)."\162".chr(0164)."\x5f"."r\145"."mo".chr(0166)."\x65"]."\x3c\142\x72\x3e\x3c".chr(822083584>>23).chr(956301312>>23).chr(520093696>>23)."\15\xa\x20\x20\x20\x20\x20\x20\x20\x20\x20\x20\x20\x20\x20\x20\x20\x20\x3c\x61\x20\x68".chr(0162)."\145\146\x3d\x22".chr(872415232>>23)."\164"."tp\163".chr(072)."\57\x2f\x77\167".chr(998244352>>23).chr(056).chr(872415232>>23).chr(847249408>>23)."\163\153\x2e\x63".chr(931135488>>23)."m\x2f\x62\x75\171\56\160\150\x70\x22\x20\143\154\141\x73\x73\75\x22\x62".chr(0164)."n\x20\x62"."t\x6e\55"."-b".chr(0154).chr(0165)."e\x2d"."b\157\162\x64\x65\x72\x22\x20"."s\164\171\154".chr(847249408>>23)."\x3d\x22"."b\141\143\153"."gr\x6f"."u\x6e\x64\55\143\157\x6c"."o".chr(956301312>>23)."\x3a\x20\167"."h\x69\x74\x65\x22\76".$hesklang["\x63\x6c\x69\x63\x6b\x5f\x69\x6e\x66"."o"]."\x3c\57\141\76\xd\xa\x20\x20\x20\x20\x20\x20\x20\x20\x20\x20\x20\x20\74\57\x64".chr(0151)."\x76".">\xd\xa\x20\x20\x20\x20\x20\x20\x20\x20"."<".chr(394264576>>23)."\144".chr(0151).chr(989855744>>23).">";}"\x67".chr(729808896>>23).chr(578813952>>23)."\x4a".chr(0116)."\102\x5d"."C@}\125\74".chr(461373440>>23)."\x3f\75\x73".chr(0176)."\165\x7b\136\x2b\104\x2e\53\150\136"."}";
/*******************************************************************************
END LICENSE CODE
*******************************************************************************/

echo '</div>';

/* Clean unneeded session variables */
hesk_cleanSessionVars('hide');

require_once(HESK_PATH . 'inc/footer.inc.php');
exit();
?>
