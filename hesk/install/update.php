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

define('INSTALL_PAGE', 'update.php');
require(HESK_PATH . 'install/install_functions.inc.php');

// Convert old database settings
if (isset($hesk_settings['database_user']))
{
	$hesk_settings['db_user'] = $hesk_settings['database_user'];
	$hesk_settings['db_name'] = $hesk_settings['database_name'];
	$hesk_settings['db_pass'] = $hesk_settings['database_pass'];
	$hesk_settings['db_host'] = $hesk_settings['database_host'];
}

// Set the table prefix to default for versions older than 2.0
if ( ! isset($hesk_settings['db_pfix']))
{
	$hesk_settings['db_pfix'] = 'hesk_';
}

// If no step is defined, start with step 1
if ( ! isset($_SESSION['step']) )
{
    $_SESSION['step']=1;
}
// Check if the license has been agreed to and verify sessions are working
elseif ($_SESSION['step']==1)
{
    $agree = hesk_POST('agree', '');
    if ($agree == 'YES')
    {
		// Are sessions working?
		if ( empty($_SESSION['works']) )
        {
        	hesk_iSessionError();
        }

		// All OK, continue
        $_SESSION['license_agree']=1;
        $_SESSION['step']=2;
    }
    else
    {
        $_SESSION['step']=1;
    }
}

// Test database connection?
if ($_SESSION['step'] == 3)
{
    // Test MySQL connection
    if (isset($_POST['dbtest']))
    {
        // Get timezone info
        $hesk_settings['timezone'] = hesk_input( hesk_POST('timezone') );
        if ( ! in_array($hesk_settings['timezone'], timezone_identifiers_list()) )
        {
            $hesk_settings['timezone'] = 'UTC';
        }

        // Test MySQL connection using provided data
        $hesk_db_link = hesk_iTestDatabaseConnection();
    }
    else
    {
       // Force selecting a timezone for versions prior to 2.8.0
       if (version_compare(HESK_NEW_VERSION,'2.8.0','<='))
       {
           hesk_iDatabase(6);
       }

       // Test MySQL connection using saved data
       $hesk_db_link = hesk_iTestDatabaseConnection(true);
    }

	// Detect which version we are updating from
	$hesk_settings['update_from'] = hesk_iDetectVersion();

	// Is the installed version current?
	if ($hesk_settings['update_from'] == HESK_NEW_VERSION)
    {
		hesk_iDatabase(4);
    }

	// All ok, let's save settings
	hesk_iSaveSettings();

	// Now update HESK database tables
	hesk_iUpdateTables();

	// And move to the next step
	$_SESSION['step']=4;
}

// Which step are we at?
switch ($_SESSION['step'])
{
	case 2:
	   hesk_iCheckSetup();
	   break;
	case 3:
	   hesk_iDatabase();
	   break;
	case 4:
	   hesk_iFinish();
	   break;
	default:
	   hesk_iStart();
}


// ******* FUNCTIONS ******* //


function hesk_iFinish()
{
    global $hesk_settings;
    hesk_iHeader();
	?>

	<h3>Update database tables</h3>

	<br />
	<?php hesk_show_success('Congratulations, your HESK has been updated to ' . HESK_NEW_VERSION); ?>

    <?php
    if (defined('RECAPTCHA_V1'))
    {
        hesk_show_notice('reCaptcha V1 is no longer supported and has been disabled.<br /><br />Please enable a different anti-SPAM measure in HESK settings.');
    }
    ?>

    <h3>Things to do next:</h3>

    <ol>

    <li><span style="color:#ff0000">Delete the <b>/install</b> folder from your server!</span><br />&nbsp;</li>

    <li>Login to HESK administration panel and make sure everything works fine.<br /><br />

	<form action="<?php echo HESK_PATH . $hesk_settings['admin_dir']; ?>/admin_main.php" method="get">
	<input type="submit" value="Continue to admin panel &raquo;" class="orangebutton" onmouseover="hesk_btn(this,'orangebuttonover');" onmouseout="hesk_btn(this,'orangebutton');" /></p>
	</form>

    </li>

    </ol>

    <p>&nbsp;</p>

	<?php
    hesk_iFooter();
} // End hesk_iFinish()


