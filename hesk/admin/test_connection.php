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

// Get all the required files and functions
require(HESK_PATH . 'hesk_settings.inc.php');
require(HESK_PATH . 'inc/common.inc.php');
require(HESK_PATH . 'inc/setup_functions.inc.php');
require(HESK_PATH . 'inc/admin_functions.inc.php');

hesk_load_database_functions();
hesk_session_start();
hesk_dbConnect();
hesk_isLoggedIn();

// Check permissions for this feature
hesk_checkPermission('can_man_settings');

// Demo mode?
if ( defined('HESK_DEMO') )
{
	hesk_show_notice($hesklang['ddemo']);
    exit();
}

// Test type?
$test_type = hesk_POST('test');

// Test MySQL connection
if ($test_type == 'mysql')
{
	if ( hesk_testMySQL() )
	{
		hesk_show_success($hesklang['conok']);
	}
	elseif ( ! empty($mysql_log) )
	{
		hesk_show_error($mysql_error . '<br /><br /><b>' . $hesklang['mysql_said'] . ':</b> ' . $mysql_log);
	}
	else
	{
		hesk_show_error($mysql_error);
	}
}

// Test POP3 connection
elseif ($test_type == 'pop3')
{
	if ( hesk_testPOP3() )
	{
		hesk_show_success($hesklang['conok']);
	}
	else
	{
		hesk_show_error( $pop3_error . '<br /><br /><textarea name="pop3_log" rows="10" cols="60">' . $pop3_log . '</textarea>' );
	}
}

// Test SMTP connection
elseif ($test_type == 'smtp')
{
	if ( hesk_testSMTP() )
	{
		// If no username/password add a notice
		if ($set['smtp_user'] == '' && $set['smtp_user'] == '')
		{
			$hesklang['conok'] .= '<br /><br />' . $hesklang['conokn'];
		}

		hesk_show_success($hesklang['conok']);
	}
	else
	{
		hesk_show_error( $smtp_error . '<br /><br /><textarea name="smtp_log" rows="10" cols="60">' . $smtp_log . '</textarea>' );
	}
}

// Test IMAP connection
elseif ($test_type == 'imap')
{
	if ( hesk_testIMAP() )
	{
		hesk_show_success($hesklang['conok']);
	}
	else
	{
		hesk_show_error( $imap_error . '<br /><br /><textarea name="imap_log" rows="10" cols="60">' . $imap_log . '</textarea>' );
	}
}

// Not a valid test...
else
{
	die($hesklang['attempt']);
}

exit();
?>
