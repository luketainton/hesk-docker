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

// We will be installing this HESK version:
define('HESK_NEW_VERSION','3.1.1');
define('REQUIRE_PHP_VERSION','5.3.0');
define('REQUIRE_MYSQL_VERSION','5.0.7');

// Other required files and settings
define('INSTALL',1);
define('HIDE_ONLINE',1);

require(HESK_PATH . 'hesk_settings.inc.php');

$hesk_settings['debug_mode'] = 1;
$hesk_settings['language']='English';
$hesk_settings['languages']=array('English' => array('folder'=>'en','hr'=>'------ Reply above this line ------'));

if (!isset($hesk_settings['x_frame_opt']))
{
    $hesk_settings['x_frame_opt'] = 1;
}

if (!isset($hesk_settings['force_ssl']))
{
    $hesk_settings['force_ssl'] = 0;
}

error_reporting(E_ALL);

// Database upgrades from old versions can take quite some time, remove time limit
if ( function_exists('set_time_limit') )
{
	set_time_limit(0);
}

require(HESK_PATH . 'inc/common.inc.php');
require(HESK_PATH . 'inc/admin_functions.inc.php');
require(HESK_PATH . 'inc/setup_functions.inc.php');
hesk_load_database_functions();

// Start the session
hesk_session_start();


// ******* FUNCTIONS ******* //