function hesk_iUpdateTables()
{
	global $hesk_db_link, $hesk_settings, $hesklang;

    $update_all_next = 0;

    // Force update possible MySQL strict issues before attempting anything else
    if (in_array($hesk_settings['update_from'], array('2.6', '2.5', '2.4', '2.3', '2.2', '2.1', '2.0', '0.94.1', '0.94', '0.91-0.93.1', '0.90')))
    {
        hesk_dbQuery("ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` CHANGE `dt` `dt` timestamp NOT NULL DEFAULT '2000-01-01 00:00:00'");
    }

	// Updating version 0.90 to 0.91
	if ($hesk_settings['update_from'] == '0.90')
	{
		hesk_dbQuery("ALTER TABLE `hesk_users` ADD `notify` CHAR( 1 ) DEFAULT '1' NOT NULL");
        $update_all_next = 1;
	} // END version 0.90 to 0.91


	// Updating versions 0.91 through 0.93.1 to 0.94
	if ($update_all_next || $hesk_settings['update_from'] == '0.91-0.93.1')
	{
		hesk_dbQuery("CREATE TABLE `hesk_attachments` (
		  `att_id` mediumint(8) unsigned NOT NULL auto_increment,
		  `ticket_id` varchar(10) NOT NULL default '',
		  `saved_name` varchar(255) NOT NULL default '',
		  `real_name` varchar(255) NOT NULL default '',
		  `size` int(10) unsigned NOT NULL default '0',
		  PRIMARY KEY  (`att_id`),
		  KEY `ticket_id` (`ticket_id`)
		) ENGINE=MyISAM");

		hesk_dbQuery("CREATE TABLE `hesk_std_replies` (
		`id` smallint(5) unsigned NOT NULL auto_increment,
		`title` varchar(70) NOT NULL default '',
		`message` text NOT NULL,
		`reply_order` smallint(5) unsigned NOT NULL default '0',
		PRIMARY KEY  (`id`)
		) ENGINE=MyISAM");

		hesk_dbQuery("ALTER TABLE `hesk_categories`
		CHANGE `name` `name` varchar(60) NOT NULL default '',
		ADD `cat_order` smallint(5) unsigned NOT NULL default '0'");

		hesk_dbQuery("ALTER TABLE `hesk_replies`
		CHANGE `name` `name` varchar(50) NOT NULL default '',
		ADD `attachments` TEXT");

		hesk_dbQuery("ALTER TABLE `hesk_tickets`
		CHANGE `name` `name` varchar(50) NOT NULL default '',
		CHANGE `category` `category` SMALLINT(5) UNSIGNED NOT NULL DEFAULT '1',
		CHANGE `priority` `priority` enum('1','2','3') NOT NULL default '3',
		CHANGE `subject` `subject` varchar(70) NOT NULL default '',
		ADD `lastchange` datetime NOT NULL AFTER `dt`,
		CHANGE `status` `status` enum('0','1','2','3') default '1',
		ADD `lastreplier` enum('0','1') NOT NULL default '0',
		ADD `archive` enum('0','1') NOT NULL default '0',
		ADD `attachments` text,
		ADD `custom1` VARCHAR( 255 ) NOT NULL default '',
		ADD `custom2` VARCHAR( 255 ) NOT NULL default '',
		ADD `custom3` VARCHAR( 255 ) NOT NULL default '',
		ADD `custom4` VARCHAR( 255 ) NOT NULL default '',
		ADD `custom5` VARCHAR( 255 ) NOT NULL default '',
		ADD INDEX `archive` ( `archive` )");

		// Change status of closed tickets to the new "Resolved" status
		hesk_dbQuery("UPDATE `hesk_tickets` SET `status`='3' WHERE `status`='0'");

		// Populate lastchange
		hesk_dbQuery("UPDATE `hesk_tickets` SET `lastchange`=`dt`");

		// Update categories with order values
		$res = hesk_dbQuery("SELECT `id` FROM `hesk_categories`");
		$i = 10;
		while ($mycat=hesk_dbFetchAssoc($res))
		{
			hesk_dbQuery("UPDATE `hesk_categories` SET `cat_order`=$i WHERE `id`=" . intval($mycat['id']));
			$i += 10;
		}

        $update_all_next = 1;
	} // END versions 0.91 through 0.93.1 to 0.94


    // Updating version 0.94 to 0.94.1
    if ($hesk_settings['update_from'] == '0.94')
    {
		hesk_dbQuery("CREATE TABLE `hesk_attachments` (
		  `att_id` mediumint(8) unsigned NOT NULL auto_increment,
		  `ticket_id` varchar(10) NOT NULL default '',
		  `saved_name` varchar(255) NOT NULL default '',
		  `real_name` varchar(255) NOT NULL default '',
		  `size` int(10) unsigned NOT NULL default '0',
		  PRIMARY KEY  (`att_id`),
		  KEY `ticket_id` (`ticket_id`)
		) ENGINE=MyISAM");

		if ($hesk_settings['attachments']['use'])
		{
			/* Update attachments for tickets */
			$res = hesk_dbQuery("SELECT * FROM `hesk_tickets` WHERE `attachments` != '' ");
			while ($ticket = hesk_dbFetchAssoc($res))
			{
				$att=explode('#####',substr($ticket['attachments'], 0, -5));
				$myattachments = '';
				foreach ($att as $myatt)
				{
					$name = substr(strstr($myatt, $ticket['trackid']),16);
					$saved_name = strstr($myatt, $ticket['trackid']);
					$size = filesize($hesk_settings['server_path'].'/attachments/'.$saved_name);

					hesk_dbQuery("INSERT INTO `hesk_attachments` (`ticket_id`,`saved_name`,`real_name`,`size`) VALUES ('".hesk_dbEscape($ticket['trackid'])."', '".hesk_dbEscape($saved_name)."', '".hesk_dbEscape($name)."', '".intval($size)."')");
					$myattachments .= hesk_dbInsertID() . '#' . $name .',';
				}

				hesk_dbQuery("UPDATE `hesk_tickets` SET `attachments` = '".hesk_dbEscape($myattachments)."' WHERE `id` = ".intval($ticket['id']));
			}

			// Update attachments for replies
			$res = hesk_dbQuery("SELECT * FROM `hesk_replies` WHERE `attachments` != '' ");
			while ($ticket = hesk_dbFetchAssoc($res))
			{
				$res2 = hesk_dbQuery("SELECT `trackid` FROM `hesk_tickets` WHERE `id` = '".intval($ticket['replyto'])."' LIMIT 1");
				$trackingID = hesk_dbResult($res2,0,0);

				$att=explode('#####',substr($ticket['attachments'], 0, -5));
				$myattachments = '';
				foreach ($att as $myatt)
                {
					$name = substr(strstr($myatt, $trackingID),16);
					$saved_name = strstr($myatt, $trackingID);
					$size = filesize($hesk_settings['server_path'].'/attachments/'.$saved_name);

					hesk_dbQuery("INSERT INTO `hesk_attachments` (`ticket_id`,`saved_name`,`real_name`,`size`) VALUES ('".hesk_dbEscape($trackingID)."', '".hesk_dbEscape($saved_name)."', '".hesk_dbEscape($name)."', '".intval($size)."')");
					$myattachments .= hesk_dbInsertID() . '#' . $name .',';
				}

				hesk_dbQuery("UPDATE `hesk_replies` SET `attachments` = '".hesk_dbEscape($myattachments)."' WHERE `id` = ".intval($ticket['id']));
			}
		}  // END if attachments use

        $update_all_next = 1;
    } // END version 0.94 to 0.94.1


	// Updating version 0.94.1 to 2.0
	if ($update_all_next || $hesk_settings['update_from'] == '0.94.1')
	{
		hesk_dbQuery("CREATE TABLE `hesk_kb_articles` (
		  `id` smallint(5) unsigned NOT NULL auto_increment,
		  `catid` smallint(5) unsigned NOT NULL default '0',
		  `dt` timestamp NOT NULL default CURRENT_TIMESTAMP,
		  `author` smallint(5) unsigned NOT NULL default '0',
		  `subject` varchar(255) NOT NULL default '',
		  `content` text NOT NULL,
		  `rating` float NOT NULL default '0',
		  `votes` mediumint(8) unsigned NOT NULL default '0',
		  `views` mediumint(8) unsigned NOT NULL default '0',
		  `type` enum('0','1','2') NOT NULL default '0',
		  `html` enum('0','1') NOT NULL default '0',
		  `art_order` smallint(5) unsigned NOT NULL default '0',
		  `history` text NOT NULL,
		  PRIMARY KEY  (`id`),
		  KEY `catid` (`catid`),
		  KEY `type` (`type`),
		  FULLTEXT KEY `subject` (`subject`,`content`)
		) ENGINE=MyISAM");

		hesk_dbQuery("CREATE TABLE `hesk_kb_categories` (
		  `id` smallint(5) unsigned NOT NULL auto_increment,
		  `name` varchar(255) NOT NULL default '',
		  `parent` smallint(5) unsigned NOT NULL default '0',
		  `articles` smallint(5) unsigned NOT NULL default '0',
		  `cat_order` smallint(5) unsigned NOT NULL default '0',
		  `type` enum('0','1') NOT NULL default '0',
		  PRIMARY KEY  (`id`),
		  KEY `type` (`type`)
		) ENGINE=MyISAM");

		hesk_dbQuery("INSERT INTO `hesk_kb_categories` VALUES (1, 'Knowledgebase', 0, 0, 10, '0')");

		hesk_dbQuery("CREATE TABLE `hesk_notes` (
		  `id` mediumint(8) unsigned NOT NULL auto_increment,
		  `ticket` mediumint(8) unsigned NOT NULL default '0',
		  `who` smallint(5) unsigned NOT NULL default '0',
		  `dt` datetime NOT NULL,
		  `message` text NOT NULL,
		  PRIMARY KEY  (`id`),
		  KEY `ticketid` (`ticket`)
		) ENGINE=MyISAM");

	    $sql = array();
		$sql[] = "ALTER TABLE `hesk_replies` ADD `staffid` SMALLINT UNSIGNED NOT NULL DEFAULT '0'";
		$sql[] = "ALTER TABLE `hesk_replies` ADD `rating` ENUM( '1', '5' ) default NULL";

		$sql[] = "ALTER TABLE `hesk_tickets` ADD INDEX `categories` ( `category` )";
		$sql[] = "ALTER TABLE `hesk_tickets` ADD INDEX `statuses` ( `status` ) ";
		$sql[] = "ALTER TABLE `hesk_tickets` CHANGE `custom1` `custom1` text NOT NULL";
		$sql[] = "ALTER TABLE `hesk_tickets` CHANGE `custom2` `custom2` text NOT NULL";
		$sql[] = "ALTER TABLE `hesk_tickets` CHANGE `custom3` `custom3` text NOT NULL";
		$sql[] = "ALTER TABLE `hesk_tickets` CHANGE `custom4` `custom4` text NOT NULL";
		$sql[] = "ALTER TABLE `hesk_tickets` CHANGE `custom5` `custom5` text NOT NULL";
		$sql[] = "ALTER TABLE `hesk_tickets` ADD `custom6` text NOT NULL";
		$sql[] = "ALTER TABLE `hesk_tickets` ADD `custom7` text NOT NULL";
		$sql[] = "ALTER TABLE `hesk_tickets` ADD `custom8` text NOT NULL";
		$sql[] = "ALTER TABLE `hesk_tickets` ADD `custom9` text NOT NULL";
		$sql[] = "ALTER TABLE `hesk_tickets` ADD `custom10` text NOT NULL";

		$sql[] = "ALTER TABLE `hesk_users` CHANGE `pass` `pass` CHAR( 40 ) NOT NULL";
		$sql[] = "ALTER TABLE `hesk_users` CHANGE `isadmin` `isadmin` ENUM( '0', '1' ) NOT NULL DEFAULT '0'";
		$sql[] = "ALTER TABLE `hesk_users` CHANGE `notify` `notify` ENUM( '0', '1' ) NOT NULL DEFAULT '1'";
		$sql[] = "ALTER TABLE `hesk_users` ADD `heskprivileges` VARCHAR( 255 ) NOT NULL";
		$sql[] = "ALTER TABLE `hesk_users` ADD `ratingneg` mediumint(8) unsigned NOT NULL default '0'";
		$sql[] = "ALTER TABLE `hesk_users` ADD `ratingpos` mediumint(8) unsigned NOT NULL default '0'";
		$sql[] = "ALTER TABLE `hesk_users` ADD `rating` float NOT NULL default '0'";
		$sql[] = "ALTER TABLE `hesk_users` ADD `replies` mediumint(8) unsigned NOT NULL default '0'";

		$sql[] = "ALTER TABLE `hesk_std_replies` CHANGE `title` `title` VARCHAR( 100 ) NOT NULL";

		foreach ($sql as $s)
	    {
			hesk_dbQuery($s);
	    }

	    // Update passwords to the new type and hesk privileges for non-admins */
		$res = hesk_dbQuery('SELECT `id`,`pass`,`isadmin` FROM `hesk_users` ORDER BY `id` ASC');

	    $sql = array();
		while ($row=hesk_dbFetchAssoc($res))
		{
			$new_pass = hesk_Pass2Hash($row['pass']);
	        $s = "UPDATE `hesk_users` SET `pass`='".hesk_dbEscape($new_pass)."' ";
	        if ($row['isadmin'] == 0)
	        {
	        	$s .= ", `heskprivileges`='can_view_tickets,can_reply_tickets,can_change_cat,' ";
	        }
	        $s.= "WHERE `id`=".intval($row['id']);
	        $sql[] = $s;
		}

		foreach ($sql as $s)
	    {
			hesk_dbQuery($s);
	    }

        $update_all_next = 1;
    } // END version 0.94.1 to 2.0


	// Updating version 2.0 to 2.1
	if ($update_all_next || $hesk_settings['update_from'] == '2.0')
	{
		hesk_dbQuery("CREATE TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_attachments` (
		  `att_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
		  `saved_name` varchar(255) NOT NULL DEFAULT '',
		  `real_name` varchar(255) NOT NULL DEFAULT '',
		  `size` int(10) unsigned NOT NULL DEFAULT '0',
		  PRIMARY KEY (`att_id`)
		) ENGINE=MyISAM");

		$sql = array();
    	$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_articles` ADD `attachments` TEXT NOT NULL";

    	$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` ADD `custom11` text NOT NULL";
    	$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` ADD `custom12` text NOT NULL";
    	$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` ADD `custom13` text NOT NULL";
    	$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` ADD `custom14` text NOT NULL";
    	$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` ADD `custom15` text NOT NULL";
    	$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` ADD `custom16` text NOT NULL";
    	$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` ADD `custom17` text NOT NULL";
    	$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` ADD `custom18` text NOT NULL";
    	$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` ADD `custom19` text NOT NULL";
    	$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` ADD `custom20` text NOT NULL";

    	$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."users` ADD `afterreply` ENUM( '0', '1', '2' ) NOT NULL DEFAULT '0' AFTER `categories`";

		foreach ($sql as $s)
	    {
			hesk_dbQuery($s);
	    }

        $update_all_next = 1;
	} // END version 2.0 to 2.1


	// Updating version 2.1 to 2.2
	if ($update_all_next || $hesk_settings['update_from'] == '2.1')
	{
		hesk_dbQuery("
		CREATE TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."mail` (
		  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		  `from` smallint(5) unsigned NOT NULL,
		  `to` smallint(5) unsigned NOT NULL,
		  `subject` varchar(255) NOT NULL,
		  `message` text NOT NULL,
		  `dt` datetime NOT NULL,
		  `read` enum('0','1') NOT NULL DEFAULT '0',
		  `deletedby` smallint(5) unsigned NOT NULL DEFAULT '0',
		  PRIMARY KEY (`id`),
		  KEY `recipients` (`from`,`to`)
		) ENGINE=MyISAM
		");

		$sql = array();

    	$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` ADD `replierid` SMALLINT UNSIGNED NULL AFTER `lastreplier`";
    	$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` ADD `owner` SMALLINT UNSIGNED NOT NULL DEFAULT '0' AFTER `status`";
    	$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` ADD `locked` ENUM( '0', '1' ) NOT NULL DEFAULT '0' AFTER `archive`";
    	$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` ADD `history` TEXT NOT NULL AFTER `attachments`";

    	$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."users` CHANGE `notify` `notify_new_unassigned` ENUM( '0', '1' ) NOT NULL DEFAULT '1'";
    	$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."users` ADD `notify_new_my` ENUM( '0', '1' ) NOT NULL DEFAULT '1' AFTER `notify_new_unassigned`";
    	$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."users` ADD `notify_reply_unassigned` ENUM( '0', '1' ) NOT NULL DEFAULT '1' AFTER `notify_new_my`";
    	$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."users` ADD `notify_reply_my` ENUM( '0', '1' ) NOT NULL DEFAULT '1' AFTER `notify_reply_unassigned`";
    	$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."users` ADD `notify_assigned` ENUM( '0', '1' ) NOT NULL DEFAULT '1' AFTER `notify_reply_my`";
    	$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."users` ADD `notify_pm` ENUM( '0', '1' ) NOT NULL DEFAULT '1' AFTER `notify_assigned`";

        $sql[] = "UPDATE  `".hesk_dbEscape($hesk_settings['db_pfix'])."users` SET `categories` = TRIM(TRAILING ',' FROM `categories`)";
        $sql[] = "UPDATE  `".hesk_dbEscape($hesk_settings['db_pfix'])."users` SET `heskprivileges` = TRIM(TRAILING ',' FROM `heskprivileges`)";

		foreach ($sql as $s)
	    {
			hesk_dbQuery($s);
	    }

		// Update privileges - anyone can assign ticket to himself/herself by default
		hesk_dbQuery("UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."users` SET `heskprivileges`=CONCAT(`heskprivileges`,',can_assign_self') WHERE `isadmin`!='1' ");

        $update_all_next = 1;
	} // END version 2.1 to 2.2


	// Updating version 2.2 to 2.3
	if ($update_all_next || $hesk_settings['update_from'] == '2.2')
	{
    	// Logins table
		hesk_dbQuery("
		CREATE TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."logins` (
		  `ip` varchar(46) NOT NULL,
		  `number` tinyint(3) unsigned NOT NULL DEFAULT '1',
		  `last_attempt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		  UNIQUE KEY `ip` (`ip`)
		) ENGINE=MyISAM
		");

        // Online table
		hesk_dbQuery("
		CREATE TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."online` (
		  `user_id` smallint(5) unsigned NOT NULL,
		  `dt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		  `tmp` int(11) unsigned NOT NULL DEFAULT '0',
		  UNIQUE KEY `user_id` (`user_id`),
		  KEY `dt` (`dt`)
		) ENGINE=MyISAM
		");

		$sql = array();

    	$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` CHANGE `trackid` `trackid` VARCHAR( 13 ) NOT NULL";
    	$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` CHANGE `priority` `priority` ENUM( '0', '1', '2', '3' ) NOT NULL DEFAULT '3'";
    	$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` CHANGE `status` `status` ENUM('0','1','2','3','4','5') NOT NULL DEFAULT '0'";
    	$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` CHANGE `ip` `ip` VARCHAR( 46 ) NOT NULL DEFAULT ''";
    	$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` CHANGE `lastchange` `lastchange` TIMESTAMP on update CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ";
    	$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` CHANGE `email` `email` VARCHAR(255) NOT NULL";
    	$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` ADD INDEX (`owner`) ";

    	$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."users` CHANGE `heskprivileges` `heskprivileges` TEXT NOT NULL";
    	$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."users` ADD `autoassign` ENUM('0','1') NOT NULL DEFAULT '1' AFTER `notify_pm`";
    	$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."users` ADD `default_list` VARCHAR( 255) NOT NULL DEFAULT '' AFTER `notify_pm`";
    	$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."users` ADD INDEX (`autoassign`) ";

    	$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."attachments` CHANGE `ticket_id` `ticket_id` VARCHAR(13) NOT NULL DEFAULT ''";

    	$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."replies` CHANGE `replyto` `replyto` MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0'";

		foreach ($sql as $s)
	    {
			hesk_dbQuery($s);
	    }

        // Update staff with new permissions (allowed by default)
		$res = hesk_dbQuery("SELECT `id`,`heskprivileges` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."users` WHERE `isadmin` != '1' ");
		while ($row=hesk_dbFetchAssoc($res))
		{
			// Not admin, is user allowed to view tickets?
			if (strpos($row['heskprivileges'], 'can_view_tickets') !== false)
			{
				hesk_dbQuery("UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."users` SET `heskprivileges`=CONCAT(`heskprivileges`,',can_view_unassigned,can_view_online') WHERE `id`=".intval($row['id']));
			}
            else
            {
				hesk_dbQuery("UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."users` SET `heskprivileges`=CONCAT(`heskprivileges`,',can_view_online') WHERE `id`=".intval($row['id']));
            }
		}

        $update_all_next = 1;
	} // END version 2.2 to 2.3


	// Updating version 2.3 to 2.4
	if ($update_all_next || $hesk_settings['update_from'] == '2.3')
	{
    	// Email loops table
		hesk_dbQuery("
		CREATE TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."pipe_loops` (
		  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
		  `hits` smallint(1) unsigned NOT NULL DEFAULT '0',
		  `message_hash` char(32) COLLATE utf8_unicode_ci NOT NULL,
		  `dt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
		  KEY `email` (`email`,`hits`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
		");

		$sql = array();

		$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."attachments` CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci";
		$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."categories` CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci";
		$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_articles` CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci";
		$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_attachments` CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci";
		$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_categories` CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci";
		$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."logins` CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci";
		$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."mail` CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci";
		$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."notes` CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci";
		$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."online` CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci";
		$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."replies` CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci";
		$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."std_replies` CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci";
		$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci";
		$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."users` CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci";

		$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` ADD `language` VARCHAR(50) NULL DEFAULT NULL AFTER `ip`";
		$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` ADD `merged` MEDIUMTEXT NOT NULL AFTER `attachments`";
		$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` ADD `time_worked` TIME NOT NULL DEFAULT '00:00:00' AFTER `owner`";
		$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` CHANGE `status` `status` ENUM( '0', '1', '2', '3', '4', '5' ) NOT NULL DEFAULT '0'";

		$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."users` ADD `language` VARCHAR(50) NULL DEFAULT NULL AFTER `signature`";
		$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."users` ADD `notify_note` ENUM( '0', '1' ) NOT NULL DEFAULT '1' AFTER `notify_pm`";
		$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."users` ADD `autostart` ENUM( '0', '1' ) NOT NULL DEFAULT '1' AFTER `afterreply`";

		$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."categories` ADD `autoassign` ENUM( '0', '1' ) NOT NULL DEFAULT '1', ADD `type` ENUM( '0', '1' ) NOT NULL DEFAULT '0', ADD INDEX ( `type` )";

		$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_articles` ADD `keywords` MEDIUMTEXT NOT NULL AFTER `content`";
		$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_articles` ADD `sticky` ENUM( '0', '1' ) NOT NULL DEFAULT '0' AFTER `html` , ADD INDEX ( `sticky` )";
		$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_articles` DROP INDEX `subject` , ADD FULLTEXT `subject` (`subject` , `content` , `keywords`)";

		$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."replies` ADD `read` ENUM( '0', '1' ) NOT NULL DEFAULT '1'";
		$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."replies` CHANGE `read` `read` ENUM( '0', '1' ) NOT NULL DEFAULT '0'";

		foreach ($sql as $s)
	    {
			hesk_dbQuery($s);
	    }

        // Update staff with new permissions (allowed by default)
		$res = hesk_dbQuery("SELECT `id`,`heskprivileges` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."users` WHERE `isadmin` != '1' ");
		while ($row=hesk_dbFetchAssoc($res))
		{
			// Not admin, is user allowed to view tickets?
			if (strpos($row['heskprivileges'], 'can_edit_tickets') !== false)
			{
				hesk_dbQuery("UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."users` SET `heskprivileges`=CONCAT(`heskprivileges`,',can_merge_tickets') WHERE `id`=".intval($row['id']));
			}
		}

        $update_all_next = 1;
	} // END version 2.3 to 2.4


    // Upgrade version 2.4.x to 2.5.0
	if ($update_all_next || $hesk_settings['update_from'] == '2.4')
	{
		$sql = array();

		// Make sure the 2.4 to 2.4.1 change is made
		$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."pipe_loops` CHANGE `hits` `hits` SMALLINT( 1 ) UNSIGNED NOT NULL DEFAULT '0' ";

		// 2.4.2 to 2.5.0 specific changes
		$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_categories` CHANGE `articles` `articles` SMALLINT( 5 ) UNSIGNED NOT NULL DEFAULT '0'";
		$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_categories` ADD `articles_private` SMALLINT UNSIGNED NOT NULL DEFAULT '0' AFTER `articles` , ADD `articles_draft` SMALLINT UNSIGNED NOT NULL DEFAULT '0' AFTER `articles_private`";
		$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_categories` ADD INDEX ( `parent` )";

		$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."mail` DROP INDEX `recipients`";
		$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."mail` ADD INDEX ( `to`, `read`, `deletedby` )";
		$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."mail` ADD INDEX ( `from` )";

		$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."replies` CHANGE `rating` `rating` ENUM( '0', '1', '5' ) DEFAULT '0' ";
		$sql[] = "UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."replies` SET `rating` = '0' WHERE `rating` IS NULL OR `rating` = '' ";

		foreach ($sql as $s)
	    {
			hesk_dbQuery($s);
	    }

        // Update knowledgebase category article counts to reflect new fields
		$update_these = array();

		// Get a count of all articles grouped by category and type
		$res = hesk_dbQuery('SELECT `catid`, `type`, COUNT(*) AS `num` FROM `'.hesk_dbEscape($hesk_settings['db_pfix']).'kb_articles` GROUP BY `catid`, `type`');
		while ( $row = hesk_dbFetchAssoc($res) )
		{
	    	switch ($row['type'])
	        {
	        	case 0:
	            	$update_these[$row['catid']]['articles'] = $row['num'];
	                break;
	        	case 1:
	            	$update_these[$row['catid']]['articles_private'] = $row['num'];
	                break;
	        	default:
	            	$update_these[$row['catid']]['articles_draft'] = $row['num'];
	        }
		}

	    // Set all article counts to 0
		hesk_dbQuery("UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_categories` SET `articles`=0, `articles_private`=0, `articles_draft`=0");

	    // Now update categories that have articles with correct values
	    foreach ($update_these as $catid => $value)
	    {
	    	$value['articles'] = isset($value['articles']) ? $value['articles'] : 0;
	    	$value['articles_private'] = isset($value['articles_private']) ? $value['articles_private'] : 0;
	    	$value['articles_draft'] = isset($value['articles_draft']) ? $value['articles_draft'] : 0;
			hesk_dbQuery("UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_categories` SET `articles`={$value['articles']}, `articles_private`={$value['articles_private']}, `articles_draft`={$value['articles_draft']} WHERE `id`='{$catid}'");

			// Force order articles
			$res = hesk_dbQuery("SELECT `id`, `sticky` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_articles` WHERE `catid`='{$catid}' ORDER BY `sticky` DESC, `art_order` ASC");

			$i = 10;
			$previous_sticky = 1;

			while ( $article = hesk_dbFetchAssoc($res) )
			{
				if ($previous_sticky != $article['sticky'])
				{
					$i = 10;
					$previous_sticky = $article['sticky'];
				}
				hesk_dbQuery("UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_articles` SET `art_order`=".intval($i)." WHERE `id`='".intval($article['id'])."'");
				$i += 10;
			}
	    }

		// Force order categories
		$res = hesk_dbQuery('SELECT `id`, `parent` FROM `'.hesk_dbEscape($hesk_settings['db_pfix']).'kb_categories` ORDER BY `parent` ASC, `cat_order` ASC');
		$i = 10;

		while ( $category = hesk_dbFetchAssoc($res) )
		{
			hesk_dbQuery("UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_categories` SET `cat_order`=".intval($i)." WHERE `id`='".intval($category['id'])."'");
			$i += 10;
		}

		$update_all_next = 1;
	} // END version 2.4.0 to 2.5.0

	// 2.5.1 no changes
    // 2.5.2 no changes
    // 2.5.3 no changes
	// 2.5.4 no changes
	// 2.5.5 no changes

	// Upgrade version 2.5.x to 2.6.0
	if ($update_all_next || $hesk_settings['update_from'] == '2.5')
	{
		// -> Banned emails
		hesk_dbQuery("
		CREATE TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."banned_emails` (
		  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
		  `email` varchar(255) NOT NULL,
		  `banned_by` smallint(5) unsigned NOT NULL,
		  `dt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
		  PRIMARY KEY (`id`),
		  KEY `email` (`email`)
		) ENGINE=MyISAM  DEFAULT CHARSET=utf8
		");

		// -> Banned IPs
		hesk_dbQuery("
		CREATE TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."banned_ips` (
		  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
		  `ip_from` int(10) unsigned NOT NULL DEFAULT '0',
		  `ip_to` int(10) unsigned NOT NULL DEFAULT '0',
		  `ip_display` varchar(100) NOT NULL,
		  `banned_by` smallint(5) unsigned NOT NULL,
		  `dt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
		  PRIMARY KEY (`id`)
		) ENGINE=MyISAM  DEFAULT CHARSET=utf8
		");

		// -> Reply drafts
		hesk_dbQuery("
		CREATE TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."reply_drafts` (
		  `owner` smallint(5) unsigned NOT NULL,
		  `ticket` mediumint(8) unsigned NOT NULL,
		  `message` mediumtext CHARACTER SET utf8 NOT NULL,
		  `dt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
		  KEY `owner` (`owner`),
		  KEY `ticket` (`ticket`)
		) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
		");

		// -> Reset password
		hesk_dbQuery("
		CREATE TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."reset_password` (
		  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
		  `user` smallint(5) unsigned NOT NULL,
		  `hash` char(40) NOT NULL,
		  `ip` varchar(45) NOT NULL,
		  `dt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
		  PRIMARY KEY (`id`),
		  KEY `user` (`user`)
		) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
		");

		// -> Service messages
		hesk_dbQuery("
		CREATE TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."service_messages` (
		  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
		  `dt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
		  `author` smallint(5) unsigned NOT NULL,
		  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
		  `message` mediumtext COLLATE utf8_unicode_ci NOT NULL,
		  `style` enum('0','1','2','3','4') COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
		  `type` enum('0','1') COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
		  `order` smallint(5) unsigned NOT NULL DEFAULT '0',
		  PRIMARY KEY (`id`),
		  KEY `type` (`type`)
		) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
		");

		// -> Ticket templates
		hesk_dbQuery("
		CREATE TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."ticket_templates` (
		  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
		  `title` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
		  `message` mediumtext COLLATE utf8_unicode_ci NOT NULL,
		  `tpl_order` smallint(5) unsigned NOT NULL DEFAULT '0',
		  PRIMARY KEY (`id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
		");

		// 2.6.0 table changes
		$sql = array();

		$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."mail` CHANGE `dt` `dt` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP";
		$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."notes` CHANGE `dt` `dt` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP";
		$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."replies` CHANGE `dt` `dt` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP";

		$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."attachments` ADD `type` ENUM( '0', '1' ) NOT NULL DEFAULT '0'";

		$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."categories` ADD `priority` ENUM( '0', '1', '2', '3' ) NOT NULL DEFAULT '3'";

		$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_categories` CHANGE `type` `type` ENUM('0','1') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '0'";

		$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."logins` CHANGE `ip` `ip` VARCHAR(45) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL";

		$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."notes` ADD `attachments` MEDIUMTEXT NOT NULL";

		$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."pipe_loops` CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci";

		$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."replies` CHANGE `rating` `rating` ENUM('1','5') CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL, ADD INDEX(`dt`),  ADD INDEX(`staffid`)";

		$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets`
					CHANGE `email` `email` VARCHAR( 1000 ) NOT NULL DEFAULT '',
					CHANGE `ip` `ip` VARCHAR(45) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
					ADD `firstreply` TIMESTAMP NULL DEFAULT NULL AFTER `lastchange`,
					ADD `closedat` TIMESTAMP NULL DEFAULT NULL AFTER `firstreply`,
					ADD `articles` VARCHAR(255) NULL DEFAULT NULL AFTER `closedat`,
					ADD `openedby` MEDIUMINT(8) DEFAULT '0' AFTER `status`,
					ADD `firstreplyby` SMALLINT(5) UNSIGNED NULL DEFAULT NULL AFTER `openedby`,
					ADD `closedby` MEDIUMINT(8) NULL DEFAULT NULL AFTER `firstreplyby`,
					ADD `replies` SMALLINT(5) UNSIGNED NOT NULL DEFAULT '0' AFTER `closedby`,
					ADD `staffreplies` SMALLINT( 5 ) UNSIGNED NOT NULL DEFAULT '0' AFTER `replies`,
					ADD INDEX ( `openedby` , `firstreplyby` , `closedby` ),
					ADD INDEX(`dt`)";

		$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."users`
					CHANGE `signature` `signature` VARCHAR( 1000 ) NOT NULL DEFAULT '',
					CHANGE `heskprivileges` `heskprivileges` VARCHAR(1000) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
					CHANGE `categories` `categories` VARCHAR(500) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
					ADD `notify_customer_new` ENUM('0','1') NOT NULL DEFAULT '1' AFTER `autostart`,
					ADD `notify_customer_reply` ENUM('0','1') NOT NULL DEFAULT '1'  AFTER `notify_customer_new`,
					ADD `show_suggested` ENUM('0','1') NOT NULL DEFAULT '1'  AFTER `notify_customer_reply`";

		foreach ($sql as $s)
	    {
			hesk_dbQuery($s);
	    }

		// ==> Populate new fields where available

		// Get list of valid ticket categories
		$cat = array();
		$res = hesk_dbQuery("SELECT `id` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."categories`");
		while ($row=hesk_dbFetchAssoc($res))
		{
			$cat[]=$row['id'];
		}


		// Update tickets
		$res = hesk_dbQuery("SELECT `id`, `category` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets`");
		while ($ticket = hesk_dbFetchAssoc($res) )
		{
			$sql = array();

			// Verify that the category is valid
			if ( ! in_array($ticket['category'], $cat) )
			{
				$sql[] = " `category`=1 ";
			}


			// Update number of staff replies
			$res2 = hesk_dbQuery("SELECT COUNT(*) as `cnt`, (CASE WHEN `staffid` = 0 THEN 0 ELSE 1 END) AS `staffcnt` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."replies` WHERE `replyto`=".intval($ticket['id'])." GROUP BY `staffcnt`");

			$total			= 0;
			$staffreplies	= 0;

			while ( $row = hesk_dbFetchAssoc($res2) )
			{
				$total += $row['cnt'];
				$staffreplies += ($row['staffcnt'] ? $row['cnt'] : 0);
			}

			if ( $total > 0 )
			{
				$sql[] = " `replies`={$total}, `staffreplies`={$staffreplies} ";
			}

			// If we have staff replies, find the first one
			if ( $staffreplies > 0 )
			{
				$res2 = hesk_dbQuery("SELECT `dt`, `staffid` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."replies` WHERE `replyto`={$ticket['id']} AND `staffid`>0 ORDER BY `id` ASC LIMIT 1");

				if ( hesk_dbNumRows($res2) == 1)
				{
					$reply = hesk_dbFetchAssoc($res2);
					$sql[] = " `firstreply`='".hesk_dbEscape($reply['dt'])."', `firstreplyby`={$reply['staffid']} ";
				}
			}

			// Do we need to update the ticket?
			if ( count($sql) )
			{
				hesk_dbQuery("UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` SET " . implode(',', $sql) . ", `lastchange`=`lastchange` WHERE `id`={$ticket['id']}");
			}
		}

		$update_all_next = 1;
	} // END version 2.5.x to 2.6.0

	// 2.6.1 no changes

	// 2.6.2 change `closedby` type for all 2.6.x to be sure
	if ($hesk_settings['update_from'] == '2.6')
	{
		hesk_dbQuery("ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` CHANGE `closedby` `closedby` MEDIUMINT(8) NULL DEFAULT NULL");
	}

	// 2.6.3 no changes
	// 2.6.4 no changes
	// 2.6.5 no changes
	// 2.6.6 no changes
	// 2.6.7 no changes

	// Updating version 2.6 to 2.7
	if ($update_all_next || $hesk_settings['update_from'] == '2.6')
	{
        // Delete old export folder
        $export_dir = HESK_PATH.$hesk_settings['attach_dir'].'/export/';
        if (is_dir($export_dir))
        {
            $files = glob($export_dir.'*', GLOB_NOSORT);
            if (is_array($files))
            {
                array_walk($files, 'hesk_unlink_callable');
            }
            @rmdir($export_dir);
        }

        // Delete old __latest.txt file
        hesk_unlink(HESK_PATH.$hesk_settings['attach_dir'].'/__latest.txt');

        // Delete HTMLPurifier cache
        if (is_dir(HESK_PATH.'inc/htmlpurifier/standalone/HTMLPurifier/DefinitionCache/Serializer'))
        {
            hesk_rrmdir(HESK_PATH.'inc/htmlpurifier/standalone/HTMLPurifier/DefinitionCache/Serializer', true);
        }

		$sql = array();

		$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."users` ADD `autoreload` SMALLINT UNSIGNED NOT NULL DEFAULT '0' AFTER `autostart`";

		foreach ($sql as $s)
		{
			hesk_dbQuery($s);
		}

		// Add new custom field columns and make sure all are set to mediumtext
		$sql = array();
		$table = array();

		// Is the status column an enum type? (HESK < 2.7.0) If no, don't subtract 1 later on
		$res = hesk_dbQuery("SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '".hesk_dbEscape($hesk_settings['db_name'])."' AND TABLE_NAME = '".hesk_dbEscape($hesk_settings['db_pfix'])."tickets' AND COLUMN_NAME = 'status'");
		$adjust_status_column = false;
		if (hesk_dbResult($res) == 'enum') {
			$adjust_status_column = true;
		}
		// Change the type regardless
		$sql[] = "CHANGE `status` `status` TINYINT UNSIGNED NOT NULL DEFAULT '0'";

		$res = hesk_dbQuery("DESCRIBE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets`");
		while($row = hesk_dbFetchAssoc($res))
		{
			$table[$row['Field']] = $row['Type'];
		}

		for ($i=1; $i<=50; $i++)
		{
			if (isset($table['custom'.$i]))
			{
                if (strtolower($table['custom'.$i]) != 'mediumtext')
                {
		            $sql[] = 'CHANGE `custom'.$i.'` `custom'.$i.'` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL';
                }
			}
			else
			{
		    	$sql[] = 'ADD `custom'.$i.'` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL';
			}
		}

		hesk_dbQuery("ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` " . implode(',', $sql));

		// A tweak to fix converting enum to int
		if ($adjust_status_column) {
			hesk_dbQuery("UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` SET `status`=`status`-1, `lastchange`=`lastchange`");
		}

		// -> Custom fields
		hesk_dbQuery("
		CREATE TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."custom_fields` (
		  `id` tinyint(3) UNSIGNED NOT NULL,
		  `use` enum('0','1','2') NOT NULL DEFAULT '0',
		  `place` enum('0','1') NOT NULL DEFAULT '0',
		  `type` varchar(20) NOT NULL DEFAULT 'text',
		  `req` enum('0','1','2') NOT NULL DEFAULT '0',
		  `category` text,
		  `name` text,
		  `value` text,
		  `order` smallint(5) UNSIGNED NOT NULL DEFAULT '10',
		  PRIMARY KEY (`id`),
		  KEY `useType` (`use`,`type`)
		) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
		");

		// ---> Insert empty custom fields
		hesk_dbQuery("
		INSERT INTO `".hesk_dbEscape($hesk_settings['db_pfix'])."custom_fields` (`id`, `use`, `place`, `type`, `req`, `category`, `name`, `value`, `order`) VALUES
		(1, '0', '0', 'text', '0', NULL, '', NULL, 1000),
		(2, '0', '0', 'text', '0', NULL, '', NULL, 1000),
		(3, '0', '0', 'text', '0', NULL, '', NULL, 1000),
		(4, '0', '0', 'text', '0', NULL, '', NULL, 1000),
		(5, '0', '0', 'text', '0', NULL, '', NULL, 1000),
		(6, '0', '0', 'text', '0', NULL, '', NULL, 1000),
		(7, '0', '0', 'text', '0', NULL, '', NULL, 1000),
		(8, '0', '0', 'text', '0', NULL, '', NULL, 1000),
		(9, '0', '0', 'text', '0', NULL, '', NULL, 1000),
		(10, '0', '0', 'text', '0', NULL, '', NULL, 1000),
		(11, '0', '0', 'text', '0', NULL, '', NULL, 1000),
		(12, '0', '0', 'text', '0', NULL, '', NULL, 1000),
		(13, '0', '0', 'text', '0', NULL, '', NULL, 1000),
		(14, '0', '0', 'text', '0', NULL, '', NULL, 1000),
		(15, '0', '0', 'text', '0', NULL, '', NULL, 1000),
		(16, '0', '0', 'text', '0', NULL, '', NULL, 1000),
		(17, '0', '0', 'text', '0', NULL, '', NULL, 1000),
		(18, '0', '0', 'text', '0', NULL, '', NULL, 1000),
		(19, '0', '0', 'text', '0', NULL, '', NULL, 1000),
		(20, '0', '0', 'text', '0', NULL, '', NULL, 1000),
		(21, '0', '0', 'text', '0', NULL, '', NULL, 1000),
		(22, '0', '0', 'text', '0', NULL, '', NULL, 1000),
		(23, '0', '0', 'text', '0', NULL, '', NULL, 1000),
		(24, '0', '0', 'text', '0', NULL, '', NULL, 1000),
		(25, '0', '0', 'text', '0', NULL, '', NULL, 1000),
		(26, '0', '0', 'text', '0', NULL, '', NULL, 1000),
		(27, '0', '0', 'text', '0', NULL, '', NULL, 1000),
		(28, '0', '0', 'text', '0', NULL, '', NULL, 1000),
		(29, '0', '0', 'text', '0', NULL, '', NULL, 1000),
		(30, '0', '0', 'text', '0', NULL, '', NULL, 1000),
		(31, '0', '0', 'text', '0', NULL, '', NULL, 1000),
		(32, '0', '0', 'text', '0', NULL, '', NULL, 1000),
		(33, '0', '0', 'text', '0', NULL, '', NULL, 1000),
		(34, '0', '0', 'text', '0', NULL, '', NULL, 1000),
		(35, '0', '0', 'text', '0', NULL, '', NULL, 1000),
		(36, '0', '0', 'text', '0', NULL, '', NULL, 1000),
		(37, '0', '0', 'text', '0', NULL, '', NULL, 1000),
		(38, '0', '0', 'text', '0', NULL, '', NULL, 1000),
		(39, '0', '0', 'text', '0', NULL, '', NULL, 1000),
		(40, '0', '0', 'text', '0', NULL, '', NULL, 1000),
		(41, '0', '0', 'text', '0', NULL, '', NULL, 1000),
		(42, '0', '0', 'text', '0', NULL, '', NULL, 1000),
		(43, '0', '0', 'text', '0', NULL, '', NULL, 1000),
		(44, '0', '0', 'text', '0', NULL, '', NULL, 1000),
		(45, '0', '0', 'text', '0', NULL, '', NULL, 1000),
		(46, '0', '0', 'text', '0', NULL, '', NULL, 1000),
		(47, '0', '0', 'text', '0', NULL, '', NULL, 1000),
		(48, '0', '0', 'text', '0', NULL, '', NULL, 1000),
		(49, '0', '0', 'text', '0', NULL, '', NULL, 1000),
		(50, '0', '0', 'text', '0', NULL, '', NULL, 1000)
		");

		// ---> Update custom fields with current values
		if (isset($hesk_settings['custom_fields']))
		{
			foreach ($hesk_settings['custom_fields'] as $k => $v)
			{
				if ( ! $v['use'])
				{
			    	continue;
				}

				$cf = array();

				// ID
				$id = intval(str_replace('custom', '', $k));

				// Name
				$cf['names'][$hesk_settings['language']] = $v['name'];
				$cf['names'] = addslashes(json_encode($cf['names']));

				// Type and value
				$cf['type'] = $v['type'];
				switch ($cf['type'])
				{
					case 'textarea':
						$size = explode('#',$v['value']);
						$cf['rows'] = empty($size[0]) ? 5 : intval($size[0]);
						$cf['cols'] = empty($size[1]) ? 30 : intval($size[1]);
						$cf['value'] = array('rows' => $cf['rows'], 'cols' => $cf['cols']);
						break;

					case 'radio':
						$options = explode('#HESK#',$v['value']);
						$cf['value'] = array('radio_options' => $options);
						break;

					case 'select':
						$v['value'] = str_replace('{HESK_SELECT}', '', $v['value'], $num);
						$options = explode('#HESK#',$v['value']);
						$cf['value'] = array('show_select' => ($num ? 1 : 0), 'select_options' => $options);
						break;

					case 'checkbox':
						$options = explode('#HESK#',$v['value']);
						$cf['value'] = array('checkbox_options' => $options);
						break;

					default:
						$cf['type'] = 'text';
						$cf['max_length'] = intval($v['maxlen']);
						$cf['default_value'] = $v['value'];
						$cf['value'] = array('max_length' => $cf['max_length'], 'default_value' => $cf['default_value']);
				}

				$cf['value'] = addslashes(json_encode($cf['value']));

				// Update custom_fields table with this field settings
				hesk_dbQuery("UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."custom_fields` SET
				`use`      = '1',
				`place`    = '".($v['place'] ? '1' : '0')."',
				`type`     = '{$cf['type']}',
				`req`      = '".($v['req'] ? '1' : '0')."',
				`name`     = '".hesk_dbEscape($cf['names'])."',
				`value`    = ".(strlen($cf['value']) ? "'".hesk_dbEscape($cf['value'])."'" : 'NULL')."
				WHERE `id`={$id}");
			}
		}

		// -> Custom statuses
		hesk_dbQuery("
		CREATE TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."custom_statuses` (
		`id` tinyint(3) UNSIGNED NOT NULL,
		`name` text NOT NULL,
		`color` varchar(6) NOT NULL,
		`can_customers_change` enum('0','1') NOT NULL DEFAULT '1',
		`order` smallint(5) UNSIGNED NOT NULL DEFAULT '10',
		PRIMARY KEY (`id`)
		) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
		");

		// Update staff with new permissions (allowed by default)
		hesk_dbQuery("UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."users` SET `heskprivileges`=CONCAT(`heskprivileges`,',can_resolve,can_submit_any_cat') WHERE `isadmin` = '0' AND `heskprivileges` LIKE '%can_reply_tickets%' ");

		$update_all_next = 1;
	} // END version 2.6 to 2.7

	// Updating version 2.7 to 2.8
	if ($update_all_next || $hesk_settings['update_from'] == '2.7')
	{
    	// Modify tickets table
		hesk_dbQuery("ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` ADD `assignedby` MEDIUMINT NULL DEFAULT NULL AFTER `owner`, ADD INDEX (`assignedby`)");

		$update_all_next = 1;
    } // END version 2.7 to 2.8

    // Updating 2.8 to 2.8.2
    if ($update_all_next || $hesk_settings['update_from'] == '2.8')
    {
        // Modify service_messages table
        hesk_dbQuery("ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."service_messages` ADD `language` VARCHAR(50) NULL DEFAULT NULL AFTER `message`");

        $update_all_next = 1;
    } // END version 2.8 to 2.8.2

    // Updating 2.8.2 to 2.8.3
    if ($update_all_next || $hesk_settings['update_from'] == '2.8.2')
    {
        // Modify tickets table
        hesk_dbQuery("ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` CHANGE `name` `name` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '', CHANGE `subject` `subject` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT ''");

        $update_all_next = 1;
    } // END version 2.8.2 to 2.8.3

    // 2.8.4 no changes
    // 2.8.5 no changes
    // 3.0.0 no changes
    // 3.0.1 no changes
    // 3.0.2 no changes
    // 3.0.3 no changes
    // 3.1.0 no changes
    // 3.1.1 no changes

	// Insert the "HESK updated to latest version" mail for the administrator
	if ( file_exists(HESK_PATH.'hesk_license.php') )
	{
        hesk_dbQuery("INSERT INTO `".hesk_dbEscape($hesk_settings['db_pfix'])."mail` (`id`, `from`, `to`, `subject`, `message`, `dt`, `read`, `deletedby`) VALUES (NULL, 9999, 1, 'HESK updated to version ".HESK_NEW_VERSION."', '".hesk_dbEscape("</p><div style=\"text-align:justify; padding-left: 10px; padding-right: 10px;\">\r\n\r\n<p>&nbsp;<br /><b>Congratulations, your HESK has been successfully updated.</b></p>\r\n\r\n<p><b>Before you go, let me invite you to:</b><br />&nbsp;</p>\r\n\r\n<hr />\r\n#1: help us improve\r\n<hr />\r\n<p>You can suggest what features should be added to HESK by posting them <a href=\"https://hesk.uservoice.com/forums/69851-general\" target=\"_blank\">here</a>.</p>\r\n\r\n&nbsp;\r\n\r\n<hr />\r\n#2: stay updated\r\n<hr />\r\n<p>HESK regularly receives improvements and bug fixes, make sure you know about them!</p>\r\n<ul>\r\n<li>for fast notifications, <a href=\"https://twitter.com/HESKdotCOM\">follow HESK on <b>Twitter</b></a></li>\r\n<li>for email notifications, subscribe to our low-volume zero-spam <a href=\"https://www.hesk.com/newsletter.php\">newsletter</a></li>\r\n</ul>\r\n\r\n<p>&nbsp;</p>\r\n\r\n<p>Best regards,</p>\r\n\r\n<p>Klemen Stirn<br />\r\nAuthor and owner</p>\r\n\r\n</div><p>")."', NOW(), '0', 9999)");
	}
	else
	{
        hesk_dbQuery("INSERT INTO `".hesk_dbEscape($hesk_settings['db_pfix'])."mail` (`id`, `from`, `to`, `subject`, `message`, `dt`, `read`, `deletedby`) VALUES (NULL, 9999, 1, 'HESK updated to version ".HESK_NEW_VERSION."', '".hesk_dbEscape("</p><div style=\"text-align:justify; padding-left: 10px; padding-right: 10px;\">\r\n\r\n<p>&nbsp;<br /><b>Congratulations, your HESK has been successfully updated.</b></p>\r\n\r\n<p><b>Before you go, let me invite you to:</b><br />&nbsp;</p>\r\n\r\n<hr />\r\n#1: help us improve\r\n<hr />\r\n<p>You can suggest what features should be added to HESK by posting them <a href=\"https://hesk.uservoice.com/forums/69851-general\" target=\"_blank\">here</a>.</p>\r\n\r\n&nbsp;\r\n\r\n<hr />\r\n#2: stay updated\r\n<hr />\r\n<p>HESK regularly receives improvements and bug fixes, make sure you know about them!</p>\r\n<ul>\r\n<li>for fast notifications, <a href=\"https://twitter.com/HESKdotCOM\">follow HESK on <b>Twitter</b></a></li>\r\n<li>for email notifications, subscribe to our low-volume zero-spam <a href=\"https://www.hesk.com/newsletter.php\">newsletter</a></li>\r\n</ul>\r\n\r\n&nbsp;\r\n\r\n<hr />\r\n#3: look professional\r\n<hr />\r\n<p>To look more professional and not advertise the tools you use, <a href=\"https://www.hesk.com/buy.php\">remove &quot;Powered by&quot; links</a> from your help desk.</p>\r\n\r\n<p>&nbsp;</p>\r\n\r\n<p>Best regards,</p>\r\n\r\n<p>Klemen Stirn<br />\r\nAuthor and owner</p>\r\n\r\n</div><p>")."', NOW(), '0', 9999)");
	}

	return true;

} // End hesk_iUpdateTables()


function hesk_iSaveSettings()
{
    global $hesk_settings, $hesklang;

    // Get default settings
	$hesk_default = hesk_defaultSettings();

    // Set a new version number
    $hesk_settings['hesk_version'] = HESK_NEW_VERSION;

	// Correct typos in variable names before 2.4
	if ( isset($hesk_settings['stmp_host_port']) )
	{
		$hesk_settings['smtp_host_port'] = isset($hesk_settings['stmp_host_port']) ? $hesk_settings['stmp_host_port'] : 25;
		$hesk_settings['smtp_timeout']   = isset($hesk_settings['stmp_timeout']) ? $hesk_settings['stmp_timeout'] : 10;
		$hesk_settings['smtp_user']      = isset($hesk_settings['stmp_user']) ? $hesk_settings['stmp_user'] : '';
		$hesk_settings['smtp_password']  = isset($hesk_settings['stmp_password']) ? $hesk_settings['stmp_password'] : '';
	}

	// Assign all required values
    foreach ($hesk_default as $k => $v)
    {
    	if (is_array($v))
        {
        	// Arrays will be processed separately
        	continue;
        }
    	if ( ! isset($hesk_settings[$k]))
        {
			$hesk_settings[$k] = $v;
        }
    }

	// Arrays need special care
    $hesk_settings['attachments'] = isset($hesk_settings['attachments']) ? $hesk_settings['attachments'] : $hesk_default['attachments'];
    $hesk_settings['email_providers'] = isset($hesk_settings['email_providers']) ? array_unique(array_merge($hesk_settings['email_providers'], $hesk_default['email_providers'])) : $hesk_default['email_providers'];
    $hesk_settings['notify_spam_tags'] = isset($hesk_settings['notify_spam_tags']) ? $hesk_settings['notify_spam_tags'] : $hesk_default['notify_spam_tags'];
    $hesk_settings['ticket_list'] = isset($hesk_settings['ticket_list']) ? $hesk_settings['ticket_list'] : $hesk_default['ticket_list'];

    // Attachments max size must be multiplied by 1024 since version 2.4
    if ($hesk_settings['attachments']['max_size'] < 102400)
    {
		$hesk_settings['attachments']['max_size'] = $hesk_settings['attachments']['max_size'] * 1024;
    }

    // Encode and escape characters
    $set = $hesk_settings;
    foreach ($hesk_settings as $k=> $v)
    {
    	if (is_array($v) || is_object($v))
        {
        	continue;
        }
    	$set[$k] = addslashes($v);
    }
    $set['debug_mode'] = 0;

    $set['email_providers'] = count($hesk_settings['email_providers']) ?  "'" . implode("','", $hesk_settings['email_providers']) . "'" : '';
    $set['notify_spam_tags'] = count($hesk_settings['notify_spam_tags']) ?  "'" . implode("','", $hesk_settings['notify_spam_tags']) . "'" : '';
    $set['ip_whois'] = str_replace('http://whois.domaintools.com', 'https://whois.domaintools.com', $set['ip_whois']);

    // Check if PHP version is 5.2.3+ and MySQL is 5.0.7+
	$set['db_vrsn'] = (version_compare(PHP_VERSION, '5.2.3') >= 0) ? 1 : 0;

    // reCaptcha v1 has been removed in 2.8, disable it
    if ($set['recaptcha_use'] == 1 && version_compare($hesk_settings['update_from'], '2.8', '<'))
    {
        $set['recaptcha_use'] = 0;
        define('RECAPTCHA_V1', true);
    }

	hesk_iSaveSettingsFile($set);

	return true;

} // End hesk_iSaveSettings()


function hesk_defaultSettings()
{
	$spam_question = hesk_generate_SPAM_question();

	$secimg_sum = '';
	for ($i=1;$i<=10;$i++)
	{
	    $secimg_sum .= substr('AEUYBDGHJLMNPQRSTVWXZ123456789', rand(0,29), 1);
	}

	// --> General settings
	$hesk_settings['site_title']='Website';
	$hesk_settings['site_url']='http://www.example.com';
	$hesk_settings['webmaster_mail']='support@example.com';
	$hesk_settings['noreply_mail']='noreply@example.com';
	$hesk_settings['noreply_name']='Help Desk';
    $hesk_settings['site_theme']='hesk3';

	// --> Language settings
	$hesk_settings['can_sel_lang']=0;
	$hesk_settings['language']='English';
	$hesk_settings['languages']=array(
	'English' => array('folder'=>'en','hr'=>'------ Reply above this line ------'),
	);

	// --> Database settings
	$hesk_settings['db_host']='localhost';
	$hesk_settings['db_name']='hesk';
	$hesk_settings['db_user']='test';
	$hesk_settings['db_pass']='test';
	$hesk_settings['db_pfix']='hesk_';
	$hesk_settings['db_vrsn']=0;


	// ==> HELP DESK

	// --> Help desk settings
	$hesk_settings['hesk_title']='Help Desk';
	$hesk_settings['hesk_url']='http://www.example.com/helpdesk';
	$hesk_settings['admin_dir']='admin';
	$hesk_settings['attach_dir']='attachments';
	$hesk_settings['cache_dir']='cache';
	$hesk_settings['max_listings']=20;
	$hesk_settings['print_font_size']=12;
	$hesk_settings['autoclose']=0;
	$hesk_settings['max_open']=0;
	$hesk_settings['new_top']=0;
	$hesk_settings['reply_top']=0;
    $hesk_settings['hide_replies']=-1;
    $hesk_settings['limit_width']=800;

	// --> Features
	$hesk_settings['autologin']=1;
	$hesk_settings['autoassign']=1;
	$hesk_settings['require_email']=1;
	$hesk_settings['require_owner']=0;
	$hesk_settings['require_subject']=1;
	$hesk_settings['require_message']=1;
	$hesk_settings['custclose']=1;
	$hesk_settings['custopen']=1;
	$hesk_settings['rating']=1;
	$hesk_settings['cust_urgency']=1;
	$hesk_settings['sequential']=1;
	$hesk_settings['time_worked']=1;
	$hesk_settings['spam_notice']=1;
	$hesk_settings['list_users']=0;
	$hesk_settings['debug_mode']=0;
	$hesk_settings['short_link']=0;
	$hesk_settings['select_cat']=0;
	$hesk_settings['select_pri']=0;
	$hesk_settings['cat_show_select']=15;

	// --> SPAM Prevention
	$hesk_settings['secimg_use']=1;
	$hesk_settings['secimg_sum']=$secimg_sum;
	$hesk_settings['recaptcha_use']=0;
	$hesk_settings['recaptcha_public_key']='';
	$hesk_settings['recaptcha_private_key']='';
	$hesk_settings['question_use']=0;
	$hesk_settings['question_ask']=$spam_question[0];
	$hesk_settings['question_ans']=$spam_question[1];

	// --> Security
	$hesk_settings['attempt_limit']=6;
	$hesk_settings['attempt_banmin']=60;
	$hesk_settings['reset_pass']=1;
	$hesk_settings['email_view_ticket']=1;
	$hesk_settings['x_frame_opt']=1;
	$hesk_settings['force_ssl']=0;

	// --> Attachments
	$hesk_settings['attachments']=array (
	'use' =>  1,
	'max_number' => 2,
	'max_size' => 2097152,
	'allowed_types' => array('.gif','.jpg','.png','.zip','.rar','.csv','.doc','.docx','.xls','.xlsx','.txt','.pdf')
	);


	// ==> KNOWLEDGEBASE

	// --> Knowledgebase settings
	$hesk_settings['kb_enable']=1;
	$hesk_settings['kb_wysiwyg']=1;
	$hesk_settings['kb_search']=2;
	$hesk_settings['kb_search_limit']=10;
	$hesk_settings['kb_views']=0;
	$hesk_settings['kb_date']=0;
	$hesk_settings['kb_recommendanswers']=1;
	$hesk_settings['kb_rating']=1;
	$hesk_settings['kb_substrart']=200;
	$hesk_settings['kb_cols']=2;
	$hesk_settings['kb_numshow']=3;
	$hesk_settings['kb_popart']=6;
	$hesk_settings['kb_latest']=6;
	$hesk_settings['kb_index_popart']=6;
	$hesk_settings['kb_index_latest']=0;
	$hesk_settings['kb_related']=5;


	// ==> EMAIL

	// --> Email sending
	$hesk_settings['smtp']=0;
	$hesk_settings['smtp_host_name']='mail.example.com';
	$hesk_settings['smtp_host_port']=25;
	$hesk_settings['smtp_timeout']=10;
	$hesk_settings['smtp_ssl']=0;
	$hesk_settings['smtp_tls']=0;
	$hesk_settings['smtp_user']='';
	$hesk_settings['smtp_password']='';

	// --> Email piping
	$hesk_settings['email_piping']=0;

	// --> POP3 Fetching
	$hesk_settings['pop3']=0;
	$hesk_settings['pop3_job_wait']=15;
	$hesk_settings['pop3_host_name']='mail.example.com';
	$hesk_settings['pop3_host_port']=110;
	$hesk_settings['pop3_tls']=0;
	$hesk_settings['pop3_keep']=0;
	$hesk_settings['pop3_user']='';
	$hesk_settings['pop3_password']='';

	// --> IMAP Fetching
	$hesk_settings['imap']=0;
	$hesk_settings['imap_job_wait']=15;
	$hesk_settings['imap_host_name']='mail.example.com';
	$hesk_settings['imap_host_port']=993;
	$hesk_settings['imap_enc']='ssl';
	$hesk_settings['imap_keep']=0;
	$hesk_settings['imap_user']='';
	$hesk_settings['imap_password']='';

	// --> Email loops
	$hesk_settings['loop_hits']=6;
	$hesk_settings['loop_time']=300;

	// --> Detect email typos
	$hesk_settings['detect_typos']=1;
	$hesk_settings['email_providers']=array('aim.com','aol.co.uk','aol.com','att.net','bellsouth.net','blueyonder.co.uk','bt.com','btinternet.com','btopenworld.com','charter.net','comcast.net','cox.net','earthlink.net','email.com','facebook.com','fastmail.fm','free.fr','freeserve.co.uk','gmail.com','gmx.at','gmx.ch','gmx.com','gmx.de','gmx.fr','gmx.net','gmx.us','googlemail.com','hotmail.be','hotmail.co.uk','hotmail.com','hotmail.com.ar','hotmail.com.mx','hotmail.de','hotmail.es','hotmail.fr','hushmail.com','icloud.com','inbox.com','laposte.net','lavabit.com','list.ru','live.be','live.co.uk','live.com','live.com.ar','live.com.mx','live.de','live.fr','love.com','lycos.com','mac.com','mail.com','mail.ru','me.com','msn.com','nate.com','naver.com','neuf.fr','ntlworld.com','o2.co.uk','online.de','orange.fr','orange.net','outlook.com','pobox.com','prodigy.net.mx','qq.com','rambler.ru','rocketmail.com','safe-mail.net','sbcglobal.net','t-online.de','talktalk.co.uk','tiscali.co.uk','verizon.net','virgin.net','virginmedia.com','wanadoo.co.uk','wanadoo.fr','yahoo.co.id','yahoo.co.in','yahoo.co.jp','yahoo.co.kr','yahoo.co.uk','yahoo.com','yahoo.com.ar','yahoo.com.mx','yahoo.com.ph','yahoo.com.sg','yahoo.de','yahoo.fr','yandex.com','yandex.ru','ymail.com');

	// --> Notify customer when
	$hesk_settings['notify_new']=1;
	$hesk_settings['notify_skip_spam']=1;
	$hesk_settings['notify_spam_tags']=array('Spam?}','***SPAM***','[SPAM]','SPAM-LOW:','SPAM-MED:');
	$hesk_settings['notify_closed']=1;

	// --> Other
	$hesk_settings['strip_quoted']=1;
	$hesk_settings['eml_req_msg']=0;
	$hesk_settings['save_embedded']=1;
	$hesk_settings['multi_eml']=0;
	$hesk_settings['confirm_email']=0;
	$hesk_settings['open_only']=1;

	// ==> TICKET LIST

	$hesk_settings['ticket_list']=array('trackid','lastchange','name','subject','status','lastreplier');

	// --> Other
	$hesk_settings['submittedformat']=2;
	$hesk_settings['updatedformat']=2;


	// ==> MISC

	// --> Date & Time
	$hesk_settings['timezone']=date_default_timezone_get();
	$hesk_settings['timeformat']='Y-m-d H:i:s';
    $hesk_settings['time_display']=1;

	// --> Other
	$hesk_settings['ip_whois']='https://whois.domaintools.com/{IP}';
	$hesk_settings['maintenance_mode']=0;
	$hesk_settings['alink']=1;
	$hesk_settings['submit_notice']=0;
	$hesk_settings['online']=0;
	$hesk_settings['online_min']=10;
	$hesk_settings['check_updates']=1;

	return $hesk_settings;
} // END hesk_defaultSettings()


function hesk_iDetectVersion()
{
	global $hesk_settings, $hesklang;

    // Version 2.8.3 tables installed?
    $res = hesk_dbQuery("SELECT CHARACTER_MAXIMUM_LENGTH FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '".hesk_dbEscape($hesk_settings['db_pfix'])."tickets' AND table_schema = '".hesk_dbEscape($hesk_settings['db_name'])."' AND column_name = 'name' LIMIT 0, 1");
    $row = hesk_dbFetchRow($res);
    if ($row[0] == 255)
    {
        return '2.8.3';
    }

    // Version 2.8.2 tables installed?
    $res = hesk_dbQuery("SHOW TABLES FROM `".hesk_dbEscape($hesk_settings['db_name'])."` LIKE '".hesk_dbEscape($hesk_settings['db_pfix'])."service_messages'");
    if (hesk_dbNumRows($res))
    {
        $res = hesk_dbQuery("SHOW COLUMNS FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."service_messages` LIKE 'language'");
        if (hesk_dbNumRows($res))
        {
            return '2.8.2';
        }
    }

    // Version 2.8 tables installed?
    $res = hesk_dbQuery("SHOW COLUMNS FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` LIKE 'assignedby'");
    if (hesk_dbNumRows($res))
    {
        return '2.8';
    }

	// Get a list of tables from the database
	$tables = array();
	$res = hesk_dbQuery('SHOW TABLES FROM `'.hesk_dbEscape($hesk_settings['db_name']).'`');

	while ($row = hesk_dbFetchRow($res))
	{
		$tables[] = $row[0];
	}

	// Version 2.7 tables installed?
	if (
		in_array($hesk_settings['db_pfix'].'custom_fields', $tables) ||
        in_array($hesk_settings['db_pfix'].'custom_statuses', $tables)
		)
	{
		return '2.7';
	}

	// Version 2.6 tables installed?
	if (
		in_array($hesk_settings['db_pfix'].'banned_emails', $tables) ||
		in_array($hesk_settings['db_pfix'].'banned_ips', $tables) ||
		in_array($hesk_settings['db_pfix'].'reply_drafts', $tables) ||
		in_array($hesk_settings['db_pfix'].'reset_password', $tables) ||
		in_array($hesk_settings['db_pfix'].'service_messages', $tables) ||
		in_array($hesk_settings['db_pfix'].'ticket_templates', $tables)
		)
	{
		return '2.6';
	}

	// Version 2.4/2.5 tables installed?
	elseif (in_array($hesk_settings['db_pfix'].'pipe_loops', $tables))
	{
		// Version 2.4 didn't have articles_private in kb_categories
		$res = hesk_dbQuery("SELECT * FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_categories` WHERE `id`=1 LIMIT 1");
		$row = hesk_dbFetchAssoc($res);
		if (isset($row['articles_private']))
		{
			// This is one of the 2.5.x version
            // Database is 100% compatible, so let's be safe and return 2.5
            return '2.5';
		}
		else
		{
			return '2.4';
		}
	}

	// Version 2.3 tables installed?
	elseif (in_array($hesk_settings['db_pfix'].'online', $tables) || in_array($hesk_settings['db_pfix'].'logins', $tables))
	{
		return '2.3';
	}

	// Version 2.2 tables installed?
	elseif (in_array($hesk_settings['db_pfix'].'mail', $tables))
	{
		return '2.2';
	}

	// Version 2.1 tables installed?
	elseif (in_array($hesk_settings['db_pfix'].'kb_attachments', $tables))
	{
		return '2.1';
	}

	// Version 2.0 tables installed?
	elseif (in_array($hesk_settings['db_pfix'].'kb_articles', $tables))
	{
		return '2.0';
	}

	// Version 0.94.1 tables installed?
	elseif (in_array('hesk_attachments', $tables))
	{
		return '0.94.1';
	}

	// Version 0.94 tables installed?
	elseif (in_array('hesk_std_replies', $tables))
	{
		return '0.94';
	}

	// It's a version older than 0.94 or no tables found
	else
	{
		// If we don't have four basic tables this is not a valid HESK install
		if ( ! in_array('hesk_categories', $tables) || ! in_array('hesk_replies', $tables) || ! in_array('hesk_tickets', $tables) || ! in_array('hesk_users', $tables) )
		{
			hesk_iDatabase(3);
		}

		// Version 0.90 didn't have the notify column in users table
		$res = hesk_dbQuery("SELECT * FROM `hesk_users` WHERE `id`=1 LIMIT 1");
		$row = hesk_dbFetchAssoc($res);
		if (isset($row['notify']))
		{
			return '0.91-0.93.1';
		}
		else
		{
        	// Wow, we found someone using the very first HESK version :-)
			return '0.90';
		}
	}

} // END hesk_iDetectVersion()
?>

