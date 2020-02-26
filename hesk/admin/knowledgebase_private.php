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
require(HESK_PATH . 'inc/knowledgebase_functions.inc.php');
hesk_load_database_functions();

hesk_session_start();
hesk_dbConnect();
hesk_isLoggedIn();

/* Is Knowledgebase enabled? */
if ( ! $hesk_settings['kb_enable'])
{
	hesk_error($hesklang['kbdis']);
}

/* Can this user manage Knowledgebase or just view it? */
$can_man_kb = hesk_checkPermission('can_man_kb',0);

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

$hesk_settings['kb_link'] = ($artid || $catid != 1 || $query) ? '<a href="knowledgebase_private.php" class="smaller">'.$hesklang['gopr'].'</a>' : ($can_man_kb ? $hesklang['gopr'] : '');

if ($hesk_settings['kb_search'] && $query)
{
    hesk_kb_search($query);
}
elseif ($artid)
{
	// Show drafts only to staff who can manage knowledgebase
	if ($can_man_kb)
	{
		$result = hesk_dbQuery("SELECT t1.*, t2.`name` AS `cat_name`
		FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_articles` AS `t1`
		LEFT JOIN `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_categories` AS `t2` ON `t1`.`catid` = `t2`.`id`
		WHERE `t1`.`id` = '{$artid}'
		");
	}
	else
	{
		$result = hesk_dbQuery("SELECT t1.*, t2.`name` AS `cat_name`
		FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_articles` AS `t1`
		LEFT JOIN `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_categories` AS `t2` ON `t1`.`catid` = `t2`.`id`
		WHERE `t1`.`id` = '{$artid}' AND `t1`.`type` IN ('0', '1')
		");
	}

    $article = hesk_dbFetchAssoc($result) or hesk_error($hesklang['kb_art_id']);
    hesk_show_kb_article($artid);
}
else
{
	hesk_show_kb_category($catid);
}

require_once(HESK_PATH . 'inc/footer.inc.php');
exit();


/*** START FUNCTIONS ***/

function hesk_kb_header()
{
    // They may be unused here, but they're used down the line. Don't delete
	global $hesk_settings, $hesklang, $can_man_kb;

	/* Print admin navigation */
	require_once(HESK_PATH . 'inc/show_admin_nav.inc.php');

    hesk_kbSearchLarge(1);
} // END hesk_kb_header()


function hesk_kb_search($query)
{
	global $hesk_settings, $hesklang;

	/* Print header */
	require_once(HESK_PATH . 'inc/header.inc.php');
	hesk_kb_header();

	$res = hesk_dbQuery('SELECT t1.`id`, t1.`subject`, LEFT(`t1`.`content`, '.max(200, $hesk_settings['kb_substrart'] * 2).') AS `content`, t1.`rating` FROM `'.hesk_dbEscape($hesk_settings['db_pfix']).'kb_articles` AS t1 LEFT JOIN `'.hesk_dbEscape($hesk_settings['db_pfix']).'kb_categories` AS t2 ON t1.`catid` = t2.`id` '." WHERE t1.`type` IN ('0','1') AND MATCH(`subject`,`content`,`keywords`) AGAINST ('".hesk_dbEscape($query)."') LIMIT ".intval($hesk_settings['kb_search_limit']));
    $num = hesk_dbNumRows($res);

    ?>



	<?php
	if ($num == 0)
	{
		hesk_show_info($hesklang['nosr']);
        hesk_show_kb_category(1,1);
	}
    else
    {
?>
<div class="main__content categories">
    <div class="table-wrap">
        <h3 style="font-size: 1.3rem"><?php echo $hesklang['sr']; ?> (<?php echo $num; ?>)</h3>
        <?php
        while ($article = hesk_dbFetchAssoc($res))
        {
            $txt = hesk_kbArticleContentPreview($article['content']);

            if ($hesk_settings['kb_rating'])
            {
                $alt = $article['rating'] ? sprintf($hesklang['kb_rated'], sprintf("%01.1f", $article['rating'])) : $hesklang['kb_not_rated'];
                $rat = '<td width="1" valign="top">
                    '.hesk3_get_rating($article['rating']).'
                </td>';
            }
            else
            {
                $rat = '';
            }

            echo '
                <div>
                    <div>
                        <svg class="icon icon-note" style="fill: #9c9c9c">
                            <use xlink:href="'. HESK_PATH .'img/sprite.svg#icon-note"></use>
                        </svg>
                        <a class="link" href="knowledgebase_private.php?article='.$article['id'].'">'.$article['subject'].'</a>
                        '.$rat.'
                    </div>  
                    <div>
                        <svg class="icon icon-note" style="visibility: hidden">
                            <use xlink:href="'. HESK_PATH .'img/sprite.svg#icon-note"></use>
                        </svg>
                        <span class="article_list">'.$txt.'</span>
                    </div>              
                </div>';
        }
        ?>
        <div style="padding-top: 20px">
            <a href="javascript:history.go(-1)">
                <svg class="icon icon-back" style="width: 20px">
                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-back"></use>
                </svg>
                <?php echo $hesklang['back']; ?>
            </a>
        </div>
    </div>
</div>
    <?php
    } // END else

} // END hesk_kb_search()


function hesk_show_kb_article($artid)
{
	global $hesk_settings, $hesklang, $article;

	// Print header
    $hesk_settings['tmp_title'] = $article['subject'];
	require_once(HESK_PATH . 'inc/header.inc.php');
	hesk_kb_header();

    // Update views by 1
	hesk_dbQuery('UPDATE `'.hesk_dbEscape($hesk_settings['db_pfix'])."kb_articles` SET `views`=`views`+1 WHERE `id`={$artid}");
?>
    <div class="main__content knowledge article">
        <div class="article__detalies">
            <div class="article__detalies_head">
                <h3><?php echo $hesklang['ad']; ?></h3>
                <?php

                if ($article['catid']==1)
                {
                    $link = 'knowledgebase_private.php';
                }
                else
                {
                    $link = 'knowledgebase_private.php?category='.$article['catid'];
                }
                ?>
            </div>
            <ul class="article__detalies_list">
                <li>
                    <div class="name"><?php echo $hesklang['aid']; ?></div>
                    <div class="descr">
                        <?php echo $article['id']; ?>
                        <?php
                        if ($article['type'] == 0)
                        {
                            echo '<a href="' . $hesk_settings['hesk_url'] . '/knowledgebase.php?article=' . $article['id'] . '">' . $hesklang['public_link'] . '</a>';
                        }
                        ?>
                    </div>
                </li>
                <li>
                    <div class="name"><?php echo $hesklang['category']; ?></div>
                    <div class="descr">
                        <a style="margin-left: 0" href="<?php echo $link; ?>"><?php echo $article['cat_name']; ?></a>
                    </div>
                </li>
                <li>
                    <div class="name"><?php echo $hesklang['dta']; ?></div>
                    <div class="descr">
                        <?php echo hesk_date($article['dt'], true); ?>
                    </div>
                </li>
                <li>
                    <div class="name"><?php echo $hesklang['views']; ?></div>
                    <div class="descr"><?php echo (isset($_GET['rated']) ? $article['views'] : $article['views']+1); ?></div>
                </li>
                <?php
                if ($hesk_settings['kb_rating']) {
                    ?>
                    <li>
                        <div class="name"><?php echo $hesklang['rating']; ?></div>
                        <div class="descr"><?php echo hesk3_get_rating($article['rating']); ?></div>
                    </li>
                    <?php
                }
                ?>
            </ul>

        </div>
        <div class="article__body">
            <?php
            if (!isset($_GET['back']))
            {
                ?>
                <div class="article__back">
                    <a href="javascript:history.go(-1)">
                        <svg class="icon icon-back">
                            <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-back"></use>
                        </svg>
                        <?php echo $hesklang['back']; ?>
                    </a>
                </div>
                <?php
            }
            ?>
            <h2><?php echo $article['subject']; ?></h2>
            <div class="article__description">
                <?php echo $article['content']; ?>

            </div>
            <div class="article__attachments" style="margin-top: 20px">
                <?php
                if (!empty($article['attachments']))
                {
                    $att=explode(',',substr($article['attachments'], 0, -1));
                    foreach ($att as $myatt)
                    {
                        list($att_id, $att_name) = explode('#', $myatt);
                        echo '
                        <svg class="icon icon-attach" style="fill: #9c9c9c">
                            <use xlink:href="'. HESK_PATH .'img/sprite.svg#icon-attach"></use>
                        </svg>
                        <a class="link" href="../download_attachment.php?kb_att='.$att_id.'" rel="nofollow">
                            '.$att_name.'
                        </a><br>';
                    }
                }
                ?>
            </div>
            <?php
            // Related articles
            if ($hesk_settings['kb_related'])
            {
                require(HESK_PATH . 'inc/mail/email_parser.php');

                $query = hesk_dbEscape( $article['subject'] . ' ' . convert_html_to_text($article['content']) );

                // Get relevant articles from the database
                $res = hesk_dbQuery("SELECT `id`, `subject`, MATCH(`subject`,`content`,`keywords`) AGAINST ('{$query}') AS `score` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_articles` WHERE `type` IN ('0','1') AND MATCH(`subject`,`content`,`keywords`) AGAINST ('{$query}') LIMIT ".intval($hesk_settings['kb_related']+1));

                // Array with related articles
                $related_articles = array();

                while ($related = hesk_dbFetchAssoc($res))
                {
                    // Get base match score from the first article
                    if ( ! isset($base_score) )
                    {
                        $base_score = $related['score'];
                    }

                    // Ignore this article
                    if ( $related['id'] == $artid )
                    {
                        continue;
                    }

                    // Stop when articles reach less than 10% of base score
                    if ($related['score'] / $base_score < 0.10)
                    {
                        break;
                    }

                    // This is a valid related article
                    $related_articles[$related['id']] = $related['subject'];
                }

                // Print related articles if we have any valid matches
                if ( count($related_articles) )
                {
                    echo '<div class="article__related">';
                    echo '<h4>'.$hesklang['relart'].'</h4>';
                    foreach ($related_articles as $id => $subject)
                    {
                        echo '<p><a href="knowledgebase_private.php?article='.$id.'">'.$subject.'</a></p>';
                    }
                    echo '</div>';
                }
            }
            ?>
        </div>
    </div>
    <?php
} // END hesk_show_kb_article()


function hesk_show_kb_category($catid, $is_search = 0) {
	global $hesk_settings, $hesklang;

    if ($is_search == 0)
    {
		/* Print header */
		require_once(HESK_PATH . 'inc/header.inc.php');
		hesk_kb_header();

		if ($catid == 1)
	    {
	    	echo '<span style="padding-left: 16px">' . $hesklang['priv'] . '</span>';
	    }
    }

	$res = hesk_dbQuery("SELECT `name`,`parent` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_categories` WHERE `id`='".intval($catid)."' LIMIT 1");
    $thiscat = hesk_dbFetchAssoc($res) or hesk_error($hesklang['kb_cat_inv']);

	if ($thiscat['parent'])
	{
		$link = ($thiscat['parent'] == 1) ? 'knowledgebase_private.php' : 'knowledgebase_private.php?category='.$thiscat['parent'];
		echo '<span class="homepageh3" style="font-size: 1.4rem; padding-left: 16px">'.$hesklang['kb_cat'].': '.$thiscat['name'].'
        &nbsp;(<a style="display: inline" class="link" href="javascript:history.go(-1)">'.$hesklang['back'].'</a>)</span>
		';
	}

    ?>
    <div class="main__content knowledge">
        <h3 style="font-size: 1.3rem"><?php echo $hesklang['kb_cat_sub']; ?></h3>
        <div class="knowledge__tabs">
            <div class="knowledge__tabs_tab" style="display: flex">
    <?php

	$result = hesk_dbQuery("SELECT `id`,`name`,`articles`,`type` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_categories` WHERE `parent`='".intval($catid)."' ORDER BY `parent` ASC, `cat_order` ASC");
	if (hesk_dbNumRows($result) > 0)
	{
        $i = 1;

        while ($cat = hesk_dbFetchAssoc($result))
        {

            $private = ($cat['type'] == 1) ? ' *' : '';
            ?>
            <div class="knowledge__list">
                <div class="knowledge__list_item">
                    <div class="item--head">
                        <a class="link not-underlined" href="knowledgebase_private.php?category=<?php echo $cat['id']; ?>">
                            <h3>
                                <svg class="icon icon-knowledge" style="fill: #9c9c9c">
                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-knowledge"></use>
                                </svg>
                                <?php echo $cat['name'].$private; ?>
                            </h3>
                        </a>
                    </div>
                    <ul class="item--list">
                    <?php
                    if (!$hesk_settings['kb_numshow'] || !$cat['articles']) {
                        echo '<li><h5>'.$hesklang['noac'].'</h5></li>';
                    }

                    /* Print most popular/sticky articles */
                    if ($hesk_settings['kb_numshow'] && $cat['articles'])
                    {
                        $res = hesk_dbQuery("SELECT `id`,`subject`,`type` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_articles` WHERE `catid`='".intval($cat['id'])."' AND `type` IN ('0','1') ORDER BY `sticky` DESC, `views` DESC, `art_order` ASC LIMIT " . (intval($hesk_settings['kb_numshow']) + 1) );
                        $num = 1;
                        while ($art = hesk_dbFetchAssoc($res))
                        {
                            $private = ($art['type'] == 1) ? ' *' : '';
                            ?>
                            <li>
                                <h5>
                                    <a href="knowledgebase_private.php?article=<?php echo $art['id']; ?>" class="article">
                                        <svg class="icon icon-note">
                                            <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-note"></use>
                                        </svg>
                                        <?php echo $art['subject']; ?>
                                        <?php echo $private; ?>
                                    </a>
                                </h5>
                            </li>
                            <?php

                            if ($num == $hesk_settings['kb_numshow'])
                            {
                                break;
                            }
                            else
                            {
                                $num++;
                            }
                        }
                        if (hesk_dbNumRows($res) > $hesk_settings['kb_numshow'])
                        {
                            echo '
                            <div class="all">
                                <a class="link" href="knowledgebase_private.php?category='. $cat['id'] .'">'.$hesklang['m'].'</a>
                            </div>
                            ';
                        }
                    }
                    ?>
                    </ul>
                </div>
            </div>
            <?php
        }
        ?>
	<?php
	} // END if NumRows > 0
	?>
        </div>
    </div>
    <div class="table-wrap" style="margin-top: 20px">
        <h3 style="font-size: 1.3rem"><?php echo $hesklang['ac']; ?></h3>
        <?php
        $res = hesk_dbQuery("SELECT `id`, `subject`, LEFT(`content`, ".max(200, $hesk_settings['kb_substrart'] * 2).") AS `content`, `rating`, `type` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_articles` WHERE `catid`='".intval($catid)."' AND `type` IN ('0','1') ORDER BY `sticky` DESC, `art_order` ASC");
        if (hesk_dbNumRows($res) == 0)
        {
            echo '<p><i>'.$hesklang['noac'].'</i></p>';
        }
        else
        {
            while ($article = hesk_dbFetchAssoc($res))
            {
                $private = ($article['type'] == 1) ? ' *' : '';

                $txt = hesk_kbArticleContentPreview($article['content']);

                echo '
				<div style="margin: 10px 0">
				    <svg class="icon icon-note" style="fill: #9c9c9c">
                        <use xlink:href="'. HESK_PATH .'img/sprite.svg#icon-note"></use>
                    </svg>
                    <a class="link" href="knowledgebase_private.php?article='.$article['id'].'">'.$article['subject'].'</a>'.$private.'
                    <br>
                    <svg class="icon icon-note" style="visibility: hidden">
                        <use xlink:href="'. HESK_PATH .'img/sprite.svg#icon-note"></use>
                    </svg>
                    <span class="article_list">'.$txt.'</span>
				</div>';
            }
        }
        ?>
    </div>
</div>
<?php
} // END hesk_show_kb_category()
?>
