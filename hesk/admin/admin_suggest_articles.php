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

/* Print XML header */
header('Content-Type: text/html; charset='.$hesklang['ENCODING']);

/* Get the search query composed of the subject and message */
$query = hesk_REQUEST('q') or die('');

/* Get relevant articles from the database, include private ones */
$res = hesk_dbQuery("SELECT `id`, `subject`, `content` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_articles` WHERE `type` IN ('0','1') AND MATCH(`subject`,`content`,`keywords`) AGAINST ('".hesk_dbEscape($query)."') LIMIT ".intval($hesk_settings['kb_search_limit']));
$num = hesk_dbNumRows($res);

/* Solve some spacing issues */
if ( hesk_isREQUEST('p') )
{
	echo '&nbsp;<br />';
}

/* Return found articles */
?>
<div class="main__content notice-flash" style="padding: 0">
    <div class="notification-bar white notice-flash" style="display: block; border-left: solid 1px #d4d6e3; border-right: solid 1px #d4d6e3">
        <div class="notification--text" style="display: block; margin: 10px">
            <div>
                <b><?php echo $hesklang['sc']; ?></b>
            </div>
            <span>
                <?php
                if (!$num)
                {
                    echo '<i>'.$hesklang['nsfo'].'</i>';
                }
                else
                {
                    while ($article = hesk_dbFetchAssoc($res))
                    {
                        $txt = strip_tags($article['content']);
                        if (hesk_mb_strlen($txt) > $hesk_settings['kb_substrart'])
                        {
                            $txt = hesk_mb_substr($txt, 0, $hesk_settings['kb_substrart']).'...';
                        }

                        echo '
			<a class="link" href="knowledgebase_private.php?article='.$article['id'].'&amp;suggest=1" target="_blank">'.$article['subject'].'</a>
		    <br />'.$txt.'<br /><br />';
                    }
                }
                ?>
            </span>
        </div>
    </div>
</div>
<?php
exit();
?>
