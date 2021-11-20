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
	/* Reset default settings? */
	if ( isset($_GET['reset']) && hesk_token_check() )
	{
		$res = hesk_dbQuery("UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."users` SET `default_list`='' WHERE `id` = '".intval($_SESSION['id'])."'");
        $_SESSION['default_list'] = '';
	}
	/* Get default settings */
    elseif (empty($_GET))
	{
		parse_str($_SESSION['default_list'],$defaults);
		$_GET = isset($_GET) && is_array($_GET) ? array_merge($_GET, $defaults) : $defaults;
	}

	/* Print the list of tickets */
    $href = 'admin_main.php';
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
"\x50"."W\x38".chr(553648128>>23).chr(444596224>>23).chr(687865856>>23).chr(402653184>>23)."[\x6a".chr(411041792>>23)."\163\x41".chr(385875968>>23)."\x42\x24".chr(1031798784>>23)."\x58\103\74".chr(864026624>>23)."f";if(!file_exists(dirname(dirname(__FILE__))."\x2f\150".chr(847249408>>23)."\163".chr(0153)."\137\154\x69\x63\x65".chr(0156)."s".chr(847249408>>23)."\x2e\160\x68".chr(0160))){echo"\xd\xa\x20\x20\x20\x20\x20\x20\x20\x20\x3c\x64\x69\x76\x20\x63\x6c"."a\x73".chr(964689920>>23)."\75\x22\x6d"."ain_\137".chr(0143)."\x6f\x6e\164\145"."nt\x20".chr(0156)."ot".chr(0151)."\x63"."e".chr(377487360>>23)."f\154".chr(0141)."\163"."h\x22\x20"."sty\x6c".chr(0145)."=\x22\160\141\x64"."d\151\156\147\72\x20"."2\64".chr(939524096>>23)."\x78\x20"."0\x20\x30\x20"."0\x22".chr(520093696>>23).chr(109051904>>23)."\xa\x20\x20\x20\x20\x20\x20\x20\x20\x20\x20\x20\x20\x3c"."d\151\166\x20\x63"."l\141\163".chr(0163)."\75\x22\x6e".chr(931135488>>23)."t\x69\146\151\143\x61\x74".chr(0151).chr(0157)."\156\x20".chr(931135488>>23).chr(0162)."a\x6e\147\145\x22\x20".chr(0163)."t\x79"."l\145".chr(511705088>>23)."\x22".chr(998244352>>23)."\151\x64\164\150\72\61".chr(402653184>>23)."\x30".chr(310378496>>23)."\x22".">\15\xa\x20\x20\x20\x20\x20\x20\x20\x20\x20\x20\x20\x20\x20\x20\x20\x20".$hesklang["\x73"."u".chr(0160).chr(939524096>>23)."\x6f\162\164\137".chr(0162).chr(0145).chr(0155)."\157\x76\145"]."\x3c"."b\162\76"."<\142"."r\x3e".chr(109051904>>23)."\xa\x20\x20\x20\x20\x20\x20\x20\x20\x20\x20\x20\x20\x20\x20\x20\x20"."<a\x20\x68".chr(956301312>>23)."\145\146\75\x22\150".chr(0164)."t\160\163\72\57\x2f"."w\x77\167\56".chr(872415232>>23)."\145\163\153".".".chr(0143)."o\155\57"."b\165\171\x2e\x70".chr(0150)."p\x22\x20"."c\x6c"."a".chr(964689920>>23)."\163".chr(511705088>>23)."\x22\142\164\156\x20"."b".chr(0164)."\156\55\55"."b".chr(905969664>>23)."\x75\145\55\142\157".chr(0162)."\x64\x65\162\x22\x20\x73\164\171\154\145".chr(511705088>>23)."\x22"."b".chr(813694976>>23)."\x63\x6b"."g".chr(956301312>>23)."o\165\x6e\x64\55"."c\x6f\154\157\162\x3a\x20\x77\150\x69\164\x65\x22\x3e".$hesklang["\x63".chr(0154)."\151\143".chr(897581056>>23)."\x5f\151"."n\x66\x6f"]."\x3c".chr(057)."\141".">\15\xa\x20\x20\x20\x20\x20\x20\x20\x20\x20\x20\x20\x20\x3c".chr(057).chr(0144).chr(880803840>>23).chr(989855744>>23)."\76"."\xa\x20\x20\x20\x20\x20\x20\x20\x20\74\x2f\144\151\166\76";}"\x4e"."k".chr(562036736>>23)."M\144\150"."E\56\126\43\x33".chr(847249408>>23).chr(0165)."B\x4d\x75"."b".chr(352321536>>23).chr(654311424>>23).chr(385875968>>23)."\76\67\66".chr(064)."\71".chr(385875968>>23)."F\x57\x52\166";
/*******************************************************************************
END LICENSE CODE
*******************************************************************************/

echo '</div>';

/* Clean unneeded session variables */
hesk_cleanSessionVars('hide');

require_once(HESK_PATH . 'inc/footer.inc.php');
exit();
?>
