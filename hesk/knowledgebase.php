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

/* Get all the required files and functions */
require(HESK_PATH . 'hesk_settings.inc.php');
// TODO Pull this from settings
define('TEMPLATE_PATH', HESK_PATH . "theme/{$hesk_settings['site_theme']}/");
require(HESK_PATH . 'inc/common.inc.php');

// Are we in maintenance mode?
hesk_check_maintenance();

// Is Knowledgebase enabled?
if (!$hesk_settings['kb_enable'])
{
    hesk_error($hesklang['kbdis']);
}

// Connect to database
hesk_load_database_functions();
hesk_dbConnect();

// Do we have any public articles at all?
$res = hesk_dbQuery("SELECT `t1`.`id` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_articles` AS `t1`
                    LEFT JOIN `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_categories` AS `t2` ON `t1`.`catid` = `t2`.`id`
                    WHERE `t1`.`type`='0' AND `t2`.`type`='0' LIMIT 1");

// If yes, load KB functions; if not, disable and hide the KB
if (hesk_dbNumRows($res) < 1)
{
    hesk_error($hesklang['noa']);
}

// Load KB functions
require(HESK_PATH . 'inc/knowledgebase_functions.inc.php');

/* Rating? */
if (isset($_GET['rating']))
{
	// Detect and block robots
    if (hesk_detect_bots())
    {
		?>
		<html>
		<head>
		<meta name="robots" content="noindex, nofollow">
		</head>
		<body>
		</body>
		</html>
		<?php
    }

	// Rating
	$rating = intval( hesk_GET('rating') );

	// Rating value may only be 1 or 5
	if ($rating != 1 && $rating != 5)
	{
		die($hesklang['attempt']);
	}

	// Article ID
    $artid = intval( hesk_GET('id', 0) ) or die($hesklang['kb_art_id']);

    // Check cookies for already rated, rate and set cookie if not already
    $_COOKIE['hesk_kb_rate'] = hesk_COOKIE('hesk_kb_rate');

    if (strpos($_COOKIE['hesk_kb_rate'],'a'.$artid.'%')===false)
    {
		// Update rating, make sure it's a public article in a public category
		hesk_dbQuery("UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_articles` AS `t1`
					LEFT JOIN `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_categories` AS `t2` ON t1.`catid` = t2.`id`
					SET `rating`=((`rating`*`votes`)+{$rating})/(`votes`+1), t1.`votes`=t1.`votes`+1
					WHERE t1.`id`='{$artid}' AND t1.`type`='0' AND t2.`type`='0'
					");
    }

    hesk_setcookie('hesk_kb_rate', $_COOKIE['hesk_kb_rate'].'a'.$artid.'%', time()+2592000);
    header('Location: knowledgebase.php?article='.$artid.'&rated=1');
    exit();
}

// Get list of public categories
$hesk_settings['public_kb_categories'] = hesk_kbCategoriesArray();

/* Any category ID set? */
$catid = intval( hesk_GET('category', 1) );
$artid = intval( hesk_GET('article', 0) );

if (isset($_GET['search']))
{
	$query = hesk_input( hesk_GET('search') );
}
else
{
	$query = 0;
}

$hesk_settings['kb_link'] = ($artid || $catid != 1 || $query) ? '<a href="knowledgebase.php" class="smaller">'.$hesklang['kb_text'].'</a>' : $hesklang['kb_text'];

if ($hesk_settings['kb_search'] && $query)
{
    hesk_kb_search($query);
}
elseif ($artid)
{
	// Get article from DB, make sure that article and category are public
	$result  = hesk_dbQuery("SELECT t1.*, t2.`name` AS `cat_name`
							FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_articles` AS `t1`
							LEFT JOIN `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_categories` AS `t2` ON `t1`.`catid` = `t2`.`id`
							WHERE `t1`.`id` = '{$artid}'
							AND `t1`.`type` = '0'
							AND `t2`.`type` = '0'
                            ");

    $article = hesk_dbFetchAssoc($result) or hesk_error($hesklang['kb_art_id']);
    $article['views_formatted'] = number_format($article['views'], 0, null, $hesklang['sep_1000']);
    $article['votes_formatted'] = number_format($article['votes'], 0, null, $hesklang['sep_1000']);
    if ($article['catid'] == 1)
    {
        $article['cat_name'] = $hesklang['kb_text'];
    }
    hesk_show_kb_article($artid);
}
else
{
	hesk_show_kb_category($catid);
}

exit();



function hesk_kb_search($query) {
	global $hesk_settings, $hesklang;

    define('HESK_NO_ROBOTS',1);

    $hesk_settings['tmp_title'] = $hesklang['sr'] . ': ' . hesk_mb_substr(hesk_htmlspecialchars(stripslashes($query)),0,20);

	$res = hesk_dbQuery('SELECT t1.`id`, t1.`subject`, LEFT(`t1`.`content`, '.max(200, $hesk_settings['kb_substrart'] * 2).') AS `content`, t1.`rating`, t1.`votes`, t1.`views` FROM `'.hesk_dbEscape($hesk_settings['db_pfix']).'kb_articles` AS t1
    					LEFT JOIN `'.hesk_dbEscape($hesk_settings['db_pfix'])."kb_categories` AS t2 ON t1.`catid` = t2.`id`
						WHERE t1.`type`='0' AND t2.`type`='0' AND  MATCH(`subject`,`content`,`keywords`) AGAINST ('".hesk_dbEscape($query)."') LIMIT " . intval($hesk_settings['kb_search_limit']));
    $num = hesk_dbNumRows($res);

    $articles = array();
    while ($article = hesk_dbFetchAssoc($res))
    {
        $article['content_preview'] = hesk_kbArticleContentPreview($article['content']);
        $article['views_formatted'] = number_format($article['views'], 0, null, $hesklang['sep_1000']);
        $article['votes_formatted'] = number_format($article['votes'], 0, null, $hesklang['sep_1000']);
        $articles[] = $article;
    }

    if ($num === 0) {
        hesk_show_kb_category(1, 1);
    } else {
        $hesk_settings['render_template'](TEMPLATE_PATH . 'customer/knowledgebase/search-results.php', array('articles' => $articles));
    }

    return true;
} // END hesk_kb_search()


function hesk_show_kb_article($artid)
{
	global $hesk_settings, $hesklang, $article;

	// Print header
    $hesk_settings['tmp_title'] = $article['subject'];

    // Update views by 1 - exclude known bots and reloads because of ratings
    if (!isset($_GET['rated']) && !hesk_detect_bots())
    {
		hesk_dbQuery("UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_articles` SET `views`=`views`+1 WHERE `id`={$artid}");
        $article['views']++;
        $article['views_formatted'] = number_format($article['views'], 0, null, $hesklang['sep_1000']);
    }

    if ($article['catid']==1)
    {
        $link = 'knowledgebase.php';
    }
    else
    {
        $link = 'knowledgebase.php?category='.$article['catid'];
    }

    $response = array(
        'article' => $article,
        'attachments' => array(),
        'showRating' => $hesk_settings['kb_rating'] && strpos(hesk_COOKIE('hesk_kb_rate'),'a'.$artid.'%') === false,
        'categoryLink' => $link
    );

    if (!empty($article['attachments'])) {
        $attachments = explode(',', substr($article['attachments'], 0, -1));
        foreach ($attachments as $attachment) {
            list($att_id, $att_name) = explode('#', $attachment);
            $response['attachments'][] = array(
                'id' => $att_id,
                'name' => $att_name
            );
        }
    }

    if (isset($_GET['rated'])) {
        $article['views']++;
    }

    $related_articles = array();
    // Related articles
    if ($hesk_settings['kb_related'])
    {
        require(HESK_PATH . 'inc/mail/email_parser.php');

        $query = hesk_dbEscape( $article['subject'] . ' ' . convert_html_to_text($article['content']) );

        // Get relevant articles from the database
        $res = hesk_dbQuery("SELECT t1.`id`, t1.`subject`, MATCH(`subject`,`content`,`keywords`) AGAINST ('{$query}') AS `score` FROM `".hesk_dbEscape($hesk_settings['db_pfix']).'kb_articles` AS t1 LEFT JOIN `'.hesk_dbEscape($hesk_settings['db_pfix'])."kb_categories` AS t2 ON t1.`catid` = t2.`id` WHERE t1.`type`='0' AND t2.`type`='0' AND MATCH(`subject`,`content`,`keywords`) AGAINST ('{$query}') LIMIT ".intval($hesk_settings['kb_related']+1));

        while ($related = hesk_dbFetchAssoc($res)) {
            // Get base match score from the first article
            if (!isset($base_score)) {
                $base_score = $related['score'];
            }

            // Ignore this article
            if ($related['id'] == $artid) {
                continue;
            }

            // Stop when articles reach less than 10% of base score
            if ($related['score'] / $base_score < 0.10) {
                break;
            }

            // This is a valid related article
            $related_articles[$related['id']] = $related['subject'];
        }
    }
    $response['relatedArticles'] = $related_articles;

    $hesk_settings['render_template'](TEMPLATE_PATH . 'customer/knowledgebase/view-article.php', $response);
} // END hesk_show_kb_article()


function hesk_show_kb_category($catid, $is_search = 0) {
	global $hesk_settings, $hesklang;

	$res = hesk_dbQuery("SELECT `id`,`name`,`parent` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_categories` WHERE `id`='{$catid}' AND `type`='0' LIMIT 1");
    $thiscat = hesk_dbFetchAssoc($res) or hesk_error($hesklang['kb_cat_inv']);

    // Top category? Translate name
    if ($thiscat['id'] == 1)
    {
        $thiscat['name'] = $hesklang['kb_text'];
    }

    $response = array(
        'currentCategory' => $thiscat,
        'noSearchResults' => $is_search,
        'service_messages' => array()
    );
    if ($is_search == 0)
    {
        /* Print header */
        $hesk_settings['tmp_title'] = $hesk_settings['hesk_title'] . ' - ' . hesk_htmlspecialchars($thiscat['name']);

        // If we are in "Knowledgebase only" mode show system messages
        if ($catid == 1 && hesk_check_kb_only(false) )
        {
            // Service messages
            $service_messages = array();
            $res = hesk_dbQuery('SELECT `title`, `message`, `style` FROM `'.hesk_dbEscape($hesk_settings['db_pfix'])."service_messages` WHERE `type`='0' AND (`language` IS NULL OR `language` LIKE '".hesk_dbEscape($hesk_settings['language'])."') ORDER BY `order` ASC");
            while ($sm=hesk_dbFetchAssoc($res))
            {
                $service_messages[] = $sm;
            }
            $response['service_messages'] = $service_messages;
        }
    }

    if ($thiscat['parent'])
    {
        $response['parentLink'] = ($thiscat['parent'] == 1) ? 'knowledgebase.php' : 'knowledgebase.php?category='.$thiscat['parent'];
    }

    $result = hesk_dbQuery("SELECT `id`,`name`,`articles` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_categories` WHERE `parent`='{$catid}' AND `type`='0' ORDER BY `cat_order` ASC");
    $response['subcategories'] = array();
    $response['subcategoriesWidth'] = intval(100 / $hesk_settings['kb_cols']) . '%';
    if (hesk_dbNumRows($result) > 0)
    {
        while ($cat = hesk_dbFetchAssoc($result))
        {
            $displayShowMoreLink = false;
            $articles_to_display = array();

            if ($hesk_settings['kb_numshow'] && $cat['articles'])
            {
                $res = hesk_dbQuery("SELECT `id`,`subject` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_articles` WHERE `catid`='{$cat['id']}' AND `type`='0' ORDER BY `sticky` DESC, `art_order` ASC LIMIT " . (intval($hesk_settings['kb_numshow']) + 1) );
                while ($art = hesk_dbFetchAssoc($res)) {
                    $articles_to_display[] = $art;
                }

                // Do we have more articles for display than what we need?
                if (hesk_dbNumRows($res) > $hesk_settings['kb_numshow']) {
                    $displayShowMoreLink = true;
                    array_pop($articles_to_display);
                } else {
                    // Maybe we have further sub-categories?
                    foreach ($hesk_settings['public_kb_categories'] as $category)
                    {
                        // Show "More" if the sub-category has sub-categories
                        if ($category['parent'] == $cat['id'])
                        {
                            $displayShowMoreLink = true;
                            break;
                        }
                    }
                }
            }

            $response['subcategories'][] = array(
                'subcategory' => $cat,
                'articles' => $articles_to_display,
                'displayShowMoreLink' => $displayShowMoreLink
            );
        }
    } // END if NumRows > 0

    $articles_in_category = array();
    $res = hesk_dbQuery("SELECT `id`, `subject`, LEFT(`content`, ".max(200, $hesk_settings['kb_substrart'] * 2).") AS `content`, `rating`, `votes`, `views` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_articles` WHERE `catid`='{$catid}' AND `type`='0' ORDER BY `sticky` DESC, `art_order` ASC");

    while ($article = hesk_dbFetchAssoc($res))
    {
        $article['content_preview'] = hesk_kbArticleContentPreview($article['content']);
        $article['views_formatted'] = number_format($article['views'], 0, null, $hesklang['sep_1000']);
        $article['votes_formatted'] = number_format($article['votes'], 0, null, $hesklang['sep_1000']);
        $articles_in_category[] = $article;
    }
    $response['articlesInCategory'] = $articles_in_category;

    /* On the main KB page print out top and latest articles if needed */
    if ($catid == 1)
    {
        /* Get list of top articles */
        $response['topArticles'] = hesk_kbTopArticles($hesk_settings['kb_popart'], 0);

        /* Get list of latest articles */
        $response['latestArticles'] = hesk_kbLatestArticles($hesk_settings['kb_latest'], 0);
    } else {
        $response['topArticles'] = array();
        $response['latestArticles'] = array();
    }

    $hesk_settings['render_template'](TEMPLATE_PATH . 'customer/knowledgebase/view-category.php', $response);
} // END hesk_show_kb_category()
?>
