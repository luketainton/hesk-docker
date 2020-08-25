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

/*** FUNCTIONS ***/

function hesk_kbCategoriesArray($public_only = true)
{
    global $hesk_settings, $hesklang;

    $res = hesk_dbQuery("SELECT * FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_categories` " . ($public_only ? "WHERE `type`='0'" : "") . " ORDER BY `cat_order` ASC");

    $categories = array();

    while ($category = hesk_dbFetchAssoc($res))
    {
        $categories[$category['id']] = $category;
    }

    // Get the full parent path for each category
    foreach ($categories as $id => $category)
    {
        $categories[$id]['parents'] = array();

        // Top category? Just translate name.
        if ($category['parent'] == 0)
        {
            $categories[$id]['name'] = $hesklang['kb_text'];
            continue;
        }

        $current_parrent = $category['parent'];
        $categories[$id]['parents'][] = $current_parrent;

        $i = 0;
        while ($current_parrent > 0 && isset($categories[$current_parrent]) && $categories[$current_parrent]['id'] != $categories[$current_parrent]['parent'] && $i < 1000)
        {
            if (($current_parrent = $categories[$current_parrent]['parent']) > 0)
            {
                $categories[$id]['parents'][] = $current_parrent;
            }
            $i++;
        }

        $categories[$id]['parents'] = array_reverse($categories[$id]['parents']);
    }

    return $categories;
} // END hesk_kbCategoriesArray()


function hesk_kbArticleContentPreview($txt)
{
	global $hesk_settings;

	// Strip HTML tags
	$txt = strip_tags($txt);

	// If text is larger than article preview length, shorten it
	if (hesk_mb_strlen($txt) > $hesk_settings['kb_substrart'])
	{
		// The quick but not 100% accurate way (number of chars displayed may be lower than the limit)
		return hesk_mb_substr($txt, 0, $hesk_settings['kb_substrart']) . '...';

		// If you want a more accurate, but also slower way, use this instead
		// return hesk_htmlentities( hesk_mb_substr( hesk_html_entity_decode($txt), 0, $hesk_settings['kb_substrart'] ) ) . '...';
	}

	return $txt;
} // END hesk_kbArticleContentPreview()