function hesk_iTestDatabaseConnection($use_existing_settings = false)
{
	global $hesk_db_link, $hesk_settings, $hesklang;

    $db_success = 1;

	// Get MySQL settings, except for successful updates
	if ( ! $use_existing_settings)
	{
		$hesk_settings['db_host'] = hesk_input( hesk_POST('host') );
		$hesk_settings['db_name'] = hesk_input( hesk_POST('name') );
		$hesk_settings['db_user'] = hesk_input( hesk_POST('user') );
		$hesk_settings['db_pass'] = str_replace('&amp;', '&', hesk_input( hesk_POST('pass') ) );

		if (INSTALL_PAGE == 'install.php')
		{
			// Get table prefix, don't allow any special chars
			$hesk_settings['db_pfix'] = preg_replace('/[^0-9a-zA-Z_]/', '', hesk_POST('pfix', 'hesk_') );
		}
	}

	// Use MySQLi extension to connect?
	$use_mysqli = function_exists('mysqli_connect') ? true : false;

    // Start output buffering
    ob_start();

    // Connect to database
    if ($use_mysqli)
    {
		// Do we need a special port? Check and connect to the database
		if ( strpos($hesk_settings['db_host'], ':') )
		{
			list($hesk_settings['db_host_no_port'], $hesk_settings['db_port']) = explode(':', $hesk_settings['db_host']);
			$hesk_db_link = mysqli_connect($hesk_settings['db_host_no_port'], $hesk_settings['db_user'], $hesk_settings['db_pass'], $hesk_settings['db_name'], intval($hesk_settings['db_port']) ) or $db_success=0;
		}
		else
		{
			$hesk_db_link = mysqli_connect($hesk_settings['db_host'], $hesk_settings['db_user'], $hesk_settings['db_pass'], $hesk_settings['db_name']) or $db_success=0;
		}
    }
    else
    {
    	$hesk_db_link = mysql_connect($hesk_settings['db_host'],$hesk_settings['db_user'], $hesk_settings['db_pass']) or $db_success=0;

        // Select database works OK?
        if ($db_success == 1 && ! mysql_select_db($hesk_settings['db_name'], $hesk_db_link) )
        {
	    	// No, try to create the database
			if (function_exists('mysql_create_db') && mysql_create_db($hesk_settings['db_name'], $hesk_db_link))
	        {
	        	if (mysql_select_db($hesk_settings['db_name'], $hesk_db_link))
	            {
					$db_success = 1;
	            }
	            else
	            {
					$db_success = 0;
	            }
	        }
	        else
	        {
	        	$db_success = 0;
	        }
        }
    }

	ob_end_clean();

	// Test DB permissions
	if ($db_success)
	{
		$sql[0] = "DROP TABLE IF EXISTS `".hesk_dbEscape($hesk_settings['db_pfix'])."mysql_test`";
		$sql[1] = "CREATE TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."mysql_test` (`id` smallint(1) unsigned NOT NULL DEFAULT '0') ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
		$sql[2] = "INSERT INTO `".hesk_dbEscape($hesk_settings['db_pfix'])."mysql_test` (`id`) VALUES ('0')";
		$sql[3] = "SELECT `id` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."mysql_test` WHERE `id`='0' LIMIT 1";
		$sql[4] = "UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."mysql_test` SET `id`='1' WHERE `id`='0'";
		$sql[5] = "DELETE FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."mysql_test` WHERE `id`='0'";
		$sql[6] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."mysql_test` ADD `name` CHAR(1) NULL DEFAULT NULL AFTER `id`";
		$sql[7] = "CREATE INDEX `name` ON `".hesk_dbEscape($hesk_settings['db_pfix'])."mysql_test`(`name`) ";
		$sql[8] = "DROP TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."mysql_test`";

		if ($use_mysqli)
		{
			for ($i=0; $i<=8; $i++)
			{
				if (! $res = mysqli_query($hesk_db_link, $sql[$i]))
				{
					global $mysql_log;
					$mysql_log = mysqli_error($hesk_db_link);
					hesk_iDatabase(1);
				}
			}
		}
		else
		{
			for ($i=0; $i<=8; $i++)
			{
				if (! $res = mysql_query($sql[$i], $hesk_db_link))
				{
					global $mysql_log;
					$mysql_log = mysql_error();
					hesk_iDatabase(1);
				}
			}
		}
	}

    // Any errors?
	if ( ! $db_success)
    {
    	global $mysql_log;
    	$mysql_log = $use_mysqli ? mysqli_connect_error() : mysql_error();

		hesk_iDatabase(1);
    }

	// Check MySQL version
	define('MYSQL_VERSION', hesk_dbResult( hesk_dbQuery('SELECT VERSION() AS version') ) );
	if ( version_compare(MYSQL_VERSION,REQUIRE_MYSQL_VERSION,'<') )
	{
		hesk_iDatabase(5);
	}

	return $hesk_db_link;

} // END hesk_iTestDatabaseConnection()


function hesk_iSaveSettingsFile($set)
{
	global $hesk_settings, $hesklang;

	// Make sure OPcache is reset when modifying settings
	if ( function_exists('opcache_reset') )
	{
		opcache_reset();
	}

	$settings_file_content='<?php
// Settings file for HESK ' . $set['hesk_version'] . '

// ==> GENERAL

// --> General settings
$hesk_settings[\'site_title\']=\'' . $set['site_title'] . '\';
$hesk_settings[\'site_url\']=\'' . $set['site_url'] . '\';
$hesk_settings[\'hesk_title\']=\'' . $set['hesk_title'] . '\';
$hesk_settings[\'hesk_url\']=\'' . $set['hesk_url'] . '\';
$hesk_settings[\'webmaster_mail\']=\'' . $set['webmaster_mail'] . '\';
$hesk_settings[\'noreply_mail\']=\'' . $set['noreply_mail'] . '\';
$hesk_settings[\'noreply_name\']=\'' . $set['noreply_name'] . '\';
$hesk_settings[\'site_theme\']=\'' . $set['site_theme'] . '\';

// --> Language settings
$hesk_settings[\'can_sel_lang\']=' . $set['can_sel_lang'] . ';
$hesk_settings[\'language\']=\'' . $set['language'] . '\';
$hesk_settings[\'languages\']=array(
\'English\' => array(\'folder\'=>\'en\',\'hr\'=>\'------ Reply above this line ------\'),
);

// --> Database settings
$hesk_settings[\'db_host\']=\'' . $set['db_host'] . '\';
$hesk_settings[\'db_name\']=\'' . $set['db_name'] . '\';
$hesk_settings[\'db_user\']=\'' . $set['db_user'] . '\';
$hesk_settings[\'db_pass\']=\'' . $set['db_pass'] . '\';
$hesk_settings[\'db_pfix\']=\'' . $set['db_pfix'] . '\';
$hesk_settings[\'db_vrsn\']=' . $set['db_vrsn'] . ';


// ==> HELP DESK

// --> Help desk settings
$hesk_settings[\'admin_dir\']=\'' . $set['admin_dir'] . '\';
$hesk_settings[\'attach_dir\']=\'' . $set['attach_dir'] . '\';
$hesk_settings[\'cache_dir\']=\'' . $set['cache_dir'] . '\';
$hesk_settings[\'max_listings\']=' . $set['max_listings'] . ';
$hesk_settings[\'print_font_size\']=' . $set['print_font_size'] . ';
$hesk_settings[\'autoclose\']=' . $set['autoclose'] . ';
$hesk_settings[\'max_open\']=' . $set['max_open'] . ';
$hesk_settings[\'new_top\']=' . $set['new_top'] . ';
$hesk_settings[\'reply_top\']=' . $set['reply_top'] . ';
$hesk_settings[\'hide_replies\']=' . $set['hide_replies'] . ';
$hesk_settings[\'limit_width\']=' . $set['limit_width'] . ';

// --> Features
$hesk_settings[\'autologin\']=' . $set['autologin'] . ';
$hesk_settings[\'autoassign\']=' . $set['autoassign'] . ';
$hesk_settings[\'require_email\']=' . $set['require_email'] . ';
$hesk_settings[\'require_owner\']=' . $set['require_owner'] . ';
$hesk_settings[\'require_subject\']=' . $set['require_subject'] . ';
$hesk_settings[\'require_message\']=' . $set['require_message'] . ';
$hesk_settings[\'custclose\']=' . $set['custclose'] . ';
$hesk_settings[\'custopen\']=' . $set['custopen'] . ';
$hesk_settings[\'rating\']=' . $set['rating'] . ';
$hesk_settings[\'cust_urgency\']=' . $set['cust_urgency'] . ';
$hesk_settings[\'sequential\']=' . $set['sequential'] . ';
$hesk_settings[\'time_worked\']=' . $set['time_worked'] . ';
$hesk_settings[\'spam_notice\']=' . $set['spam_notice'] . ';
$hesk_settings[\'list_users\']=' . $set['list_users'] . ';
$hesk_settings[\'debug_mode\']=' . $set['debug_mode'] . ';
$hesk_settings[\'short_link\']=' . $set['short_link'] . ';
$hesk_settings[\'select_cat\']=' . $set['select_cat'] . ';
$hesk_settings[\'select_pri\']=' . $set['select_pri'] . ';
$hesk_settings[\'cat_show_select\']=' . $set['cat_show_select'] . ';

// --> SPAM Prevention
$hesk_settings[\'secimg_use\']=' . $set['secimg_use'] . ';
$hesk_settings[\'secimg_sum\']=\'' . $set['secimg_sum'] . '\';
$hesk_settings[\'recaptcha_use\']=' . $set['recaptcha_use'] . ';
$hesk_settings[\'recaptcha_public_key\']=\'' . $set['recaptcha_public_key'] . '\';
$hesk_settings[\'recaptcha_private_key\']=\'' . $set['recaptcha_private_key'] . '\';
$hesk_settings[\'question_use\']=' . $set['question_use'] . ';
$hesk_settings[\'question_ask\']=\'' . $set['question_ask'] . '\';
$hesk_settings[\'question_ans\']=\'' . $set['question_ans'] . '\';

// --> Security
$hesk_settings[\'attempt_limit\']=' . $set['attempt_limit'] . ';
$hesk_settings[\'attempt_banmin\']=' . $set['attempt_banmin'] . ';
$hesk_settings[\'reset_pass\']=' . $set['reset_pass'] . ';
$hesk_settings[\'email_view_ticket\']=' . $set['email_view_ticket'] . ';
$hesk_settings[\'x_frame_opt\']=' . $set['x_frame_opt'] . ';
$hesk_settings[\'force_ssl\']=' . $set['force_ssl'] . ';

// --> Attachments
$hesk_settings[\'attachments\']=array (
\'use\' => ' . $set['attachments']['use'] . ',
\'max_number\' => ' . $set['attachments']['max_number'] . ',
\'max_size\' => ' . $set['attachments']['max_size'] . ',
\'allowed_types\' => array(\'' . implode('\',\'',$set['attachments']['allowed_types']) . '\')
);


// ==> KNOWLEDGEBASE

// --> Knowledgebase settings
$hesk_settings[\'kb_enable\']=' . $set['kb_enable'] . ';
$hesk_settings[\'kb_wysiwyg\']=' . $set['kb_wysiwyg'] . ';
$hesk_settings[\'kb_search\']=' . $set['kb_search'] . ';
$hesk_settings[\'kb_search_limit\']=' . $set['kb_search_limit'] . ';
$hesk_settings[\'kb_views\']=' . $set['kb_views'] . ';
$hesk_settings[\'kb_date\']=' . $set['kb_date'] . ';
$hesk_settings[\'kb_recommendanswers\']=' . $set['kb_recommendanswers'] . ';
$hesk_settings[\'kb_rating\']=' . $set['kb_rating'] . ';
$hesk_settings[\'kb_substrart\']=' . $set['kb_substrart'] . ';
$hesk_settings[\'kb_cols\']=' . $set['kb_cols'] . ';
$hesk_settings[\'kb_numshow\']=' . $set['kb_numshow'] . ';
$hesk_settings[\'kb_popart\']=' . $set['kb_popart'] . ';
$hesk_settings[\'kb_latest\']=' . $set['kb_latest'] . ';
$hesk_settings[\'kb_index_popart\']=' . $set['kb_index_popart'] . ';
$hesk_settings[\'kb_index_latest\']=' . $set['kb_index_latest'] . ';
$hesk_settings[\'kb_related\']=' . $set['kb_related'] . ';


// ==> EMAIL

// --> Email sending
$hesk_settings[\'smtp\']=' . $set['smtp'] . ';
$hesk_settings[\'smtp_host_name\']=\'' . $set['smtp_host_name'] . '\';
$hesk_settings[\'smtp_host_port\']=' . $set['smtp_host_port'] . ';
$hesk_settings[\'smtp_timeout\']=' . $set['smtp_timeout'] . ';
$hesk_settings[\'smtp_ssl\']=' . $set['smtp_ssl'] . ';
$hesk_settings[\'smtp_tls\']=' . $set['smtp_tls'] . ';
$hesk_settings[\'smtp_user\']=\'' . $set['smtp_user'] . '\';
$hesk_settings[\'smtp_password\']=\'' . $set['smtp_password'] . '\';

// --> Email piping
$hesk_settings[\'email_piping\']=' . $set['email_piping'] . ';

// --> POP3 Fetching
$hesk_settings[\'pop3\']=' . $set['pop3'] . ';
$hesk_settings[\'pop3_job_wait\']=' . $set['pop3_job_wait'] . ';
$hesk_settings[\'pop3_host_name\']=\'' . $set['pop3_host_name'] . '\';
$hesk_settings[\'pop3_host_port\']=' . $set['pop3_host_port'] . ';
$hesk_settings[\'pop3_tls\']=' . $set['pop3_tls'] . ';
$hesk_settings[\'pop3_keep\']=' . $set['pop3_keep'] . ';
$hesk_settings[\'pop3_user\']=\'' . $set['pop3_user'] . '\';
$hesk_settings[\'pop3_password\']=\'' . $set['pop3_password'] . '\';

// --> IMAP Fetching
$hesk_settings[\'imap\']=' . $set['imap'] . ';
$hesk_settings[\'imap_job_wait\']=' . $set['imap_job_wait'] . ';
$hesk_settings[\'imap_host_name\']=\'' . $set['imap_host_name'] . '\';
$hesk_settings[\'imap_host_port\']=' . $set['imap_host_port'] . ';
$hesk_settings[\'imap_enc\']=\'' . $set['imap_enc'] . '\';
$hesk_settings[\'imap_keep\']=' . $set['imap_keep'] . ';
$hesk_settings[\'imap_user\']=\'' . $set['imap_user'] . '\';
$hesk_settings[\'imap_password\']=\'' . $set['imap_password'] . '\';

// --> Email loops
$hesk_settings[\'loop_hits\']=' . $set['loop_hits'] . ';
$hesk_settings[\'loop_time\']=' . $set['loop_time'] . ';

// --> Detect email typos
$hesk_settings[\'detect_typos\']=' . $set['detect_typos'] . ';
$hesk_settings[\'email_providers\']=array(' . $set['email_providers'] . ');

// --> Notify customer when
$hesk_settings[\'notify_new\']=' . $set['notify_new'] . ';
$hesk_settings[\'notify_skip_spam\']=' . $set['notify_skip_spam'] . ';
$hesk_settings[\'notify_spam_tags\']=array(' . $set['notify_spam_tags'] . ');
$hesk_settings[\'notify_closed\']=' . $set['notify_closed'] . ';

// --> Other
$hesk_settings[\'strip_quoted\']=' . $set['strip_quoted'] . ';
$hesk_settings[\'eml_req_msg\']=' . $set['eml_req_msg'] . ';
$hesk_settings[\'save_embedded\']=' . $set['save_embedded'] . ';
$hesk_settings[\'multi_eml\']=' . $set['multi_eml'] . ';
$hesk_settings[\'confirm_email\']=' . $set['confirm_email'] . ';
$hesk_settings[\'open_only\']=' . $set['open_only'] . ';


// ==> TICKET LIST

$hesk_settings[\'ticket_list\']=array(\'' . implode('\',\'',$set['ticket_list']) . '\');

// --> Other
$hesk_settings[\'submittedformat\']=' . $set['submittedformat'] . ';
$hesk_settings[\'updatedformat\']=' . $set['updatedformat'] . ';


// ==> MISC

// --> Date & Time
$hesk_settings[\'timezone\']=\'' . $set['timezone'] . '\';
$hesk_settings[\'timeformat\']=\'' . $set['timeformat'] . '\';
$hesk_settings[\'time_display\']=\'' . $set['time_display'] . '\';

// --> Other
$hesk_settings[\'ip_whois\']=\'' . $set['ip_whois'] . '\';
$hesk_settings[\'maintenance_mode\']=' . $set['maintenance_mode'] . ';
$hesk_settings[\'alink\']=' . $set['alink'] . ';
$hesk_settings[\'submit_notice\']=' . $set['submit_notice'] . ';
$hesk_settings[\'online\']=' . $set['online'] . ';
$hesk_settings[\'online_min\']=' . $set['online_min'] . ';
$hesk_settings[\'check_updates\']=' . $set['check_updates'] . ';


#############################
#     DO NOT EDIT BELOW     #
#############################
$hesk_settings[\'hesk_version\']=\'' . $set['hesk_version'] . '\';
if ($hesk_settings[\'debug_mode\'])
{
    error_reporting(E_ALL);
}
else
{
    error_reporting(0);
}
if (!defined(\'IN_SCRIPT\')) {die(\'Invalid attempt!\');}';

	// Write to the settings file
	if ( ! file_put_contents(HESK_PATH . 'hesk_settings.inc.php', $settings_file_content) )
	{
		hesk_error($hesklang['err_openset']);
	}

	return true;
} // END hesk_iSaveSettingsFile()


function hesk_iDatabase($problem=0)
{
    global $hesk_settings, $hesk_db_link, $mysql_log;

    hesk_iHeader();
	?>

	<h3>Database settings</h3>

	<br />

	<?php
	if ($problem == 1)
	{
	    hesk_show_error('<br /><br />Double-check all the information below. Contact your hosting company for the correct information to use!<br /><br /><b>MySQL said:</b> '.$mysql_log.'</p>', 'Database connection failed');
	}
    elseif ($problem == 2)
    {
	    hesk_show_error('<b>Database tables already exist!</b><br /><br />
        HESK database tables with <b>'.$hesk_settings['db_pfix'].'</b> prefix already exist in this database!<br /><br />
	    To upgrade an existing HESK installation select <a href="index.php">Update existing install</a> instead.<br /><br />
	    To install a new copy of HESK in the same database use a unique table prefix.');
    }
    elseif ($problem == 3)
    {
	    hesk_show_error('<b>Old database tables not found!</b><br /><br />
        HESK database tables have not been found in this database!<br /><br />
	    To install HESK use the <a href="index.php">New install</a> option instead.');
    }
    elseif ($problem == 4)
    {
	    hesk_show_error('<b>Version '.HESK_NEW_VERSION.' tables already exist!</b><br /><br />
        Your database seems to be compatible with HESK version '.HESK_NEW_VERSION.'<br /><br />
	    To install a new copy of HESK use the <a href="index.php">New install</a> option instead.');
    }
	elseif ($problem == 5)
	{
		hesk_show_error('MySQL version <b>'.REQUIRE_MYSQL_VERSION.'+</b> required, you are using: <b>' . MYSQL_VERSION . '</b><br /><br />
		You are using and old and insecure MySQL version with known bugs, security issues and outdated functionality.<br /><br />
		Ask your hosting company to update your MySQL version.');
	}
    elseif ($problem == 6)
    {
        hesk_show_notice('Please select your help desk timezone'); 
    }
    else
    {
    	hesk_show_notice('Contact your host for help with correct database settings', 'Tip');
    }
	?>

	<div align="center">
	<table border="0" width="750" cellspacing="1" cellpadding="5" class="white">
	<tr>
	<td>

	<form action="<?php echo INSTALL_PAGE; ?>" method="post">
	<table>
	<tr>
	<td width="200">Database Host:</td>
	<td><input type="text" name="host" value="<?php echo $hesk_settings['db_host']; ?>" size="40" autocomplete="off" /></td>
	</tr>
	<tr>
	<td width="200">Database Name:</td>
	<td><input type="text" name="name" value="<?php echo $hesk_settings['db_name']; ?>" size="40" autocomplete="off" /></td>
	</tr>
	<tr>
	<td width="200">Database User (login):</td>
	<td><input type="text" name="user" value="<?php echo $hesk_settings['db_user']; ?>" size="40" autocomplete="off" /></td>
	</tr>
	<tr>
	<td width="200">User Password:</td>
	<td><input type="text" name="pass" value="<?php echo $hesk_settings['db_pass']; ?>" size="40" autocomplete="off" /></td>
	</tr>
	<?php
	if (INSTALL_PAGE == 'install.php')
	{
		?>
		<tr>
		<td width="200">Table prefix:</td>
		<td><input type="text" name="pfix" value="<?php echo $hesk_settings['db_pfix']; ?>" size="40" autocomplete="off" /></td>
		</tr>

		</table>

		<hr />

		<script language="javascript" type="text/javascript"><!--
		function hesk_randomPassword()                                                            
		{
			chars = '23456789abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ!@#$%*()_+-={}[]:?,.';
			length = Math.floor(Math.random() * (5)) + 8;
			var result = '';
			for (var i = length; i > 0; --i) result += chars[Math.round(Math.random() * (chars.length - 1))];
			return result;
		}
		//-->
		</script>

		<h3>HESK login details</h3>

		<p>Username and password you will use to login into HESK administration.</p>

		<table>
		<tr>
		<td width="200">Choose a Username:</td>
		<td><input type="text" name="admin_user" value="<?php echo isset($_SESSION['admin_user']) ? stripslashes($_SESSION['admin_user']) : 'Administrator'; ?>" size="40" autocomplete="off" /></td>
		</tr>
		<tr>
		<td width="200">Choose a Password:</td>
		<td><input type="text" name="admin_pass" id="admin_pass" value="<?php echo isset($_SESSION['admin_pass']) ? stripslashes($_SESSION['admin_pass']) : ''; ?>" size="40" autocomplete="off" /></td>
		</tr>
		<tr>
		<td width="200">&nbsp;</td>
		<td style="text-align:right"><a href="javascript:void(0)" onclick="javascript:getElementById('admin_pass').value = hesk_randomPassword();">Generate a random password</a></td>
		</tr>
		</table>

		<?php
	}
	else
	{
		?>
		</table>
		<?php
	}
	?>

    <hr />

    <h3>Other info</h3>

    <table>
    <tr>
    <td width="200">Help desk timezone:</td>
    <td>
    <?php
    // Get list of supported timezones
    $timezone_list = hesk_generate_timezone_list();
    ?>
    <select name="timezone">
    <?php
    foreach ($timezone_list as $timezone => $description)
    {
        echo '<option value="' . $timezone . '"' . ($hesk_settings['timezone'] == $timezone ? ' selected="selected"' : '') . '>' . $description . '</option>';
    }
    ?>
    </select>
    </td>
    </tr>
    </table>

    <p>&nbsp;</p>

	<p align="center"><input type="hidden" name="dbtest" value="1" /><input type="submit" value="Continue to Step 4" class="orangebutton" onmouseover="hesk_btn(this,'orangebuttonover');" onmouseout="hesk_btn(this,'orangebutton');" /></p>
	</form>

	</td>
	</tr>
	</table>
	</div>

	<?php
    hesk_iFooter();
} // End hesk_iDatabase()


function hesk_iCheckSetup()
{
    global $hesk_settings;

    $correct_these = array();

    // 1. PHP 5+ required
    if ( function_exists('version_compare') && version_compare(PHP_VERSION,REQUIRE_PHP_VERSION,'<') )
    {
		$correct_these[] = '
		PHP version <b>'.REQUIRE_PHP_VERSION.'+</b> required, you are using: <b>' . PHP_VERSION . '</b><br /><br />
		You are using and old and insecure PHP version with known bugs, security issues and outdated functionality.<br /><br />
		Ask your hosting company to update your PHP version.
		';
    }

    // 2. File hesk_settings.inc.php must be writable
	if ( ! is__writable(HESK_PATH . 'hesk_settings.inc.php') )
	{
		// -> try to CHMOD it
		if ( function_exists('chmod') )
		{
			@chmod(HESK_PATH . 'hesk_settings.inc.php', 0666);
		}

		// -> test again
		if ( ! is__writable(HESK_PATH . 'hesk_settings.inc.php') )
		{
			$correct_these[] = '
			File <b>hesk_settings.inc.php</b> is not writable by PHP.<br /><br />
			Make sure PHP has permission to write to file <b>hesk_settings.inc.php</b><br /><br />
			&raquo; on <b>Linux</b> servers <a href="https://www.phpjunkyard.com/tutorials/ftp-chmod-tutorial.php">CHMOD</a> this file to 666 (rw-rw-rw-)<br />
	        &raquo; on <b>Windows</b> servers allow Internet Guest Account to modify the file<br />
	        &raquo; contact your hosting company for help with setting up file permissions.
			';
		}
	}

    // 3. Folder attachments must exist
    $hesk_settings['attach_dir_name'] = isset($hesk_settings['attach_dir']) ? $hesk_settings['attach_dir'] : 'attachments';
    $hesk_settings['attach_dir'] = HESK_PATH . $hesk_settings['attach_dir_name'];

	// -> Try to create it
	if ( ! file_exists($hesk_settings['attach_dir']) )
	{
	    @mkdir($hesk_settings['attach_dir'], 0755);
	}

    // -> Is the folder now there?
	if ( is_dir($hesk_settings['attach_dir']) )
    {

		// -> Is it writable?
	    if ( ! is__writable($hesk_settings['attach_dir']) )
	    {
			// -> try to CHMOD it
			@chmod($hesk_settings['attach_dir'], 0777);

			// -> test again
			if ( ! is__writable($hesk_settings['attach_dir']) )
			{
				$correct_these[] = '
				Folder <b>' . $hesk_settings['attach_dir_name'] . '</b> is not writable by PHP.<br /><br />
				Make sure PHP has permission to write to folder <b>' . $hesk_settings['attach_dir_name'] . '</b><br /><br />
				&raquo; on <b>Linux</b> servers <a href="https://www.phpjunkyard.com/tutorials/ftp-chmod-tutorial.php">CHMOD</a> this folder to 777 (rwxrwxrwx)<br />
		        &raquo; on <b>Windows</b> servers allow Internet Guest Account to modify the folder<br />
		        &raquo; contact your hosting company for help with setting up folder permissions.
				';
		   	}
	    }
	}
	else
	{
		$correct_these[] = '
		Folder <b>' . $hesk_settings['attach_dir_name'] . '</b> is missing.<br /><br />
		Create a folder called <b>' . $hesk_settings['attach_dir_name'] . '</b> inside your main HESK folder.<br /><br />
		';
	}

	// 3.2 Folder cache must exist
	$hesk_settings['cache_dir_name'] = isset($hesk_settings['cache_dir']) ? $hesk_settings['cache_dir'] : 'cache';
	$hesk_settings['cache_dir'] = HESK_PATH . $hesk_settings['cache_dir_name'];

	// -> Try to create it
	if ( ! file_exists($hesk_settings['cache_dir']) )
	{
	    @mkdir($hesk_settings['cache_dir'], 0755);
	}

	// -> Is the folder now there?
	if ( is_dir($hesk_settings['cache_dir']) )
	{

		// -> Is it writable?
		if ( ! is__writable($hesk_settings['cache_dir']) )
		{
			// -> try to CHMOD it
			@chmod($hesk_settings['cache_dir'], 0777);

			// -> test again
			if ( ! is__writable($hesk_settings['cache_dir']) )
			{
				$correct_these[] = '
				Folder <b>' . $hesk_settings['cache_dir_name'] . '</b> is not writable by PHP.<br /><br />
				Make sure PHP has permission to write to folder <b>' . $hesk_settings['cache_dir_name'] . '</b><br /><br />
				&raquo; on <b>Linux</b> servers <a href="https://www.phpjunkyard.com/tutorials/ftp-chmod-tutorial.php">CHMOD</a> this folder to 777 (rwxrwxrwx)<br />
				&raquo; on <b>Windows</b> servers allow Internet Guest Account to modify the folder<br />
				&raquo; contact your hosting company for help with setting up folder permissions.
				';
			}
		}
	}
	else
	{
		$correct_these[] = '
		Folder <b>' . $hesk_settings['cache_dir_name'] . '</b> is missing.<br /><br />
		Create a folder called <b>' . $hesk_settings['cache_dir_name'] . '</b> inside your main HESK folder.<br /><br />
		';
	}

    // 4. MySQL must be available
	if ( ! function_exists('mysql_connect') && ! function_exists('mysqli_connect') )
	{
		$correct_these[] = '
		MySQL is disabled.<br /><br />
		HESK requires MySQL to be installed and enabled.<br /><br />
        Ask your hosting company to enable MySQL for PHP.
		';
	}

    // 5. Can we use GD library?
	$GD_LIB = ( extension_loaded('gd') && function_exists('gd_info') ) ? true : false;

	// 6. Make sure old files are deleted
	$hesk_settings['admin_dir'] = isset($hesk_settings['admin_dir']) ? $hesk_settings['admin_dir'] : 'admin';
	$old_files = array(

	    // pre-0.93 *.inc files
	    'hesk_settings.inc','hesk.sql','inc/common.inc','inc/database.inc','inc/footer.inc','inc/header.inc',
	    'inc/print_tickets.inc','inc/show_admin_nav.inc','inc/show_search_form.inc','install.php','update.php',

		// pre-2.0 files
		'admin.php','admin_change_status.php','admin_main.php','admin_move_category','admin_reply_ticket.php',
	    'admin_settings.php','admin_settings_save.php','admin_ticket.php','archive.php',
	    'delete_tickets.php','find_tickets.php','manage_canned.php','manage_categories.php',
	    'manage_users.php','profile.php','show_tickets.php',

		// pre-2.1 files
		'emails/','language/english.php',

	    // pre-2.3 files
	    'secimg.inc.php',

	    // pre-2.4 files
	    'hesk_style_v23.css','TreeMenu.js',

        // malicious files that were found on some websites illegally redistributing HESK
        'inc/tiny_mce/utils/r00t10.php', 'language/en/help_files/r00t10.php',

        // pre-2.5 files
        'hesk_style_v24.css', 'hesk_javascript_v24.js',

        // pre-2.6 files
        'hesk_style_v25.css', 'hesk_javascript_v25.js',

		// pre-2.7 files,
		$hesk_settings['admin_dir'].'/options.php',

        // pre-3.0 files,
        $hesk_settings['admin_dir'].'/admin_settings.php',
        'img/add_article.png',
        'img/add_category.png',
        'img/anonymize.png',
        'img/article_text.png',
        'img/autoassign_off.png',
        'img/autoassign_on.png',
        'img/ban.png',
        'img/banned.png',
        'img/blank.gif',
        'img/bluebtn.png',
        'img/clip.png',
        'img/code.png',
        'img/code_off.png',
        'img/delete.png',
        'img/delete_off.png',
        'img/delete_ticket.png',
        'img/edit.png',
        'img/email.png',
        'img/error.png',
        'img/existingticket.png',
        'img/export.png',
        'img/flag_critical.png',
        'img/flag_high.png',
        'img/flag_low.png',
        'img/flag_low2.png',
        'img/flag_medium.png',
        'img/folder-expanded.gif',
        'img/folder.gif',
        'img/greenbtn.jpg',
        'img/greenbtnover.gif',
        'img/header.png',
        'img/headerbgsm.jpg',
        'img/headerleftsm.jpg',
        'img/headerrightsm.jpg',
        'img/header_bottom.png',
        'img/header_bottom_left.png',
        'img/header_bottom_right.png',
        'img/header_left.png',
        'img/header_right.png',
        'img/header_top.png',
        'img/header_up_left.png',
        'img/header_up_right.png',
        'img/ico-search.png',
        'img/ico_canned.gif',
        'img/ico_categories.gif',
        'img/ico_home.gif',
        'img/ico_kb.gif',
        'img/ico_logout.gif',
        'img/ico_mail.gif',
        'img/ico_profile.gif',
        'img/ico_reports.gif',
        'img/ico_settings.gif',
        'img/ico_tools.png',
        'img/ico_users.gif',
        'img/import_kb.png',
        'img/import_kb1.png',
        'img/inbox.png',
        'img/info.png',
        'img/link.png',
        'img/lock.png',
        'img/login.png',
        'img/mail.png',
        'img/manage.png',
        'img/menu.png',
        'img/move_down.png',
        'img/move_down1.png',
        'img/move_down2.png',
        'img/move_down3.png',
        'img/move_down4.png',
        'img/move_down5.png',
        'img/move_down6.png',
        'img/move_down7.png',
        'img/move_down8.png',
        'img/move_down9.png',
        'img/move_up.png',
        'img/move_up1.png',
        'img/move_up2.png',
        'img/move_up3.png',
        'img/move_up4.png',
        'img/move_up5.png',
        'img/move_up6.png',
        'img/move_up7.png',
        'img/move_up8.png',
        'img/move_up9.png',
        'img/newticket.png',
        'img/new_mail.png',
        'img/notice.png',
        'img/online_off.png',
        'img/online_on.png',
        'img/orangebtn.jpg',
        'img/orangebtnover.gif',
        'img/orangebtnsec.jpg',
        'img/outbox.png',
        'img/print.png',
        'img/private.png',
        'img/public.png',
        'img/refresh.png',
        'img/reload.png',
        'img/roundcornersb.jpg',
        'img/roundcornerslb.jpg',
        'img/roundcornerslm.jpg',
        'img/roundcornerslt.jpg',
        'img/roundcornersrb.jpg',
        'img/roundcornersrm.jpg',
        'img/roundcornersrt.jpg',
        'img/roundcornerst.jpg',
        'img/sort_priority_asc.png',
        'img/sort_priority_desc.png',
        'img/star_0.png',
        'img/star_10.png',
        'img/star_15.png',
        'img/star_20.png',
        'img/star_25.png',
        'img/star_30.png',
        'img/star_35.png',
        'img/star_40.png',
        'img/star_45.png',
        'img/star_50.png',
        'img/sticky.png',
        'img/sticky_off.png',
        'img/success.png',
        'img/tableheader.jpg',
        'img/tag.png',
        'img/tag_off.png',
        'img/unlock.png',
        'img/vertical.jpg',
        'img/view.png',
	    );

	sort($old_files);

	$still_exist = array();

	foreach ($old_files as $f)
	{
		if (file_exists(HESK_PATH . $f))
	    {
            //Try to remove the file
            @unlink(HESK_PATH . $f);

            // If not successful, ask the user to delete those files
            if (file_exists(HESK_PATH . $f))
            {
	    	    $still_exist[] = $f;
            }
	    }
	}

    $old_folders = array(
        // pre-2.4 folders
        'help_files/',

        // pre-3.0 folders
        'inc/calendar/',
    );

	foreach ($old_folders as $f)
	{
		if (is_dir(HESK_PATH . $f))
	    {
            //Try to remove the folder
            hesk_rrmdir(HESK_PATH . $f);

            // If not successful, ask the user to delete those folders
            if (is_dir(HESK_PATH . $f))
            {
	    	    $still_exist[] = $f;
            }
	    }
	}

	if ( count($still_exist) )
	{
        sort(array_unique($still_exist));

		$correct_these[] = '
		Outdated files and folders<br /><br />
		For security reasons please delete these legacy files and folders:<br />
        <ul><li><b>'.implode('</b></li><li><b>',$still_exist).'</b></li></ul>
		';
	}

    // Do we have any errors?
    if ( count($correct_these) )
    {
		hesk_iHeader();
        ?>

        &nbsp;

		<?php
        foreach ($correct_these as $correct_this)
        {
        	hesk_show_error($correct_this);
            echo "&nbsp;";
        }
        ?>

		<form method="post" action="<?php echo INSTALL_PAGE; ?>">
		<p align="center"><input type="submit" onclick="javascript:this.value='Working, please wait...'" value="Click here to TEST AGAIN" class="orangebutton" onmouseover="hesk_btn(this,'orangebuttonover');" onmouseout="hesk_btn(this,'orangebutton');" /></p>
		</form>
        <p>&nbsp;</p>
        <?php
        hesk_iFooter();
    }

    // If all tests were successful, we can continue to the next step
    $_SESSION['set_attachments'] = 1;
	$_SESSION['set_captcha'] = $GD_LIB ? 1 : 0;
	$_SESSION['use_spamq'] = $GD_LIB ? 0 : 1;
	$_SESSION['step'] = 3;

	// When updating, first try saved MySQL info
	if (INSTALL_PAGE == 'update.php')
	{
		header('Location: ' . INSTALL_PAGE);
	}
	else
	{
		hesk_iDatabase();
	}
	exit();
}


function hesk_iStart()
{
	global $hesk_settings;

	// Set this session variable to check later if sessions are working
	$_SESSION['works'] = true;

	hesk_iHeader();

    $eula_alt =  '
        <div style="text-align:justify; width:100%">
        <p><b>HESK Software End User License Agreement</b></p>
        <p><a href="https://www.hesk.com/eula.php" target="_blank">Read the HESK End-User License Agreement here</a></p>
        </div>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
    ';

    echo file_exists('../docs/license.html') ? '<iframe src="../docs/license.html" style="border:1px solid #ccc; height:250px; width:100%">'.$eula_alt.'</iframe>' : $eula_alt;
	?>

<p>&nbsp;</p>

<form method="post" action="<?php echo INSTALL_PAGE; ?>" name="license">
<div align="center">
<table border="0">
<tr>
<td>

	<p><b>Do you accept the HESK Software End-User License Agreement?</b></b><br />&nbsp;</p>

	<p align="center">
	<input type="hidden" name="agree" value="YES" />
	<input type="button" onclick="javascript:parent.location='index.php'" value="I DO NOT ACCEPT (Cancel setup)" class="orangebuttonsec" onmouseover="hesk_btn(this,'orangebuttonsecover');" onmouseout="hesk_btn(this,'orangebuttonsec');" />
	&nbsp;
	<input type="submit" onclick="javascript:this.value='Working, please wait...'" value="I ACCEPT (Click to continue) &raquo;" class="orangebutton" onmouseover="hesk_btn(this,'orangebuttonover');" onmouseout="hesk_btn(this,'orangebutton');" />
	</p>

    <p><img src="https://www.hesk.com/images/space.gif" width="10" height="10" alt="" border="0" />&nbsp;</p>

</td>
</tr>
</table>
</div>
</form>

	<?php
    hesk_iFooter();
} // End hesk_iStart()


function hesk_iHeader()
{
    global $hesk_settings;

	$steps = array(
    	1 => '1. License agreement',
        2 => '2. Check setup',
        3 => '3. Setup Database',
        4 => '4. Finishing touches'
        );

	?>
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
	<title>HESK setup script: <?php echo HESK_NEW_VERSION; ?></title>
	<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
	<link href="hesk_style.css?<?php echo HESK_NEW_VERSION; ?>" type="text/css" rel="stylesheet" />
	<script type="text/javascript" src="hesk_javascript.js?<?php echo HESK_NEW_VERSION; ?>"></script>
	</head>
	<body>

	<div align="center">
	<table border="0" cellspacing="0" cellpadding="5" class="enclosing">
	<tr>
	<td>
		<table width="100%" border="0" cellspacing="0" cellpadding="0">
		<tr>
		<td width="3"><img src="img/headerleftsm.jpg" width="3" height="25" alt="" /></td>
		<td class="headersm">HESK setup script: <?php echo HESK_NEW_VERSION; ?></td>
		<td width="3"><img src="img/headerrightsm.jpg" width="3" height="25" alt="" /></td>
		</tr>
		</table>
	</td>
	</tr>
	<tr>
	<td>

    <?php
    if ( isset($_SESSION['step']) )
    {
    	$_SESSION['step'] = intval($_SESSION['step']);
    	?>

		<table border="0" width="100%">
		<tr>
		<td>
        <?php
        foreach ($steps as $number => $description)
        {
        	if ($number == $_SESSION['step'])
            {
            	$steps[$number] = '<b>' . $steps[$number] . '</b>';
            }
            elseif ($number < $_SESSION['step'])
            {
            	$steps[$number] = '<span style="color:#008000">' . $steps[$number] . '</span>';
            }
        }

        echo implode(' &raquo; ', $steps);
        ?>
        </td>
		</tr>
		</table>

		<br />
	<?php
    }
    else
    {
		hesk_show_notice('<a href="../docs/index.html">Read installation guide</a> before using this setup script!', 'Important');
    }
    ?>

    <div align="center">
	<table border="0" cellspacing="0" cellpadding="0" width="100%">
	<tr>
		<td width="7" height="7"><img src="img/roundcornerslt.jpg" width="7" height="7" alt="" /></td>
		<td class="roundcornerstop"></td>
		<td><img src="img/roundcornersrt.jpg" width="7" height="7" alt="" /></td>
	</tr>
	<tr>
		<td class="roundcornersleft">&nbsp;</td>
		<td>
	<?php
} // End hesk_iHeader()


function hesk_iFooter()
{
	global $hesk_settings;
	?>
		</td>
		<td class="roundcornersright">&nbsp;</td>
	</tr>
	<tr>
		<td><img src="img/roundcornerslb.jpg" width="7" height="7" alt="" /></td>
		<td class="roundcornersbottom"></td>
		<td width="7" height="7"><img src="img/roundcornersrb.jpg" width="7" height="7" alt="" /></td>
	</tr>
	</table>
    </div>

	<p style="text-align:center"><span class="smaller">&nbsp;<br />Powered by <a href="https://www.hesk.com" class="smaller" title="Free PHP Help Desk Software">Help Desk Software</a> <b>HESK</b>, in partnership with <a href="https://www.sysaid.com/?utm_source=Hesk&utm_medium=cpc&utm_campaign=HeskProduct_To_HP">SysAid Technologies</a></span></p>
	</td>
	</tr>
	</table>
	</div>
	</body>
	</html>
	<?php
    exit();
} // End hesk_iFooter()


function hesk_iSessionError()
{
	hesk_session_stop();
	hesk_iHeader();
	?>

	<br />
	<div class="error">
		<img src="<?php echo HESK_PATH; ?>install/img/error.png" width="16" height="16" border="0" alt="" style="vertical-align:text-bottom" />
		<b>Error:</b> PHP sessions not working!<br /><br />Note that this is a server configuration issue, not a HESK issue.<br /><br />Please contact your hosting company and ask them to verify why PHP sessions aren't working on your server!
	</div>
	<br />

	<form method="get" action="<?php echo INSTALL_PAGE; ?>">
	<p align="center"><input type="submit" value="&laquo; Start over" class="orangebutton" onmouseover="hesk_btn(this,'orangebuttonover');" onmouseout="hesk_btn(this,'orangebutton');" /></p>
	</form>

	<?php
	hesk_iFooter();
} // END hesk_iSessionError()


function hesk_compareVariable($k,$v)
{
	global $hesk_settings;

    if (is_array($v))
    {
    	foreach ($v as $sub_k => $sub_v)
        {
			$v[$k] = hesk_compareVariable($sub_k,$sub_v);
        }
    }

	if (isset($hesk_settings[$k]))
    {
    	return $hesk_settings[$k];
    }
    else
    {
    	return $v;
    }
} // END hesk_compareVariable()


function is__writable($path)
{
//will work in despite of Windows ACLs bug
//NOTE: use a trailing slash for folders!!!
//see http://bugs.php.net/bug.php?id=27609
//see http://bugs.php.net/bug.php?id=30931

    if ($path[strlen($path)-1]=='/') // recursively return a temporary file path
        return is__writable($path.uniqid(mt_rand()).'.tmp');
    else if (is_dir($path))
        return is__writable($path.'/'.uniqid(mt_rand()).'.tmp');
    // check tmp file for read/write capabilities
    $rm = file_exists($path);
    $f = @fopen($path, 'a');
    if ($f===false)
        return false;
    fclose($f);
    if (!$rm)
        unlink($path);
    return true;
} // END is__writable()
