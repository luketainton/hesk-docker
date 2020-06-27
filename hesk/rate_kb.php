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
define('HESK_PATH','./');

// Get all the required files and functions
require(HESK_PATH . 'hesk_settings.inc.php');
require(HESK_PATH . 'inc/common.inc.php');
require(HESK_PATH . 'inc/knowledgebase_functions.inc.php');
define('TEMPLATE_PATH', HESK_PATH . "theme/{$hesk_settings['site_theme']}/");
require(TEMPLATE_PATH . 'customer/util/rating.php');

// Is rating enabled?
if ( ! $hesk_settings['kb_rating'])
{
	die($hesklang['rdis']);
}

// Rating value
$rating = intval( hesk_GET('rating', 0) );

// Rating can only be 1 or 5
if ($rating != 1 && $rating != 5)
{
	die($hesklang['attempt']);
}

// Article ID
$artid = intval( hesk_GET('id', 0) ) or die($hesklang['attempt']);

// Check cookies for already rated, rate and set cookie if not already
$_COOKIE['hesk_kb_rate'] = hesk_COOKIE('hesk_kb_rate');

if (strpos($_COOKIE['hesk_kb_rate'],'a'.$artid.'%')===false)
{
    // Connect to database
    hesk_load_database_functions();
    hesk_dbConnect();

    // Update rating, make sure it's a public article in a public category
    hesk_dbQuery("UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_articles` AS `t1`
    	LEFT JOIN `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_categories` AS `t2` ON t1.`catid` = t2.`id`
    	SET `rating`=((`rating`*`votes`)+{$rating})/(`votes`+1), t1.`votes`=t1.`votes`+1
    	WHERE t1.`id`='{$artid}' AND t1.`type`='0' AND t2.`type`='0'
    	");
}

// Get article from DB, make sure that article and category are public
$result = hesk_dbQuery("SELECT t1.`rating`, t1.`votes`
            FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_articles` AS `t1`
            LEFT JOIN `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_categories` AS `t2` ON `t1`.`catid` = `t2`.`id`
            WHERE `t1`.`id` = '{$artid}' AND `t1`.`type` = '0' AND `t2`.`type` = '0'
          ");

$article = hesk_dbFetchAssoc($result) or die($hesklang['kb_art_id']);

hesk_setcookie('hesk_kb_rate', $_COOKIE['hesk_kb_rate'].'a'.$artid.'%', time()+2592000);
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header('Content-type: text/plain; charset=utf-8');

echo hesk3_get_customer_rating($article['rating']);
if ($hesk_settings['kb_views'])
{
    echo ' <span class="lightgrey">('.number_format($article['votes'], 0, null, $hesklang['sep_1000']).')</span>';
}

exit();