function hesk_kbTopArticles($how_many, $index = 1)
{
	global $hesk_settings, $hesklang;

	$articles = array();
	// Index page or KB main page?
	if ($index)
	{
		// Disabled?
		if (!$hesk_settings['kb_index_popart'])
		{
			return $articles;
		}
	}
	else
	{
		// Disabled?
		if (!$hesk_settings['kb_popart'])
		{
			return $articles;
		}
    }

    /* Get list of articles from the database */
    $res = hesk_dbQuery("SELECT `t1`.`id`,`t1`.`catid`,`t1`.`subject`,`t1`.`views`, `t1`.`content`, `t2`.`name` AS `category`, `t1`.`rating`, `t1`.`votes` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_articles` AS `t1`
                        LEFT JOIN `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_categories` AS `t2` ON `t1`.`catid` = `t2`.`id`
                        WHERE `t1`.`type`='0' AND `t2`.`type`='0'
                        ORDER BY `t1`.`sticky` DESC, `t1`.`views` DESC, `t1`.`art_order` ASC LIMIT ".intval($how_many));

    $articles = array();

    // Remember what articles are printed for "Top" so we don't print them again in "Latest"
    $hesk_settings['kb_top_articles_printed'] = array();

	while ($article = hesk_dbFetchAssoc($res))
	{
        // Top category? Translate name
        if ($article['catid'] == 1)
        {
            $article['category'] = $hesklang['kb_text'];
        }

        $hesk_settings['kb_top_articles_printed'][] = $article['id'];
        $article['content_preview'] = hesk_kbArticleContentPreview($article['content']);
        $article['views_formatted'] = number_format($article['views'], 0, null, $hesklang['sep_1000']);
        $article['votes_formatted'] = number_format($article['votes'], 0, null, $hesklang['sep_1000']);
        $articles[] = $article;
	}

	return $articles;
} // END hesk_kbTopArticles()


function hesk_kbLatestArticles($how_many, $index = 1)
{
	global $hesk_settings, $hesklang;

	$articles = array();
	// Index page or KB main page?
	if ($index)
	{
		// Disabled?
		if ( ! $hesk_settings['kb_index_latest'])
		{
			return $articles;
		}

		// Show title in italics
		$font_weight = 'i';
	}
	else
	{
		// Disabled?
		if ( ! $hesk_settings['kb_latest'])
		{
			return $articles;
		}

		// Show title in bold
		$font_weight = 'b';
    }

    // Don't include articles that have already been printed under "Top" articles
    $sql_top = '';
    if (isset($hesk_settings['kb_top_articles_printed']) && count($hesk_settings['kb_top_articles_printed']))
    {
        $sql_top = ' AND `t1`.`id` NOT IN ('.implode(',', $hesk_settings['kb_top_articles_printed']).')';
    }

    /* Get list of articles from the database */
    $res = hesk_dbQuery("SELECT `t1`.`id`,`t1`.`catid`,`t1`.`subject`,`t1`.`dt`,`t1`.`views`, `t1`.`content`, `t1`.`rating`, `t1`.`votes`, `t2`.`name` AS `category` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_articles` AS `t1`
                        LEFT JOIN `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_categories` AS `t2` ON `t1`.`catid` = `t2`.`id`
                        WHERE `t1`.`type`='0' AND `t2`.`type`='0' {$sql_top}
                        ORDER BY `t1`.`dt` DESC LIMIT ".intval($how_many));

	while ($article = hesk_dbFetchAssoc($res))
	{
        // Top category? Translate name
        if ($article['catid'] == 1)
        {
            $article['category'] = $hesklang['kb_text'];
        }

        $article['content_preview'] = hesk_kbArticleContentPreview($article['content']);
        $article['views_formatted'] = number_format($article['views'], 0, null, $hesklang['sep_1000']);
        $article['votes_formatted'] = number_format($article['votes'], 0, null, $hesklang['sep_1000']);
	    $articles[] = $article;
	}

	return $articles;
} // END hesk_kbLatestArticles()


function hesk_kbSearchLarge($admin = '')
{
	global $hesk_settings, $hesklang;

	$action = 'knowledgebase.php';

	if ($admin)
	{
		if ( ! $hesk_settings['kb_search'])
		{
			return '';
		}
		$action = 'knowledgebase_private.php';
	}
	elseif ($hesk_settings['kb_search'] != 2)
	{
		return '';
	}
	?>
    <form action="<?php echo $action; ?>" method="get" style="display: inline; margin: 10px" name="searchform" class="form">
        <div class="form-group">
            <label for="kb_largesearch"><?php echo $hesklang['ask']; ?></label>
            <div style="display: flex">
                <input id="kb_largesearch" type="text" name="search" class="searchfield form-control" style="flex-grow: 1; margin-right: 10px">
                <button class="btn btn-full" type="submit" title="<?php echo $hesklang['search']; ?>" class="searchbutton" style="display: inline-block; height: 40px !important">
                    <?php echo $hesklang['search']; ?>
                </button>
            </div>
        </div>
        <!-- START KNOWLEDGEBASE SUGGEST -->
        <div id="kb_suggestions" style="display:none">
            <img src="<?php echo HESK_PATH; ?>img/loading.gif" width="24" height="24" alt="" border="0" style="vertical-align:text-bottom" /> <i><?php echo $hesklang['lkbs']; ?></i>
        </div>

        <script type="text/javascript"><!--
            hesk_suggestKBsearch(<?php echo $admin; ?>);
            //-->
        </script>
        <!-- END KNOWLEDGEBASE SUGGEST -->
    </form>
	<?php
} // END hesk_kbSearchLarge()


function hesk_kbSearchSmall()
{
	global $hesk_settings, $hesklang;

	if ($hesk_settings['kb_search'] != 1)
	{
		return '';
	}
    ?>

	<td style="text-align:right" valign="top" width="300">
		<div style="display:inline;">
			<form action="knowledgebase.php" method="get" style="display: inline; margin: 0;">
			<input type="text" name="search" class="searchfield sfsmall" />
			<input type="submit" value="<?php echo $hesklang['search']; ?>" title="<?php echo $hesklang['search']; ?>" class="searchbutton sbsmall" />
			</form>
		</div>
	</td>

	<?php
} // END hesk_kbSearchSmall()


function hesk_detect_bots()
{
	$botlist = array('googlebot', 'msnbot', 'slurp', 'alexa', 'teoma', 'froogle',
	'gigabot', 'inktomi', 'looksmart', 'firefly', 'nationaldirectory',
	'ask jeeves', 'tecnoseek', 'infoseek', 'webfindbot', 'girafabot',
	'crawl', 'www.galaxy.com', 'scooter', 'appie', 'fast', 'webbug', 'spade', 'zyborg', 'rabaz',
	'baiduspider', 'feedfetcher-google', 'technoratisnoop', 'rankivabot',
	'mediapartners-google', 'crawler', 'spider', 'robot', 'bot/', 'bot-','voila');

	if ( ! isset($_SERVER['HTTP_USER_AGENT']))
    {
    	return false;
    }

    $ua = strtolower($_SERVER['HTTP_USER_AGENT']);

	foreach ($botlist as $bot)
    {
    	if (strpos($ua,$bot) !== false)
        {
        	return true;
        }
    }

	return false;
} // END hesk_detect_bots()
