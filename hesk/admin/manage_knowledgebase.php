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

// Check for POST requests larger than what the server can handle
if ( $_SERVER['REQUEST_METHOD'] == 'POST' && empty($_POST) && ! empty($_SERVER['CONTENT_LENGTH']) )
{
	hesk_error($hesklang['maxpost']);
}

// For convenience allow adding at least 3 attachments at once in the KB
if ($hesk_settings['attachments']['max_number'] < 3)
{
	$hesk_settings['attachments']['max_number'] = 3;
}

hesk_session_start();
hesk_dbConnect();
hesk_isLoggedIn();

/* Check permissions for this feature */
if ( ! hesk_checkPermission('can_man_kb',0))
{
	/* This person can't manage the knowledgebase, but can read it */
	header('Location: knowledgebase_private.php');
    exit();
}

/* Is Knowledgebase enabled? */
if ( ! $hesk_settings['kb_enable'])
{
	hesk_error($hesklang['kbdis']);
}

/* This will tell the header to include WYSIWYG editor Javascript */
define('WYSIWYG',1);

/* What should we do? */
if ( $action = hesk_REQUEST('a') )
{
	if ($action == 'add_article')		 {add_article();}
	elseif ($action == 'add_category')   {add_category();}
	elseif ($action == 'manage_cat') 	 {manage_category();}
	elseif ($action == 'edit_article') 	 {edit_article();}
	elseif ($action == 'import_article') {import_article();}
	elseif ($action == 'list_private')	 {list_private();}
	elseif ($action == 'list_draft')	 {list_draft();}
	elseif ( defined('HESK_DEMO') )		 {hesk_process_messages($hesklang['ddemo'], 'manage_knowledgebase.php', 'NOTICE');}
	elseif ($action == 'new_article')    {new_article();}
	elseif ($action == 'new_category') 	 {new_category();}
	elseif ($action == 'remove_article') {remove_article();}
	elseif ($action == 'save_article') 	 {save_article();}
	elseif ($action == 'order_article')	 {order_article();}
    elseif ($action == 'order_cat')		 {order_category();}
	elseif ($action == 'edit_category')	 {edit_category();}
	elseif ($action == 'remove_kb_att')	 {remove_kb_att();}
	elseif ($action == 'sticky')	 	 {toggle_sticky();}
	elseif ($action == 'update_count')	 {update_count(1);}
}

// Part of a trick to prevent duplicate article submissions by reloading pages
hesk_cleanSessionVars('article_submitted');

/* Print header */
require_once(HESK_PATH . 'inc/header.inc.php');

/* Print main manage users page */
require_once(HESK_PATH . 'inc/show_admin_nav.inc.php');

/* This will handle error, success and notice messages */
hesk_handle_messages();

// Total number of KB articles
$total_articles = 0;

// Get number of sub-categories for each parent category
$parent = array(0 => 1);
$result = hesk_dbQuery('SELECT `parent`, COUNT(*) AS `num` FROM `'.hesk_dbEscape($hesk_settings['db_pfix']).'kb_categories` GROUP BY `parent`');
while ($row = hesk_dbFetchAssoc($result))
{
	$parent[$row['parent']] = $row['num'];
}
$parent_copy = $parent;

//print_r($parent);

// Get Knowledgebase structure
$kb_cat = array();
$result = hesk_dbQuery('SELECT * FROM `'.hesk_dbEscape($hesk_settings['db_pfix']).'kb_categories` ORDER BY `parent` ASC, `cat_order` ASC');
while ($cat = hesk_dbFetchAssoc($result))
{
	// Can this category be moved at all?
	if (
    	$cat['id'] == 1                  || // Main category cannot be moved
        ! isset($parent[$cat['parent']]) || // if the parent category isn't set
        $parent[$cat['parent']] < 2         // Less than 2 articles in category
    )
    {
    	$cat['move_up']   = false;
        $cat['move_down'] = false;
    }
    else
    {
    	$cat['move_up']   = true;
        $cat['move_down'] = true;
    }

	$kb_cat[] = $cat;
}

//print_r($kb_cat);

/* Translate main category "Knowledgebase" if needed */
$kb_cat[0]['name'] = $hesklang['kb_text'];

require(HESK_PATH . 'inc/treemenu/TreeMenu.php');
$icon         = 'icon-chevron-right';
$expandedIcon = 'icon-knowledge';
$menu		  = new HTML_TreeMenu();

$thislevel = array('0');
$nextlevel = array();
$i = 1;
$j = 1;

if (isset($_SESSION['KB_CATEGORY']))
{
	$selected_catid = intval($_SESSION['KB_CATEGORY']);
}
else
{
	$selected_catid = 0;
}

while (count($kb_cat) > 0)
{

    foreach ($kb_cat as $k=>$cat)
    {

    	if (in_array($cat['parent'],$thislevel))
        {
        	$arrow = ($i - 2) % 10;
            $arrow_colors = array(
                0 => '#5ac05a',
                1 => '#a3a0ff',
                2 => '#ff8184',
                3 => '#e686ff',
                4 => '#e19900',
                5 => '#a9a9a9',
                6 => '#db9696',
                7 => '#b7ab00',
                8 => '#a2602d',
                9 => '#aff600'
            );

			$up = $cat['parent'];
			$my = $cat['id'];
			$type = $cat['type'] ? '*' : '';
			$selected = ($selected_catid == $my) ? 1 : 0;
            $cls = (isset($_SESSION['newcat']) && $_SESSION['newcat'] == $my) ? ' class="kbCatListON"' : '';

            $text = str_replace('\\','\\\\','<span id="c_'.$my.'"'.$cls.'><a href="manage_knowledgebase.php?a=manage_cat&catid='.$my.'">'.$cat['name'].'</a>').$type.'</span> (<span class="kb_published">'.$cat['articles'].'</span>, <span class="kb_private">'.$cat['articles_private'].'</span>, <span class="kb_draft">'.$cat['articles_draft'].'</span>) ';                  /* ' */

            $text_short = $cat['name'].$type.' ('.$cat['articles'].', '.$cat['articles_private'].', '.$cat['articles_draft'].')';

			$total_articles += $cat['articles'];

			// Generate KB menu icons
			$menu_icons =
			'<a href="manage_knowledgebase.php?a=add_article&amp;catid='.$my.'" onclick="document.getElementById(\'option'.$j.'\').selected=true;return true;" title="'.$hesklang['kb_i_art'].'">'.
			    '<svg style="fill: #9c9c9c" class="icon icon-add">'.
                    '<use xlink:href="'.HESK_PATH.'img/sprite.svg#icon-add"></use>'.
                '</svg>'.
             '</a>&nbsp;&nbsp;&nbsp;'
			.'<a href="manage_knowledgebase.php?a=add_category&amp;parent='.$my.'" onclick="document.getElementById(\'option'.$j.'_2\').selected=true;return true;" title="'.$hesklang['kb_i_cat'].'">'.
                '<svg style="fill: #9c9c9c" class="icon icon-categories">'.
                    '<use xlink:href="'.HESK_PATH.'img/sprite.svg#icon-categories"></use>'.
                '</svg>'.
              '</a>&nbsp;&nbsp;&nbsp;'
			.'<a href="manage_knowledgebase.php?a=manage_cat&amp;catid='.$my.'" title="'.$hesklang['kb_p_man'].'">'.
			    '<svg style="fill: #9c9c9c" class="icon icon-settings">'.
                    '<use xlink:href="'.HESK_PATH.'img/sprite.svg#icon-settings"></use>'.
                '</svg>'.
              '</a> '
			;

			// Can this category be moved up?
			if ($cat['move_up'] == false || ($cat['move_up'] && $parent_copy[$cat['parent']] == $parent[$cat['parent']]) )
            {
                $menu_icons .= '<a href="#" style="visibility: hidden;width: 11px; display: inline-block">'.
                        '<svg class="icon icon-chevron-up" style="font-size: 8px">'.
                            '<use xlink:href="'. HESK_PATH .'img/sprite.svg#icon-chevron-down"></use>'.
                        '</svg>'.
                    '</a> ';
            }
            else
            {
                $menu_icons .= '<a style="width: 11px; display: inline-block" href="manage_knowledgebase.php?a=order_cat&amp;catid='.$my.'&amp;move=-15&amp;token=' . hesk_token_echo(0) . '" title="'.$hesklang['move_up'].'">'.
                    '<svg class="icon icon-chevron-up" style="fill: '.$arrow_colors[$arrow].'; font-size: 8px">'.
                        '<use xlink:href="'. HESK_PATH .'img/sprite.svg#icon-chevron-down"></use>'.
                    '</svg>'.
                '</a> ';
			}

			// Can this category be moved down?
			if ($cat['move_down'] == false || ($cat['move_down'] && $parent_copy[$cat['parent']] == 1) )
            {
				$menu_icons .= '<a href="#" style="visibility: hidden; width: 11px; display: inline-block">'.
                    '<svg class="icon icon-chevron-down">'.
                        '<use xlink:href="'. HESK_PATH .'img/sprite.svg#icon-chevron-down"></use>'.
                    '</svg>'.
                '</a> ';
            }
            else
            {
				$menu_icons .= '<a style="width: 11px; display: inline-block" href="manage_knowledgebase.php?a=order_cat&amp;catid='.$my.'&amp;move=15&amp;token=' . hesk_token_echo(0) . '" title="'.$hesklang['move_dn'].'">'.
                    '<svg class="icon icon-chevron-down" style="fill: '.$arrow_colors[$arrow].'">'.
                        '<use xlink:href="'. HESK_PATH .'img/sprite.svg#icon-chevron-down"></use>'.
                    '</svg>'.
                '</a> ';
			}

            if (isset($node[$up]))
            {
                $HTML_TreeNode[$my] = new HTML_TreeNode(array('hesk_selected' => $selected, 'text' => $text, 'text_short' => $text_short, 'menu_icons' => $menu_icons, 'hesk_catid' => $cat['id'], 'hesk_select' => 'option'.$j, 'icon' => $icon, 'expandedIcon' => $expandedIcon, 'expanded' => true));
	            $node[$my] = &$node[$up]->addItem($HTML_TreeNode[$my]);
            }
            else
            {
                $node[$my] = new HTML_TreeNode(array('hesk_selected' => $selected, 'text' => $text, 'text_short' => $text_short, 'menu_icons' => $menu_icons, 'hesk_catid' => $cat['id'], 'hesk_select' => 'option'.$j, 'icon' => $icon, 'expandedIcon' => $expandedIcon, 'expanded' => true));
            }

	        $nextlevel[] = $cat['id'];
            $parent_copy[$cat['parent']]--;
            $j++;
	        unset($kb_cat[$k]);

        }

    }

    $thislevel = $nextlevel;
    $nextlevel = array();

    /* Break after 20 recursions to avoid hang-ups in case of any problems */
    if ($i > 20)
    {
    	break;
    }
    $i++;
}

$menu->addItem($node[1]);

// Create the presentation class
$HTML_TreeMenu_DHTML = new HTML_TreeMenu_DHTML($menu, array('images' => '../img', 'defaultClass' => 'treeMenuDefault', 'isDynamic' => true));
$treeMenu = & ref_new($HTML_TreeMenu_DHTML);

$HTML_TreeMenu_Listbox = new HTML_TreeMenu_Listbox($menu);
$listBox  = & ref_new($HTML_TreeMenu_Listbox);

/* Hide new article and new category forms by default */
if (!isset($_SESSION['hide']))
{
	$_SESSION['hide'] = array(
		//'treemenu' => 1,
		'new_article' => 1,
		'new_category' => 1,
	);
}

/* Hide tree menu? */
if (!isset($_SESSION['hide']['treemenu']))
{
	?>
    <div class="main__content categories">
        <div class="table-wrap">

            <h3 style="font-size: 1.3rem">
                <?php echo $hesklang['kb']; ?>
                <div class="tooltype right out-close">
                    <svg class="icon icon-info">
                        <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-info"></use>
                    </svg>
                    <div class="tooltype__content">
                        <div class="tooltype__wrapper">
                            <?php echo $hesklang['kb_intro']; ?>
                        </div>
                    </div>
                </div>
            </h3>
            <?php
            // Show a notice if total public articles is less than 5
            if ($total_articles < 5)
            {
                echo '<div style="margin: -24px -24px 0 -16px;">';
                hesk_show_notice($hesklang['nkba']);
                echo '</div>';
            }
            ?>
            <!-- SUB NAVIGATION -->
            <?php show_subnav(); ?>
            <!-- SUB NAVIGATION -->
            <!-- SHOW THE CATEGORY TREE -->
            <?php show_treeMenu(); ?>
            <!-- SHOW THE CATEGORY TREE -->
            <h4 style="margin-top: 10px;font-size: 1rem; "><?php echo $hesklang['ktool']; ?></h4>
            <div>
                <svg style="fill: #9c9c9c" class="icon icon-search">
                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-search"></use>
                </svg>
                <a class="link" href="manage_knowledgebase.php?a=list_private"><?php echo $hesklang['listp']; ?></a>
            </div>
            <div>
                <svg style="fill: #9c9c9c" class="icon icon-search">
                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-search"></use>
                </svg>
                <a class="link" href="manage_knowledgebase.php?a=list_draft"><?php echo $hesklang['listd']; ?></a>
            </div>
            <div>
                <svg style="fill: #9c9c9c" class="icon icon-settings">
                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-settings"></use>
                </svg>
                <a class="link" href="manage_knowledgebase.php?a=update_count"><?php echo $hesklang['uac']; ?></a>
            </div>
            <div>
                <svg style="fill: #9c9c9c" class="icon icon-tools">
                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-tools"></use>
                </svg>
                <a class="link" href="http://support.mozilla.com/en-US/kb/how-to-write-knowledge-base-articles" rel="nofollow" target="_blank"><?php echo $hesklang['goodkb']; ?></a>
            </div>
        </div>
    </div>
	<?php
} // END hide treemenu

/* Hide article form? */
if (!isset($_SESSION['hide']['new_article']))
{
	if (isset($_SESSION['new_article']))
    {
		$_SESSION['new_article'] = hesk_stripArray($_SESSION['new_article']);
    }
    elseif ( isset($_GET['type']) )
    {
		$_SESSION['new_article']['type'] = intval( hesk_GET('type') );
        if ($_SESSION['new_article']['type'] != 1 && $_SESSION['new_article']['type'] != 2)
        {
        	$_SESSION['new_article']['type'] = 0;
        }
    }

    if ($hesk_settings['kb_wysiwyg'])
    {
        ?>
        <script>
            tinymce.init({
                selector: '#content',
                convert_urls: false,
                branding: false,
                browser_spellcheck: true,
                toolbar: 'undo redo | styleselect fontselect fontsizeselect | bold italic underline | alignleft aligncenter alignright alignjustify | forecolor backcolor | bullist numlist outdent indent | link unlink image codesample code',
                plugins: 'charmap code codesample image link lists table',
            });
        </script>
        <?php
    }

    // If a category is selected, use it as default for articles and parents
    if (isset($_SESSION['KB_CATEGORY']))
    {
        $catid = intval($_SESSION['KB_CATEGORY']);
    }
    ?>

    <div class="main__content knowledge article">
        <form action="manage_knowledgebase.php" method="post" name="form1" enctype="multipart/form-data">
            <div class="article__detalies edit">
                <div class="article__detalies_head">
                    <h3><?php echo $hesklang['ad']; ?></h3>
                </div>
                <ul class="article__detalies_list">
                    <li>
                        <div class="checkbox-custom">
                            <input type="checkbox" id="add_sticky" name="sticky" value="Y" <?php if ( ! empty($_SESSION['new_article']['sticky'])) {echo 'checked';} ?>>
                            <label for="add_sticky"><?php echo $hesklang['sticky']; ?></label>
                        </div>
                    </li>
                    <li>
                        <div class="form-group">
                            <label><?php echo $hesklang['kb_type']; ?></label>
                            <div class="radio-list">
                                <div class="radio-custom">
                                    <input type="radio" id="add_type0" name="type" value="0" <?php if (!isset($_SESSION['new_article']['type']) || (isset($_SESSION['new_article']['type']) && $_SESSION['new_article']['type'] == 0) ) {echo 'checked';} ?>>
                                    <label for="add_type0"><?php echo $hesklang['kb_published']; ?></label>
                                </div>
                                <div style="margin-left: 24px; margin-bottom: 10px"><?php echo $hesklang['kb_published2']; ?></div>
                                <div class="radio-custom">
                                    <input type="radio" id="add_type1" name="type" value="1" <?php if (isset($_SESSION['new_article']['type']) && $_SESSION['new_article']['type'] == 1) {echo 'checked="checked"';} ?>>
                                    <label for="add_type1"><?php echo $hesklang['kb_private']; ?></label>
                                </div>
                                <div style="margin-left: 24px; margin-bottom: 10px"><?php echo $hesklang['kb_private2']; ?></div>
                                <div class="radio-custom">
                                    <input type="radio" id="add_type2" name="type" value="2" <?php if (isset($_SESSION['new_article']['type']) && $_SESSION['new_article']['type'] == 2) {echo 'checked="checked"';} ?>>
                                    <label for="add_type2"><?php echo $hesklang['kb_draft']; ?></label>
                                </div>
                                <div style="margin-left: 24px; margin-bottom: 10px"><?php echo $hesklang['kb_draft2']; ?></div>
                            </div>
                        </div>
                    </li>
                    <li>
                        <div class="name category">
                            <label for="add_catid"><?php echo $hesklang['kb_cat']; ?></label>
                        </div>
                        <div class="descr">
                            <div class="dropdown-select center out-close">
                                <select id="add_catid" name="catid"><?php $listBox->printMenu(); ?></select>
                            </div>
                        </div>
                    </li>
                </ul>
                <div class="article__detalies_action">
                    <button type="submit" class="btn btn-full" ripple="ripple"><?php echo $hesklang['kb_save']; ?></button>
                </div>
            </div>
            <div class="article__body form">
                <div class="article__back">
                    <a href="manage_knowledgebase.php?a=manage_cat&amp;catid=<?php echo $catid; ?>">
                        <svg class="icon icon-back">
                            <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-back"></use>
                        </svg>
                        <span><?php echo $hesklang['wizard_back']; ?></span>
                    </a>
                </div>
                <div class="article__title">
                    <div class="form-group">
                        <label for="add_subject"><?php echo $hesklang['kb_subject']; ?></label>
                        <input id="add_subject" type="text" name="subject" class="form-control" maxlength="255"
                            <?php if (isset($_SESSION['new_article']['subject'])) {echo 'value="'.$_SESSION['new_article']['subject'].'"';} ?>>
                    </div>
                </div>
                <div class="article__description">
                    <?php
                    $displayType = $hesk_settings['kb_wysiwyg'] ? 'none' : 'block';
                    $displayWarn = 'none';
                    ?>
                    <span id="contentType" style="display:<?php echo $displayType; ?>">
                        <label><input type="radio" name="html" value="0" <?php if (!isset($_SESSION['new_article']['html']) || (isset($_SESSION['new_article']['html']) && $_SESSION['new_article']['html'] == 0) ) {echo 'checked="checked"';} ?> onclick="javascript:document.getElementById('kblinks').style.display = 'none'" /> <?php echo $hesklang['kb_dhtml']; ?></label><br />
                        <label><input type="radio" name="html" value="1" <?php $display = 'none'; if (isset($_SESSION['new_article']['html']) && $_SESSION['new_article']['html'] == 1) {echo 'checked="checked"'; $displayWarn = 'block';} ?> onclick="javascript:document.getElementById('kblinks').style.display = 'block'" /> <?php echo $hesklang['kb_ehtml']; ?></label><br />
                        <span id="kblinks" style="display:<?php echo $displayWarn; ?>"><i><?php echo $hesklang['kb_links']; ?></i></span>
                    </span>
                    <label>
                        <textarea name="content" rows="25" cols="70" id="content"><?php if (isset($_SESSION['new_article']['content'])) {echo $_SESSION['new_article']['content'];} ?></textarea>
                    </label>
                </div>
                <?php
                if ($hesk_settings['attachments']['use'])
                {
                ?>
                <div style="margin-top: 16px">
                    <svg class="icon icon-attach">
                        <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-attach"></use>
                    </svg>
                    <?php echo $hesklang['attachments']; ?> (<a href="Javascript:void(0)" onclick="hesk_window('../file_limits.php',250,500);return false;"><?php echo $hesklang['ful']; ?></a>)
                    <?php
                    for ($i=1;$i<=$hesk_settings['attachments']['max_number'];$i++)
                    {
                        echo '<div><input type="file" name="attachment['.$i.']"></div>';
                    }
                    ?>
                </div>
                    <?php
                } // End attachments
                ?>
                <div class="form-group article__keywords">
                    <label for="keywords">
                        <b><?php echo $hesklang['kw']; ?></b>
                        <span><?php echo $hesklang['kw1']; ?></span>
                    </label>
                    <textarea class="form-control" style="height: inherit;" name="keywords" rows="3" cols="70" id="keywords"><?php if (isset($_SESSION['new_article']['keywords'])) {echo $_SESSION['new_article']['keywords'];} ?></textarea>
                </div>
            </div>
            <div class="d-flex-center sm-hidden mt2">
                <button type="submit" class="btn btn-full ml1" ripple="ripple"><?php echo $hesklang['kb_save']; ?></button>
            </div>
            <input type="hidden" name="a" value="new_article">
            <input type="hidden" name="token" value="<?php hesk_token_echo(); ?>">
        </form>
    </div>
	<?php
} // END hide article

/* Hide new category form? */
if (!isset($_SESSION['hide']['new_category']))
{

	if (isset($_SESSION['new_category']))
    {
		$_SESSION['new_category'] = hesk_stripArray($_SESSION['new_category']);
    }
	?>
    <div class="main__content categories">
        <div class="table-wrap">
            <form class="form" action="manage_knowledgebase.php" method="post" name="form2">
                <h3 style="font-size: 1.3rem"><a name="new_category"></a><?php echo $hesklang['kb_cat_new']; ?></h3>
                <div class="form-group">
                    <label for="add_cat_title"><?php echo $hesklang['kb_cat_title']; ?></label>
                    <input type="text" name="title" class="form-control" id="add_cat_title" maxlength="255">
                </div>
                <div class="form-group">
                    <label for="add_cat_parent"><?php echo $hesklang['kb_cat_parent']; ?></label>
                    <div class="dropdown-select center out-close">
                        <select id="add_cat_parent" name="parent"><?php $listBox->printMenu()?></select>
                    </div>
                </div>
                <div class="form-group">
                    <label><?php echo $hesklang['kb_type']; ?></label>
                    <div class="radio-list">
                        <div class="radio-custom">
                            <input type="radio" name="type" id="add_type0" value="0" <?php if (!isset($_SESSION['new_category']['type']) || (isset($_SESSION['new_category']['type']) && $_SESSION['new_category']['type'] == 0) ) {echo 'checked';} ?>>
                            <label for="add_type0"><?php echo $hesklang['kb_published']; ?></label>
                        </div>
                        <div style="margin-left: 24px; margin-bottom: 10px">
                            <?php echo $hesklang['kb_cat_published']; ?>
                        </div>
                        <div class="radio-custom">
                            <input type="radio" name="type" id="add_type1" value="1" <?php if (isset($_SESSION['new_category']['type']) && $_SESSION['new_category']['type'] == 1) {echo 'checked';} ?>>
                            <label for="add_type1"><?php echo $hesklang['kb_private']; ?></label>
                        </div>
                        <div style="margin-left: 24px; margin-bottom: 10px">
                            <?php echo $hesklang['kb_cat_private']; ?>
                        </div>
                    </div>
                </div>
                <input type="hidden" name="a" value="new_category">
                <input type="hidden" name="token" value="<?php hesk_token_echo(); ?>">
                <div>
                    <button style="display: inline-flex" type="submit" class="btn btn-full" ripple="ripple"><?php echo $hesklang['kb_cat_add']; ?></button>
                    <a style="display: inline-flex" class="btn btn-border" href="manage_knowledgebase.php" ripple="ripple"><?php echo $hesklang['cancel']; ?></a>
                </div>
            </form>
        </div>
        <?php
        /* Show the treemenu? */
        if (isset($_SESSION['hide']['cat_treemenu']))
        {
            echo '<div class="table-wrap" style="margin-top: 20px">';
            show_treeMenu();
            echo '</div>';
        }
        ?>
    </div>
	<?php
} // END hide new category form

/* Clean unneeded session variables */
hesk_cleanSessionVars(array('hide','new_article','new_category','KB_CATEGORY','manage_cat','edit_article','newcat'));
?>

<p>&nbsp;</p>

<?php
require_once(HESK_PATH . 'inc/footer.inc.php');
exit();


/*** START FUNCTIONS ***/

function list_draft() {
	global $hesk_settings, $hesklang;

    $catid  = 1;
    $kb_cat = hesk_getCategoriesArray(1);

	/* Translate main category "Knowledgebase" if needed */
	$kb_cat[0]['name'] = $hesklang['kb_text'];

	/* Print header */
	require_once(HESK_PATH . 'inc/header.inc.php');

	/* Print main manage users page */
	require_once(HESK_PATH . 'inc/show_admin_nav.inc.php');
	?>
    <div class="main__content knowledge category">
        <div class="category__list visible">
            <div class="category__list_head">
                <h3><?php echo $hesklang['artd']; ?></h3>
            </div>
            <div class="category__list_table overflow-x-scroll" style="display: block">
                <div style="float: right; margin-bottom: 10px;">
                    <a class="btn btn--blue-border" href="manage_knowledgebase.php?a=add_article&amp;catid=<?php echo $catid; ?>&amp;type=2">
                        <?php echo $hesklang['kb_i_art2']; ?>
                    </a>
                </div>
                <table>
                    <tbody>
                    <?php
                    $result = hesk_dbQuery("SELECT * FROM `". hesk_dbEscape($hesk_settings['db_pfix'])."kb_articles` WHERE `type`='2' ORDER BY `catid` ASC, `id` ASC");
                    $num = hesk_dbNumRows($result);

                    if ($num == 0)
                    {
                        echo '
                            <tr>
                                <td colspan="4" style="padding-left: 10px">'.$hesklang['kb_no_dart'].'</td>
                            </tr>
                            ';
                    }
                    else
                    {
                        while ($article=hesk_dbFetchAssoc($result))
                        {
                            // Check for articles with no existing parent category
                            if ( ! isset($kb_cat[$article['catid']]) )
                            {
                                $article['catid'] = hesk_stray_article($article['id']);
                            }

                            $table_row = 'class="';
                            if (isset($_SESSION['artord']) && $article['id'] == $_SESSION['artord'])
                            {
                                $table_row .= 'ticket-new ';
                                unset($_SESSION['artord']);
                            }

                            if ($article['sticky']) {
                                $table_row .= 'sticky';
                            }
                            $table_row .= '"';
                            ?>
                            <tr <?php echo $table_row; ?>>
                                <td class="title">
                                    <a href="knowledgebase_private.php?article=<?php echo $article['id']; ?>&amp;back=1<?php if ($article['type'] == 2) {echo '&amp;draft=1';} ?>">
                                        <?php echo $article['subject']; ?>
                                    </a>
                                </td>
                                <td class="view">
                                    <svg class="icon icon-eye-close">
                                        <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-eye-close"></use>
                                    </svg>
                                    <?php echo $article['views']; ?>
                                </td>
                                <td class="status">
                                    <div style="margin-bottom: 3px"><?php echo $hesklang['kb_draft']; ?></div>
                                </td>
                                <td class="actions">
                                    <div class="actions--buttons">
                                        <a href="manage_knowledgebase.php?a=edit_article&amp;id=<?php echo $article['id']; ?>"
                                           title="<?php echo $hesklang['edit']; ?>">
                                            <svg class="icon icon-edit-ticket">
                                                <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-edit-ticket"></use>
                                            </svg>
                                        </a>
                                        <?php
                                        $modal_id = hesk_generate_delete_modal($hesklang['confirm_deletion'],
                                            $hesklang['del_art'],
                                            'manage_knowledgebase.php?a=remove_article&amp;id='. $article['id'] .'&amp;token='. hesk_token_echo(0));
                                        ?>
                                        <a href="javascript:"
                                            data-modal="[data-modal-id='<?php echo $modal_id; ?>']"
                                           title="<?php echo $hesklang['delete']; ?>">
                                            <svg class="icon icon-delete">
                                                <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-delete"></use>
                                            </svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php
                        } // End while
                    } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php

	/* Clean unneeded session variables */
	hesk_cleanSessionVars(array('hide','manage_cat','edit_article'));

    require_once(HESK_PATH . 'inc/footer.inc.php');
    exit();
} // END list_draft()


function list_private() {
	global $hesk_settings, $hesklang;

    $catid  = 1;
    $kb_cat = hesk_getCategoriesArray(1);

	/* Translate main category "Knowledgebase" if needed */
	$kb_cat[0]['name'] = $hesklang['kb_text'];

    /* Get list of private categories */
    $private_categories = array();
	$res = hesk_dbQuery("SELECT `id` FROM `". hesk_dbEscape($hesk_settings['db_pfix'])."kb_categories` WHERE `type`='1'");
    $num = hesk_dbNumRows($res);
    if ($num)
    {
    	while ($row = hesk_dbFetchAssoc($res))
		{
			$private_categories[] = intval($row['id']);
        }
    }

	/* Print header */
	require_once(HESK_PATH . 'inc/header.inc.php');

	/* Print main manage users page */
	require_once(HESK_PATH . 'inc/show_admin_nav.inc.php');
	?>
    <div class="main__content knowledge category">
        <div class="category__list visible">
            <div class="category__list_head">
                <h3><?php echo $hesklang['artp']; ?></h3>
            </div>
            <div class="category__list_table overflow-x-scroll" style="display: block">
                <div style="float: right; margin-bottom: 10px;">
                    <a class="btn btn--blue-border" href="manage_knowledgebase.php?a=add_article&amp;catid=<?php echo $catid; ?>&amp;type=1">
                        <?php echo $hesklang['kb_i_art2']; ?>
                    </a>
                </div>
                <table>
                    <tbody>
                    <?php
                    $result = hesk_dbQuery("SELECT * FROM `". hesk_dbEscape($hesk_settings['db_pfix'])."kb_articles` WHERE `type`='1' " . (count($private_categories) ? " OR `catid` IN('" . implode("','", $private_categories) . "') " : '') . " ORDER BY `catid` ASC, `id` ASC");
                    $num = hesk_dbNumRows($result);

                    if ($num == 0)
                    {
                        echo '
                            <tr>
                                <td colspan="4" style="padding-left: 10px">'.$hesklang['kb_no_part'].'</td>
                            </tr>
                            ';
                    }
                    else
                    {
                        while ($article=hesk_dbFetchAssoc($result))
                        {
                            // Check for articles with no existing parent category
                            if ( ! isset($kb_cat[$article['catid']]) )
                            {
                                $article['catid'] = hesk_stray_article($article['id']);
                            }

                            $table_row = 'class="';
                            if (isset($_SESSION['artord']) && $article['id'] == $_SESSION['artord'])
                            {
                                $table_row = 'ticket-new ';
                                unset($_SESSION['artord']);
                            }

                            if ($article['sticky']) {
                                $table_row .= 'sticky';
                            }
                            $table_row .= '"';


                            if ($hesk_settings['kb_rating'])
                            {
                                $alt = $article['rating'] ? sprintf($hesklang['kb_rated'], sprintf("%01.1f", $article['rating'])) : $hesklang['kb_not_rated'];
                                $type = hesk3_get_rating($article['rating'], $article['votes']);
                            }

                            ?>
                            <tr <?php echo $table_row; ?>>
                                <td class="title">
                                    <a href="knowledgebase_private.php?article=<?php echo $article['id']; ?>&amp;back=1<?php if ($article['type'] == 2) {echo '&amp;draft=1';} ?>">
                                        <?php echo $article['subject']; ?>
                                    </a>
                                </td>
                                <td class="view">
                                    <svg class="icon icon-eye-close">
                                        <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-eye-close"></use>
                                    </svg>
                                    <?php echo $article['views']; ?>
                                </td>
                                <td class="status">
                                    <?php echo $type; ?>
                                </td>
                                <td class="actions">
                                    <div class="actions--buttons">
                                        <a href="manage_knowledgebase.php?a=edit_article&amp;id=<?php echo $article['id']; ?>"
                                           title="<?php echo $hesklang['edit']; ?>">
                                            <svg class="icon icon-edit-ticket">
                                                <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-edit-ticket"></use>
                                            </svg>
                                        </a>
                                        <?php
                                        $modal_id = hesk_generate_delete_modal($hesklang['confirm_deletion'],
                                            $hesklang['del_art'],
                                            'manage_knowledgebase.php?a=remove_article&amp;id='. $article['id'] .'&amp;token='. hesk_token_echo(0));
                                        ?>
                                        <a href="javascript:"
                                           data-modal="[data-modal-id='<?php echo $modal_id; ?>']"
                                           title="<?php echo $hesklang['delete']; ?>">
                                            <svg class="icon icon-delete">
                                                <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-delete"></use>
                                            </svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php
                        } // End while
                    } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php
	/* Clean unneeded session variables */
	hesk_cleanSessionVars(array('hide','manage_cat','edit_article'));

    require_once(HESK_PATH . 'inc/footer.inc.php');
    exit();
} // END list_private()


function import_article()
{
	global $hesk_settings, $hesklang, $listBox;

	$_SESSION['hide'] = array(
		'treemenu' => 1,
		//'new_article' => 1,
		'new_category' => 1,
	);

    $_SESSION['KB_CATEGORY'] = 1;

    // Get ticket ID
    $trackingID = hesk_cleanID();
	if (empty($trackingID))
	{
		return false;
	}

	// Get ticket info
	$res = hesk_dbQuery("SELECT `id`,`category`,`subject`,`message`,`owner` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` WHERE `trackid`='".hesk_dbEscape($trackingID)."' LIMIT 1");
	if (hesk_dbNumRows($res) != 1)
	{
		return false;
	}
	$ticket = hesk_dbFetchAssoc($res);

	// Permission to view this ticket?
	if ($ticket['owner'] && $ticket['owner'] != $_SESSION['id'] && ! hesk_checkPermission('can_view_ass_others',0))
	{
		return false;
	}

	if ( ! $ticket['owner'] && ! hesk_checkPermission('can_view_unassigned',0))
	{
		return false;
	}

	// Is this user allowed to view tickets inside this category?
	if ( ! hesk_okCategory($ticket['category'],0))
    {
    	return false;
    }

    // Set article contents
    if ($hesk_settings['kb_wysiwyg'])
    {
    	// With WYSIWYG editor
		$_SESSION['new_article'] = array(
		'html' => 1,
		'subject' => $ticket['subject'],
		'content' => hesk_htmlspecialchars($ticket['message']),
		);
    }
    else
    {
    	// Without WYSIWYG editor *
		$_SESSION['new_article'] = array(
		'html' => 0,
		'subject' => $ticket['subject'],
		'content' => hesk_msgToPlain($ticket['message'], 0, 0),
		);
    }

	// Get messages from replies to the ticket
	$res = hesk_dbQuery("SELECT `message` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."replies` WHERE `replyto`='".intval($ticket['id'])."' ORDER BY `id` ASC");

    while ($reply=hesk_dbFetchAssoc($res))
    {
    	if ($hesk_settings['kb_wysiwyg'])
        {
			$_SESSION['new_article']['content'] .= "<br /><br />" . hesk_htmlspecialchars($reply['message']);
        }
        else
        {
	        $_SESSION['new_article']['content'] .= "\n\n" . hesk_msgToPlain($reply['message'], 0, 0);
        }
    }

    // Make sure everything is extra slashed as stripslashes will be called later
    $_SESSION['new_article']['subject'] = addslashes($_SESSION['new_article']['subject']);
    $_SESSION['new_article']['content'] = addslashes($_SESSION['new_article']['content']);

    hesk_process_messages($hesklang['import'],'NOREDIRECT','NOTICE');

} // END add_article()


function add_article()
{
	global $hesk_settings, $hesklang;

	$_SESSION['hide'] = array(
		'treemenu' => 1,
		//'new_article' => 1,
		'new_category' => 1,
	);

    $_SESSION['KB_CATEGORY'] = intval( hesk_GET('catid', 1) );
} // END add_article()


function add_category()
{
	global $hesk_settings, $hesklang;

	$_SESSION['hide'] = array(
		'treemenu' => 1,
		'new_article' => 1,
		//'new_category' => 1,
        'cat_treemenu' => 1,
	);

    $_SESSION['KB_CATEGORY'] = intval( hesk_GET('parent', 1) );
} // END add_category()


function remove_kb_att()
{
	global $hesk_settings, $hesklang;

	// A security check
	hesk_token_check();

	$att_id  = intval( hesk_GET('kb_att') ) or hesk_error($hesklang['inv_att_id']);
    $id		 = intval( hesk_GET('id', 1) );

	// Get attachment details
	$res = hesk_dbQuery("SELECT * FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_attachments` WHERE `att_id`='".intval($att_id)."'");

    // Does the attachment exist?
	if ( hesk_dbNumRows($res) != 1 )
    {
    	hesk_process_messages($hesklang['inv_att_id'], 'manage_knowledgebase.php');
    }

    $att = hesk_dbFetchAssoc($res);

	// Delete the file if it exists
    hesk_unlink(HESK_PATH.$hesk_settings['attach_dir'].'/'.$att['saved_name']);

	hesk_dbQuery("DELETE FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_attachments` WHERE `att_id`='".intval($att_id)."'");

	$res = hesk_dbQuery("SELECT * FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_articles` WHERE `id`='".intval($id)."'");
    $art = hesk_dbFetchAssoc($res);

    // Make log entry
    $revision = sprintf($hesklang['thist12'],hesk_date(),$att['real_name'],$_SESSION['name'].' ('.$_SESSION['user'].')');

    // Remove attachment from article
    $art['attachments'] = str_replace($att_id.'#'.$att['real_name'].',','',$art['attachments']);

	hesk_dbQuery("UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_articles` SET `attachments`='".hesk_dbEscape($art['attachments'])."', `history`=CONCAT(`history`,'".hesk_dbEscape($revision)."') WHERE `id`='".intval($id)."'");

    hesk_process_messages($hesklang['kb_att_rem'],'manage_knowledgebase.php?a=edit_article&id='.$id,'SUCCESS');
} // END remove_kb_att()


function edit_category()
{
	global $hesk_settings, $hesklang;

	/* A security check */
	hesk_token_check('POST');

	$_SESSION['hide'] = array(
		'article_list' => 1,
	);

    $hesk_error_buffer = array();

	$catid  = intval( hesk_POST('catid') ) or hesk_error($hesklang['kb_cat_inv']);
    $title  = hesk_input( hesk_POST('title') ) or $hesk_error_buffer[] = $hesklang['kb_cat_e_title'];
    $parent = intval( hesk_POST('parent', 1) );
    $type   = empty($_POST['type']) ? 0 : 1;

    /* Category can't be it's own parent */
    if ($parent == $catid)
    {
		$hesk_error_buffer[] = $hesklang['kb_spar'];
    }

    /* Any errors? */
    if (count($hesk_error_buffer))
    {
		$_SESSION['manage_cat'] = array(
		'type' => $type,
		'parent' => $parent,
		'title' => $title,
		);

		$tmp = '';
		foreach ($hesk_error_buffer as $error)
		{
			$tmp .= "<li>$error</li>\n";
		}
		$hesk_error_buffer = $tmp;

    	$hesk_error_buffer = $hesklang['rfm'].'<br /><br /><ul>'.$hesk_error_buffer.'</ul>';
    	hesk_process_messages($hesk_error_buffer,'./manage_knowledgebase.php?a=manage_cat&catid='.$catid);
    }

    /* Delete category or just update it? */
    if ( hesk_POST('dodelete')=='Y')
    {
    	// Delete contents
    	if ( hesk_POST('movearticles') == 'N')
        {
			// Delete all articles and all subcategories
			delete_category_recursive($catid);
        }
        // Move contents
        else
        {
			// -> Update category of articles in the category we are deleting
			hesk_dbQuery("UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_articles` SET `catid`=".intval($parent)." WHERE `catid`='".intval($catid)."'");

			// -> Update parent category of subcategories
			hesk_dbQuery("UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_categories` SET `parent`=".intval($parent)." WHERE `parent`='".intval($catid)."'");

			// -> Update article counts to make sure they are correct
			update_count();
        }

        // Now delete the category
        hesk_dbQuery("DELETE FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_categories` WHERE `id`='".intval($catid)."'");

        // Clear KB cache
        hesk_purge_cache('kb');

		$_SESSION['hide'] = array(
			//'treemenu' => 1,
			'new_article' => 1,
			'new_category' => 1,
		);

        hesk_process_messages($hesklang['kb_cat_dlt'],'./manage_knowledgebase.php','SUCCESS');
    }

	hesk_dbQuery("UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_categories` SET `name`='".hesk_dbEscape($title)."',`parent`=".intval($parent).",`type`='".intval($type)."' WHERE `id`='".intval($catid)."'");

    unset($_SESSION['hide']);

    hesk_process_messages($hesklang['your_cat_mod'],'./manage_knowledgebase.php?a=manage_cat&catid='.$catid,'SUCCESS');
} // END edit_category()


function save_article()
{
	global $hesk_settings, $hesklang, $hesk_error_buffer;

	/* A security check */
	hesk_token_check('POST');

    $hesk_error_buffer = array();

    $id    = intval( hesk_POST('id') ) or hesk_error($hesklang['kb_art_id']);
	$catid = intval( hesk_POST('catid', 1) );
    $type  = intval( hesk_POST('type') );
    $type  = ($type < 0 || $type > 2) ? 0 : $type;
    $html  = $hesk_settings['kb_wysiwyg'] ? 1 : (empty($_POST['html']) ? 0 : 1);
    $now   = hesk_date();
    $old_catid = intval( hesk_POST('old_catid') );
    $old_type  = intval( hesk_POST('old_type') );
    $old_type  = ($old_type < 0 || $old_type > 2) ? 0 : $old_type;
    $from = hesk_POST('from');

    $subject = hesk_input( hesk_POST('subject') ) or $hesk_error_buffer[] = $hesklang['kb_e_subj'];

    if ($html)
    {
	    if (empty($_POST['content']))
	    {
	    	$hesk_error_buffer[] = $hesklang['kb_e_cont'];
	    }
        
	    $content = hesk_getHTML( hesk_POST('content') );

        // Clean the HTML code
        require(HESK_PATH . 'inc/htmlpurifier/HeskHTMLPurifier.php');
        $purifier = new HeskHTMLPurifier($hesk_settings['cache_dir']);
        $content = $purifier->heskPurify($content);
    }
	else
    {
    	$content = hesk_input( hesk_POST('content') ) or $hesk_error_buffer[] = $hesklang['kb_e_cont'];
	    $content = nl2br($content);
	    $content = hesk_makeURL($content);
    }

    $sticky = isset($_POST['sticky']) ? 1 : 0;

    $keywords = hesk_input( hesk_POST('keywords') );

    $extra_sql = '';
    if ( hesk_POST('resetviews')=='Y')
    {
    	$extra_sql .= ',`views`=0 ';
    }
    if (hesk_POST('resetvotes')=='Y')
    {
    	$extra_sql .= ',`votes`=0, `rating`=0 ';
    }

    /* Article attachments */
	define('KB',1);
    require_once(HESK_PATH . 'inc/posting_functions.inc.php');
    $attachments = array();
	$myattachments='';

	if ($hesk_settings['attachments']['use'])
	{
		require_once(HESK_PATH . 'inc/attachments.inc.php');

		for ($i=1; $i<=$hesk_settings['attachments']['max_number']; $i++)
		{
			$att = hesk_uploadFile($i);
			if ( ! empty($att))
			{
				$attachments[$i] = $att;
			}
		}
	}

    /* Any errors? */
    if (count($hesk_error_buffer))
    {
		// Remove any successfully uploaded attachments
		if ($hesk_settings['attachments']['use'])
		{
			hesk_removeAttachments($attachments);
		}

		$_SESSION['edit_article'] = array(
		'type' => $type,
		'html' => $html,
		'subject' => $subject,
		'content' => hesk_input( hesk_POST('content') ),
		'keywords' => $keywords,
        'catid' => $catid,
        'sticky' => $sticky,
        'resetviews' => (isset($_POST['resetviews']) ? 'Y' : 0),
        'resetvotes' => (isset($_POST['resetvotes']) ? 'Y' : 0),
		);

		$tmp = '';
		foreach ($hesk_error_buffer as $error)
		{
			$tmp .= "<li>$error</li>\n";
		}
		$hesk_error_buffer = $tmp;

    	$hesk_error_buffer = $hesklang['rfm'].'<br /><br /><ul>'.$hesk_error_buffer.'</ul>';
    	hesk_process_messages($hesk_error_buffer,'./manage_knowledgebase.php?a=edit_article&id='.$id.'&from='.$from);
    }

	/* Add to database */
	if (!empty($attachments))
	{
	    foreach ($attachments as $myatt)
	    {
	        hesk_dbQuery("INSERT INTO `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_attachments` (`saved_name`,`real_name`,`size`) VALUES ('".hesk_dbEscape($myatt['saved_name'])."', '".hesk_dbEscape($myatt['real_name'])."', '".intval($myatt['size'])."')");
	        $myattachments .= hesk_dbInsertID() . '#' . $myatt['real_name'] .',';
	    }

        $extra_sql .= ", `attachments` = CONCAT(`attachments`, '".$myattachments."') ";
	}

    /* Update article in the database */
    $revision = sprintf($hesklang['revision2'],$now,$_SESSION['name'].' ('.$_SESSION['user'].')');

	hesk_dbQuery("UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_articles` SET
    `catid`=".intval($catid).",
    `subject`='".hesk_dbEscape($subject)."',
    `content`='".hesk_dbEscape($content)."',
    `keywords`='".hesk_dbEscape($keywords)."' $extra_sql ,
    `type`='".intval($type)."',
    `html`='".intval($html)."',
    `sticky`='".intval($sticky)."',
    `history`=CONCAT(`history`,'".hesk_dbEscape($revision)."')
    WHERE `id`='".intval($id)."'");

    $_SESSION['artord'] = $id;

	// Update proper category article count
    // (just do them all to be sure, don't compliate...)
	update_count();

    // Update article order
    update_article_order($catid);

    // Clear KB cache
    hesk_purge_cache('kb');

    // Redirect to the correct page
    switch ($from)
    {
        case 'draft':
            $redirect_action = 'a=list_draft';
            break;
        case 'private':
            $redirect_action = 'a=list_private';
            break;
        default:
            $redirect_action = 'a=manage_cat&catid='.$catid;
            break;
    }

    hesk_process_messages($hesklang['your_kb_mod'],'./manage_knowledgebase.php?'.$redirect_action,'SUCCESS');
} // END save_article()


function edit_article()
{
	global $hesk_settings, $hesklang, $listBox;

    $hesk_error_buffer = array();

    $id = intval( hesk_GET('id') ) or hesk_process_messages($hesklang['kb_art_id'],'./manage_knowledgebase.php');

    /* Get article details */
	$result = hesk_dbQuery("SELECT * FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_articles` WHERE `id`='".intval($id)."' LIMIT 1");
    if (hesk_dbNumRows($result) != 1)
    {
        hesk_process_messages($hesklang['kb_art_id'],'./manage_knowledgebase.php');
    }
    $article = hesk_dbFetchAssoc($result);

    if ($hesk_settings['kb_wysiwyg'] || $article['html'])
    {
		$article['content'] = hesk_htmlspecialchars($article['content']);
    }
    else
    {
    	$article['content'] = hesk_msgToPlain($article['content']);
    }

    $catid = $article['catid'];

    $from = hesk_GET('from');

    if (isset($_SESSION['edit_article']))
    {
    	$_SESSION['edit_article'] = hesk_stripArray($_SESSION['edit_article']);
		$article['type'] = $_SESSION['edit_article']['type'];
        $article['html'] = $_SESSION['edit_article']['html'];
        $article['subject'] = $_SESSION['edit_article']['subject'];
        $article['content'] = $_SESSION['edit_article']['content'];
        $article['keywords'] = $_SESSION['edit_article']['keywords'];
        $article['catid'] = $_SESSION['edit_article']['catid'];
        $article['sticky'] = $_SESSION['edit_article']['sticky'];
    }

    /* Get categories */
	$result = hesk_dbQuery('SELECT * FROM `'.hesk_dbEscape($hesk_settings['db_pfix']).'kb_categories` ORDER BY `parent` ASC, `cat_order` ASC');
	$kb_cat = array();

	while ($cat = hesk_dbFetchAssoc($result))
	{
		$kb_cat[] = $cat;
        if ($cat['id'] == $article['catid'])
        {
        	$this_cat = $cat;
            $this_cat['parent'] = $article['catid'];
        }
	}

	/* Translate main category "Knowledgebase" if needed */
	$kb_cat[0]['name'] = $hesklang['kb_text'];

	require(HESK_PATH . 'inc/treemenu/TreeMenu.php');
	$icon         = 'icon-chevron-right';
	$expandedIcon = 'icon-knowledge';
    $menu		  = new HTML_TreeMenu();

	$thislevel = array('0');
	$nextlevel = array();
	$i = 1;
	$j = 1;

	while (count($kb_cat) > 0)
	{

	    foreach ($kb_cat as $k=>$cat)
	    {

	    	if (in_array($cat['parent'],$thislevel))
	        {

	        	$up = $cat['parent'];
	            $my = $cat['id'];
	            $type = $cat['type'] ? '*' : '';

	            $text_short = $cat['name'].$type.' ('.$cat['articles'].', '.$cat['articles_private'].', '.$cat['articles_draft'].')';

	            if (isset($node[$up]))
	            {
                    $HTML_TreeNode[$my] = new HTML_TreeNode(array('hesk_parent' => $this_cat['parent'], 'text' => 'Text', 'text_short' => $text_short, 'hesk_catid' => $cat['id'], 'hesk_select' => 'option'.$j, 'icon' => $icon, 'expandedIcon' => $expandedIcon, 'expanded' => true));
		            $node[$my] = &$node[$up]->addItem($HTML_TreeNode[$my]);
	            }
	            else
	            {
	                $node[$my] = new HTML_TreeNode(array('hesk_parent' => $this_cat['parent'], 'text' => 'Text',  'text_short' => $text_short, 'hesk_catid' => $cat['id'], 'hesk_select' => 'option'.$j, 'icon' => $icon, 'expandedIcon' => $expandedIcon, 'expanded' => true));
	            }

		        $nextlevel[] = $cat['id'];
	            $j++;
		        unset($kb_cat[$k]);

	        }

	    }

	    $thislevel = $nextlevel;
	    $nextlevel = array();

	    /* Break after 20 recursions to avoid hang-ups in case of any problems */

	    if ($i > 20)
	    {
	    	break;
	    }
	    $i++;
	}

	$menu->addItem($node[1]);

	// Create the presentation class
    $HTML_TreeMenu_Listbox = new HTML_TreeMenu_Listbox($menu);
	$listBox  = & ref_new($HTML_TreeMenu_Listbox);

	/* Print header */
	require_once(HESK_PATH . 'inc/header.inc.php');

	/* Print main manage users page */
	require_once(HESK_PATH . 'inc/show_admin_nav.inc.php');

	/* This will handle error, success and notice messages */
	hesk_handle_messages();

    if ($hesk_settings['kb_wysiwyg'])
    {
    ?>
    <script>
        tinymce.init({
            selector: '#content',
            convert_urls: false,
            branding: false,
            browser_spellcheck: true,
            toolbar: 'undo redo | styleselect fontselect fontsizeselect | bold italic underline | alignleft aligncenter alignright alignjustify | forecolor backcolor | bullist numlist outdent indent | link unlink image codesample code',
            plugins: 'charmap code codesample image link lists table',
        });
    </script>
    <?php
    }
    ?>

    <div class="main__content knowledge article">
        <form action="manage_knowledgebase.php" method="post" name="form1" enctype="multipart/form-data">
            <div class="article__detalies edit">
                <div class="article__detalies_head">
                    <h3><?php echo $hesklang['kb_art_edit']; ?></h3>
                </div>
                <ul class="article__detalies_list">
                    <li>
                        <div class="checkbox-list">
                            <div class="checkbox-custom" style="margin-bottom: 5px">
                                <input type="checkbox" id="edit_sticky" name="sticky" value="Y" <?php if ($article['sticky']) {echo 'checked';} ?>>
                                <label for="edit_sticky"><?php echo $hesklang['sticky']; ?></label>
                            </div>
                            <div class="checkbox-custom" style="margin-bottom: 5px">
                                <input type="checkbox" id="edit_resetviews" name="resetviews" value="Y" <?php if (isset($_SESSION['edit_article']['resetviews']) && $_SESSION['edit_article']['resetviews'] == 'Y') {echo 'checked';} ?>>
                                <label for="edit_resetviews"><?php echo $hesklang['rv']; ?></label>
                            </div>
                            <div class="checkbox-custom" style="margin-bottom: 5px">
                                <input type="checkbox" id="edit_resetvotes" name="resetvotes" value="Y" <?php if (isset($_SESSION['edit_article']['resetvotes']) && $_SESSION['edit_article']['resetvotes'] == 'Y') {echo 'checked';} ?>>
                                <label for="edit_resetvotes"><?php echo $hesklang['rr']; ?></label>
                            </div>
                        </div>
                    </li>
                    <li>
                        <div class="form-group">
                            <label><?php echo $hesklang['kb_type']; ?></label>
                            <div class="radio-list">
                                <div class="radio-custom">
                                    <input type="radio" id="edit_type0" name="type" value="0" <?php if ($article['type']==0) {echo 'checked';} ?>>
                                    <label for="edit_type0"><?php echo $hesklang['kb_published']; ?></label>
                                </div>
                                <div style="margin-left: 24px; margin-bottom: 10px"><?php echo $hesklang['kb_published2']; ?></div>
                                <div class="radio-custom">
                                    <input type="radio" id="edit_type1" name="type" value="1" <?php if ($article['type']==1) {echo 'checked';} ?>>
                                    <label for="edit_type1"><?php echo $hesklang['kb_private']; ?></label>
                                </div>
                                <div style="margin-left: 24px; margin-bottom: 10px"><?php echo $hesklang['kb_private2']; ?></div>
                                <div class="radio-custom">
                                    <input type="radio" id="edit_type2" name="type" value="2" <?php if ($article['type']==2) {echo 'checked';} ?>>
                                    <label for="edit_type2"><?php echo $hesklang['kb_draft']; ?></label>
                                </div>
                                <div style="margin-left: 24px; margin-bottom: 10px"><?php echo $hesklang['kb_draft2']; ?></div>
                            </div>
                        </div>
                    </li>
                    <li>
                        <div class="name category">
                            <label for="edit_catid"><?php echo $hesklang['kb_cat']; ?></label>
                        </div>
                        <div class="descr">
                            <div class="dropdown-select center out-close">
                                <select id="edit_catid" name="catid"><?php $listBox->printMenu()?></select>
                            </div>
                        </div>
                    </li>
                </ul>
                <div class="article__detalies_action">
                    <button type="submit" class="btn btn-full" ripple="ripple"><?php echo $hesklang['kb_save']; ?></button>
                    <?php
                    $modal_id = hesk_generate_delete_modal($hesklang['confirm_deletion'],
                        $hesklang['del_art'],
                        'manage_knowledgebase.php?a=remove_article&amp;id='. $article['id'] .'&amp;token='. hesk_token_echo(0));
                    ?>
                    <a class="btn btn--blue-border" href="javascript:" data-modal="[data-modal-id='<?php echo $modal_id; ?>']">
                        <?php echo $hesklang['delete_article']; ?>
                    </a>
                </div>
            </div>
            <?php
            // Redirect to the correct page
            switch ($from)
            {
                case 'draft':
                    $redirect_action = 'a=list_draft';
                    break;
                case 'private':
                    $redirect_action = 'a=list_private';
                    break;
                default:
                    $redirect_action = 'a=manage_cat&amp;catid='.$catid;
                    break;
            }
            ?>
            <div class="article__body form">
                <div class="article__back">
                    <a href="manage_knowledgebase.php?<?php echo $redirect_action; ?>">
                        <svg class="icon icon-back">
                            <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-back"></use>
                        </svg>
                        <span><?php echo $hesklang['wizard_back']; ?></span>
                    </a>
                </div>
                <div class="article__title">
                    <div class="form-group">
                        <label for="edit_subject"><?php echo $hesklang['kb_subject']; ?></label>
                        <input id="edit_subject" type="text" class="form-control" name="subject" maxlength="255"
                               value="<?php echo $article['subject']; ?>">
                    </div>
                </div>
                <div class="article__description">
                    <?php
                    $displayType = $hesk_settings['kb_wysiwyg'] ? 'none' : 'block';
                    $displayWarn = $article['html'] ? 'block' : 'none';
                    ?>
                    <span id="contentType" style="display:<?php echo $displayType; ?>">
                        <label><input type="radio" name="html" value="0" <?php if (!$article['html']) {echo 'checked="checked"';} ?> onclick="javascript:document.getElementById('kblinks').style.display = 'none'" /> <?php echo $hesklang['kb_dhtml']; ?></label><br />
                        <label><input type="radio" name="html" value="1" <?php if ($article['html']) {echo 'checked="checked"';} ?> onclick="javascript:document.getElementById('kblinks').style.display = 'block'" /> <?php echo $hesklang['kb_ehtml']; ?></label>
                        <span id="kblinks" style="display:<?php echo $displayWarn; ?>"><i><?php echo $hesklang['kb_links']; ?></i></span>
                    </span>
                    <label>
                        <textarea class="form-control" style="height: inherit" name="content" rows="25" cols="70" id="content"><?php echo $article['content']; ?></textarea>
                    </label>
                </div>
                <?php
                if ( ! empty($article['attachments']) || $hesk_settings['attachments']['use'])
                {
                    ?>
                    <div style="margin-top: 16px">
                        <svg class="icon icon-attach">
                            <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-attach"></use>
                        </svg>
                        <?php echo $hesklang['attachments']; ?> (<a href="Javascript:void(0)" onclick="hesk_window('../file_limits.php',250,500);return false;"><?php echo $hesklang['ful']; ?></a>)<br>
                        <?php
                        // Existing attachments
                        if ( ! empty($article['attachments']))
                        {
                            $att=explode(',',substr($article['attachments'], 0, -1));
                            foreach ($att as $myatt)
                            {
                                list($att_id, $att_name) = explode('#', $myatt);

                                $tmp = 'White';
                                $style = 'class="option'.$tmp.'OFF" onmouseover="this.className=\'option'.$tmp.'ON\'" onmouseout="this.className=\'option'.$tmp.'OFF\'"';

                                echo '
                                    <a title="'.$hesklang['dela'].'" href="manage_knowledgebase.php?a=remove_kb_att&amp;id='.$id.'&amp;kb_att='.$att_id.'&amp;token='.hesk_token_echo(0).'" onclick="return hesk_confirmExecute(\''.hesk_makeJsString($hesklang['delatt']).'\');">
                                        <svg class="icon icon-delete">
                                            <use xlink:href="'. HESK_PATH .'img/sprite.svg#icon-delete"></use>
                                        </svg>
                                    </a> ';
                                echo '
                                    <a href="../download_attachment.php?kb_att='.$att_id.'" title="'.$hesklang['dnl'].' '.$att_name.'">
                                        <svg class="icon icon-attach">
                                            <use xlink:href="'. HESK_PATH .'img/sprite.svg#icon-attach"></use>
                                        </svg>
                                    </a> ';
                                echo '<a href="../download_attachment.php?kb_att='.$att_id.'">'.$att_name.'</a><br />';
                            }
                            echo '<br>';
                        }

                        // New attachments
                        if ($hesk_settings['attachments']['use'])
                        {
                            for ($i=1;$i<=$hesk_settings['attachments']['max_number'];$i++)
                            {
                                echo '<input type="file" name="attachment['.$i.']" size="50"><br>';
                            }
                        }
                        ?>
                    </div>
                    <?php
                } // End attachments
                ?>
                <div class="form-group article__keywords">
                    <label for="keywords">
                        <b><?php echo $hesklang['kw']; ?></b>
                        <span><?php echo $hesklang['kw1']; ?></span>
                    </label>
                    <textarea class="form-control" style="height: inherit" name="keywords" rows="3" cols="70" id="keywords"><?php echo $article['keywords']; ?></textarea>
                </div>
            </div>
            <div class="d-flex-center sm-hidden mt2">
                <?php
                $modal_id = hesk_generate_delete_modal($hesklang['confirm_deletion'],
                    $hesklang['del_art'],
                    'manage_knowledgebase.php?a=remove_article&amp;id='. $article['id'] .'&amp;token='. hesk_token_echo(0));
                ?>
                <a class="btn btn--blue-border" data-modal="[data-modal-id='<?php echo $modal_id; ?>']" href="javascript:">
                    <?php echo $hesklang['delete_article']; ?>
                </a>
                <button type="submit" class="btn btn-full ml1" ripple="ripple">
                    <?php echo $hesklang['kb_save']; ?>
                </button>
            </div>
            <input type="hidden" name="a" value="save_article">
            <input type="hidden" name="id" value="<?php echo $id; ?>">
            <input type="hidden" name="old_type" value="<?php echo $article['type']; ?>">
            <input type="hidden" name="old_catid" value="<?php echo $catid; ?>">
            <input type="hidden" name="token" value="<?php hesk_token_echo(); ?>">
            <input type="hidden" name="from" value="<?php echo $from; ?>">
        </form>
        <div class="table-wrap">
            <h3 style="font-size: 16px; font-weight: bold">
                <?php echo $hesklang['revhist']; ?>
            </h3>
            <ul>
                <?php echo $article['history']; ?>
            </ul>
        </div>
    </div>

	<?php
    /* Clean unneeded session variables */
    hesk_cleanSessionVars('edit_article');

    require_once(HESK_PATH . 'inc/footer.inc.php');
    exit();
} // END edit_article()


function manage_category() {
	global $hesk_settings, $hesklang;

    $catid = intval( hesk_GET('catid') ) or hesk_error($hesklang['kb_cat_inv']);

	$result = hesk_dbQuery('SELECT * FROM `'.hesk_dbEscape($hesk_settings['db_pfix']).'kb_categories` ORDER BY `parent` ASC, `cat_order` ASC');
	$kb_cat = array();

	while ($cat = hesk_dbFetchAssoc($result))
	{
		$kb_cat[] = $cat;
        if ($cat['id'] == $catid)
        {
        	$this_cat = $cat;
        }
	}

    if (isset($_SESSION['manage_cat']))
    {
    	$_SESSION['manage_cat'] = hesk_stripArray($_SESSION['manage_cat']);
		$this_cat['type'] = $_SESSION['manage_cat']['type'];
        $this_cat['parent'] = $_SESSION['manage_cat']['parent'];
        $this_cat['name'] = $_SESSION['manage_cat']['title'];
    }

	/* Translate main category "Knowledgebase" if needed */
	$kb_cat[0]['name'] = $hesklang['kb_text'];

	require(HESK_PATH . 'inc/treemenu/TreeMenu.php');
	$icon         = 'icon-chevron-right';
	$expandedIcon = 'icon-knowledge';
    $menu		  = new HTML_TreeMenu();

	$thislevel = array('0');
	$nextlevel = array();
	$i = 1;
	$j = 1;

	while (count($kb_cat) > 0)
	{

	    foreach ($kb_cat as $k=>$cat)
	    {

            if ($cat['id'] == $catid)
            {
                continue;
            }

	    	if (in_array($cat['parent'],$thislevel))
	        {

	        	$up = $cat['parent'];
	            $my = $cat['id'];
	            $type = $cat['type'] ? '*' : '';

				$text_short = $cat['name'].$type.' ('.$cat['articles'].', '.$cat['articles_private'].', '.$cat['articles_draft'].')';

	            if (isset($node[$up]))
	            {
                    $HTML_TreeNode[$my] = new HTML_TreeNode(array('hesk_parent' => $this_cat['parent'], 'text' => 'Text', 'text_short' => $text_short, 'hesk_catid' => $cat['id'], 'hesk_select' => 'option'.$j, 'icon' => $icon, 'expandedIcon' => $expandedIcon, 'expanded' => true));
		            $node[$my] = &$node[$up]->addItem($HTML_TreeNode[$my]);
	            }
	            else
	            {
	                $node[$my] = new HTML_TreeNode(array('hesk_parent' => $this_cat['parent'], 'text' => 'Text',  'text_short' => $text_short, 'hesk_catid' => $cat['id'], 'hesk_select' => 'option'.$j, 'icon' => $icon, 'expandedIcon' => $expandedIcon, 'expanded' => true));
	            }

		        $nextlevel[] = $cat['id'];
	            $j++;
		        unset($kb_cat[$k]);

	        }

	    }

	    $thislevel = $nextlevel;
	    $nextlevel = array();

	    /* Break after 20 recursions to avoid hang-ups in case of any problems */

	    if ($i > 20)
	    {
	    	break;
	    }
	    $i++;
	}

	$menu->addItem($node[1]);

	// Create the presentation class
    $HTML_TreeMenu_Listbox = new HTML_TreeMenu_Listbox($menu);
	$listBox  = & ref_new($HTML_TreeMenu_Listbox);

	/* Print header */
	require_once(HESK_PATH . 'inc/header.inc.php');

	/* Print main manage users page */
	require_once(HESK_PATH . 'inc/show_admin_nav.inc.php');

    hesk_handle_messages();
	echo '<div class="main__content knowledge category">';
    if ( ! isset($_SESSION['hide']['article_list']))
    {
    ?>
        <div class="category__list visible">
            <div class="category__list_head">
                <h3><?php echo $this_cat['name']; ?></h3>
            </div>
            <div class="category__list_table overflow-x-scroll" style="display: block">
                <div style="float: right; margin-bottom: 10px;">
                    <a class="btn btn--blue-border" href="manage_knowledgebase.php?a=add_article&amp;catid=<?php echo $catid; ?>">
                        <?php echo $hesklang['kb_i_art2']; ?>
                    </a>
                </div>
                <table>
                    <tbody>
                    <?php
                    $result = hesk_dbQuery("SELECT * FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_articles` WHERE `catid`='{$catid}' ORDER BY `sticky` DESC, `art_order` ASC");
                    $num    = hesk_dbNumRows($result);

                    if ($num == 0)
                    {
                        echo '
                        <tr>
                            <td colspan="4" style="padding-left: 10px">'.$hesklang['kb_no_art'].'</td>
                        </tr>
                        ';
                    }
                    else
                    {
                        /* Get number of sticky articles */
                        $res2 = hesk_dbQuery("SELECT COUNT(*) FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_articles` WHERE `catid`='{$catid}' AND `sticky` = '1' ");
                        $num_sticky = hesk_dbResult($res2);

                        $num_nosticky = $num - $num_sticky;

                        $i=1;
                        $j=1;
                        $k=1;
                        $previous_sticky=1;
                        $num = $num_sticky;

                        while ($article=hesk_dbFetchAssoc($result))
                        {

                            if ($previous_sticky != $article['sticky'])
                            {
                                $k = 1;
                                $num = $num_nosticky;
                                $previous_sticky = $article['sticky'];
                            }

                            $table_row = 'class="';
                            if (isset($_SESSION['artord']) && $article['id'] == $_SESSION['artord'])
                            {
                                $table_row .= 'ticket-new ';
                                unset($_SESSION['artord']);
                            }

                            if ($article['sticky']) {
                                $table_row .= 'sticky';
                            }
                            $table_row .= '"';

                            $i     = $i ? 0 : 1;

                            // Status
                            switch ($article['type'])
                            {
                                case '1':
                                    $type = '<div style="margin-bottom: 3px">' . $hesklang['kb_private'] . '</div>';
                                    break;
                                case '2':
                                    $type = '<div style="margin-bottom: 3px">' . $hesklang['kb_draft'] . '</div>';
                                    break;
                                default:
                                    $type = '<div style="margin-bottom: 3px">' . $hesklang['kb_published'] . '</div>';
                            }


                            if ($hesk_settings['kb_rating'] && $article['type'] != '2')
                            {
                                $type .= hesk3_get_rating($article['rating'], $article['votes']);
                            }

                            ?>
                            <tr <?php echo $table_row; ?>>
                                <td class="title">
                                    <a href="knowledgebase_private.php?article=<?php echo $article['id']; ?>&amp;back=1<?php if ($article['type'] == 2) {echo '&amp;draft=1';} ?>">
                                        <?php echo $article['subject']; ?>
                                    </a>
                                </td>
                                <td class="view">
                                    <svg class="icon icon-eye-close">
                                        <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-eye-close"></use>
                                    </svg>
                                    <?php echo $article['views']; ?>
                                </td>
                                <td class="status">
                                    <?php echo $type; ?>
                                </td>
                                <td class="actions">
                                    <div class="actions--buttons">
                                        <?php
                                        if ($num > 1)
                                        {
                                            if ($k == 1)
                                            {
                                                ?>
                                                <a href="#" style="visibility: hidden">
                                                    <svg class="icon icon-chevron-up">
                                                        <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-chevron-down"></use>
                                                    </svg>
                                                </a>
                                                <a href="manage_knowledgebase.php?a=order_article&amp;id=<?php echo $article['id']; ?>&amp;catid=<?php echo $catid; ?>&amp;move=15&amp;token=<?php hesk_token_echo(); ?>">
                                                    <svg class="icon icon-chevron-down">
                                                        <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-chevron-down"></use>
                                                    </svg>
                                                </a>
                                                <?php
                                            }
                                            elseif ($k == $num)
                                            {
                                                ?>
                                                <a href="manage_knowledgebase.php?a=order_article&amp;id=<?php echo $article['id']; ?>&amp;catid=<?php echo $catid; ?>&amp;move=-15&amp;token=<?php hesk_token_echo(); ?>">
                                                    <svg class="icon icon-chevron-up">
                                                        <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-chevron-down"></use>
                                                    </svg>
                                                </a>
                                                <a href="#" style="visibility: hidden">
                                                    <svg class="icon icon-chevron-down">
                                                        <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-chevron-down"></use>
                                                    </svg>
                                                </a>
                                                <?php
                                            }
                                            else
                                            {
                                                ?>
                                                <a href="manage_knowledgebase.php?a=order_article&amp;id=<?php echo $article['id']; ?>&amp;catid=<?php echo $catid; ?>&amp;move=-15&amp;token=<?php hesk_token_echo(); ?>">
                                                    <svg class="icon icon-chevron-up">
                                                        <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-chevron-down"></use>
                                                    </svg>
                                                </a>
                                                <a href="manage_knowledgebase.php?a=order_article&amp;id=<?php echo $article['id']; ?>&amp;catid=<?php echo $catid; ?>&amp;move=15&amp;token=<?php hesk_token_echo(); ?>">
                                                    <svg class="icon icon-chevron-down">
                                                        <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-chevron-down"></use>
                                                    </svg>
                                                </a>
                                                <?php
                                            }
                                        }
                                        elseif ( $num_sticky > 1 || $num_nosticky > 1 )
                                        {
                                            echo '
                                            <a href="#" style="visibility: hidden">
                                                <svg class="icon icon-chevron-up">
                                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-chevron-down"></use>
                                                </svg>
                                            </a>
                                            <a href="#" style="visibility: hidden">
                                                <svg class="icon icon-chevron-down">
                                                    <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-chevron-down"></use>
                                                </svg>
                                            </a>';
                                        }
                                        ?>
                                        <a href="manage_knowledgebase.php?a=sticky&amp;s=<?php echo $article['sticky'] ? 0 : 1 ?>&amp;id=<?php echo $article['id']; ?>&amp;catid=<?php echo $catid; ?>&amp;token=<?php hesk_token_echo(); ?>"
                                           title="<?php echo $article['sticky'] ? $hesklang['stickyoff'] : $hesklang['stickyon']; ?>">
                                            <svg class="icon icon-pin" <?php echo $article['sticky'] ? ' style="fill: #38bc7d; transform: rotate(50deg);"' : ''; ?>>
                                                <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-pin"></use>
                                            </svg>
                                        </a>
                                        <a href="manage_knowledgebase.php?a=edit_article&amp;id=<?php echo $article['id']; ?>"
                                           title="<?php echo $hesklang['edit']; ?>">
                                            <svg class="icon icon-edit-ticket">
                                                <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-edit-ticket"></use>
                                            </svg>
                                        </a>
                                        <?php
                                        $modal_id = hesk_generate_delete_modal($hesklang['confirm_deletion'],
                                            $hesklang['del_art'],
                                            'manage_knowledgebase.php?a=remove_article&amp;id='. $article['id'] .'&amp;token='. hesk_token_echo(0));
                                        ?>
                                        <a href="javascript:"
                                           data-modal="[data-modal-id='<?php echo $modal_id; ?>']"
                                           title="<?php echo $hesklang['delete']; ?>">
                                            <svg class="icon icon-delete">
                                                <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-delete"></use>
                                            </svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php
                            $j++;
                            $k++;
                        } // End while
                    } ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    } // END if hide article list

        /* Manage Category (except the default one) */
		if ($catid != 1)
		{
        ?>
            <div class="table-wrap">
                <h3 style="font-size: 16px;font-weight: bold;padding-bottom:5px;"><?php echo $hesklang['catset']; ?></h3>
                <div style="text-align:right; margin-bottom: 10px">
                    <?php echo '<a class="btn btn--blue-border" href="manage_knowledgebase.php?a=add_category&amp;parent='.$catid.'">'.$hesklang['kb_i_cat2'].'</a>'; ?>
                </div>
                <form action="manage_knowledgebase.php" method="post" name="form1"
                      class="form"
                      onsubmit="Javascript:return hesk_deleteIfSelected('dodelete','<?php echo hesk_makeJsString($hesklang['kb_delcat']); ?>')">
                    <div class="form-group">
                        <label for="edit_cat_title"><?php echo $hesklang['kb_cat_title']; ?></label>
                        <input id="edit_cat_title" class="form-control" type="text" name="title" maxlength="255" value="<?php echo $this_cat['name']; ?>">
                    </div>
                    <div class="form-group">
                        <label for="edit_cat_parent"><?php echo $hesklang['kb_cat_parent']; ?></label>
                        <div class="dropdown-select center out-close">
                            <select id="edit_cat_parent" name="parent"><?php $listBox->printMenu();  ?></select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label><?php echo $hesklang['kb_type']; ?></label>
                        <div class="radio-list">
                            <div class="radio-custom">
                                <input id="edit_type0" type="radio" name="type" value="0" <?php if (!$this_cat['type']) {echo 'checked';} ?> >
                                <label for="edit_type0"><?php echo $hesklang['kb_published']; ?></label>
                            </div>
                            <div style="margin-left: 24px; margin-bottom: 10px">
                                <?php echo $hesklang['kb_cat_published']; ?>
                            </div>
                            <div class="radio-custom">
                                <input id="edit_type1" type="radio" name="type" value="1" <?php if ($this_cat['type']) {echo 'checked';} ?>>
                                <label for="edit_type1"><?php echo $hesklang['kb_private']; ?></label>
                            </div>
                            <div style="margin-left: 24px; margin-bottom: 10px">
                                <?php echo $hesklang['kb_cat_private']; ?>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label><?php echo $hesklang['opt']; ?></label>
                        <div class="checkbox-list">
                            <div class="checkbox-custom">
                                <input type="checkbox" name="dodelete" id="dodelete" value="Y" onclick="Javascript:hesk_toggleLayerDisplay('deleteoptions')">
                                <label for="dodelete"><?php echo $hesklang['delcat']; ?></label>
                            </div>
                        </div>
                    </div>
                    <div id="deleteoptions" style="display: none;">
                        <div class="form-group">
                            <div class="radio-list">
                                <div class="radio-custom">
                                    <input id="edit_movearticlesY" type="radio" name="movearticles" value="Y" checked>
                                    <label for="edit_movearticlesY"><?php echo $hesklang['move1']; ?></label>
                                </div>
                                <div class="radio-custom">
                                    <input id="edit_movearticlesN" type="radio" name="movearticles" value="N" />
                                    <label for="edit_movearticlesN"><?php echo $hesklang['move2']; ?></label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="a" value="edit_category">
                    <input type="hidden" name="token" value="<?php hesk_token_echo(); ?>">
                    <input type="hidden" name="catid" value="<?php echo $catid; ?>">
                    <button type="submit" class="btn btn-full"><?php echo $hesklang['save_changes']; ?></button>
                </form>
            </div>
	<?php
    } // END if $catid != 1

    echo '</div>';

    echo '&nbsp;<br />&nbsp;';

	/* Clean unneeded session variables */
	hesk_cleanSessionVars(array('hide','manage_cat','edit_article'));

    require_once(HESK_PATH . 'inc/footer.inc.php');
    exit();
} // END manage_category()


function new_category() {
	global $hesk_settings, $hesklang;

	/* A security check */
	hesk_token_check('POST');

	$_SESSION['hide'] = array(
		'treemenu' => 1,
		'new_article' => 1,
		//'new_category' => 1,
	);

    $parent = intval( hesk_POST('parent', 1) );
    $type   = empty($_POST['type']) ? 0 : 1;

    $_SESSION['KB_CATEGORY'] = $parent;
    $_SERVER['PHP_SELF'] = 'manage_knowledgebase.php';

    /* Check that title is valid */
	$title  = hesk_input( hesk_POST('title') );
	if (!strlen($title))
	{
		$_SESSION['new_category'] = array(
			'type' => $type,
		);

		hesk_process_messages($hesklang['kb_cat_e_title'],$_SERVER['PHP_SELF']);
	}

	/* Get the latest reply_order */
	$res = hesk_dbQuery('SELECT `cat_order` FROM `'.hesk_dbEscape($hesk_settings['db_pfix']).'kb_categories` ORDER BY `cat_order` DESC LIMIT 1');
	$row = hesk_dbFetchRow($res);
    $my_order = isset($row[0]) ? intval($row[0]) + 10 : 10;

	$result = hesk_dbQuery("INSERT INTO `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_categories` (`name`,`parent`,`cat_order`,`type`) VALUES ('".hesk_dbEscape($title)."','".intval($parent)."','".intval($my_order)."','".intval($type)."')");

    $_SESSION['newcat'] = hesk_dbInsertID();

	$_SESSION['hide'] = array(
		'treemenu' => 1,
		'new_article' => 1,
		//'new_category' => 1,
        'cat_treemenu' => 1,
	);

    hesk_process_messages($hesklang['kb_cat_added2'],$_SERVER['PHP_SELF'],'SUCCESS');
} // End new_category()


function new_article()
{
	global $hesk_settings, $hesklang, $listBox;
    global $hesk_error_buffer;

	/* A security check */
	# hesk_token_check('POST');

	$_SESSION['hide'] = array(
		'treemenu' => 1,
		//'new_article' => 1,
		'new_category' => 1,
	);

    $hesk_error_buffer = array();

	$catid = intval( hesk_POST('catid', 1) );
    $type  = empty($_POST['type']) ? 0 : (hesk_POST('type') == 2 ? 2 : 1);
    $html  = $hesk_settings['kb_wysiwyg'] ? 1 : (empty($_POST['html']) ? 0 : 1);
    $now   = hesk_date();

	// Prevent submitting duplicate articles by reloading manage_knowledgebase.php page
	if (isset($_SESSION['article_submitted']))
	{
		header('Location:manage_knowledgebase.php?a=manage_cat&catid=' . $catid);
	    exit();
	}

    $_SESSION['KB_CATEGORY'] = $catid;

    $subject = hesk_input( hesk_POST('subject') ) or $hesk_error_buffer[] = $hesklang['kb_e_subj'];

    if ($html)
    {
	    if (empty($_POST['content']))
	    {
        	$hesk_error_buffer[] = $hesklang['kb_e_cont'];
	    }

        $content = hesk_getHTML( hesk_POST('content') );

        // Clean the HTML code
        require(HESK_PATH . 'inc/htmlpurifier/HeskHTMLPurifier.php');
        $purifier = new HeskHTMLPurifier($hesk_settings['cache_dir']);
        $content = $purifier->heskPurify($content);
    }
	else
    {
    	$content = hesk_input( hesk_POST('content') ) or $hesk_error_buffer[] = $hesklang['kb_e_cont'];
	    $content = nl2br($content);
	    $content = hesk_makeURL($content);
    }

    $sticky = isset($_POST['sticky']) ? 1 : 0;

    $keywords = hesk_input( hesk_POST('keywords') );

    /* Article attachments */
	define('KB',1);
	require_once(HESK_PATH . 'inc/posting_functions.inc.php');
    $attachments = array();
	$myattachments='';

	if ($hesk_settings['attachments']['use'])
	{
		require_once(HESK_PATH . 'inc/attachments.inc.php');

		for ($i=1; $i<=$hesk_settings['attachments']['max_number']; $i++)
		{
			$att = hesk_uploadFile($i);
			if ( ! empty($att))
			{
				$attachments[$i] = $att;
			}
		}
	}

    /* Any errors? */
    if (count($hesk_error_buffer))
    {
		// Remove any successfully uploaded attachments
		if ($hesk_settings['attachments']['use'])
		{
			hesk_removeAttachments($attachments);
		}

		$_SESSION['new_article'] = array(
		'type' => $type,
		'html' => $html,
		'subject' => $subject,
		'content' => hesk_input( hesk_POST('content') ),
		'keywords' => $keywords,
        'sticky' => $sticky,
		);

		$tmp = '';
		foreach ($hesk_error_buffer as $error)
		{
			$tmp .= "<li>$error</li>\n";
		}
		$hesk_error_buffer = $tmp;

    	$hesk_error_buffer = $hesklang['rfm'].'<br /><br /><ul>'.$hesk_error_buffer.'</ul>';
    	hesk_process_messages($hesk_error_buffer,'manage_knowledgebase.php');
    }

    $revision = sprintf($hesklang['revision1'],$now,$_SESSION['name'].' ('.$_SESSION['user'].')');

	/* Add to database */
	if ( ! empty($attachments))
	{
	    foreach ($attachments as $myatt)
	    {
	        hesk_dbQuery("INSERT INTO `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_attachments` (`saved_name`,`real_name`,`size`) VALUES ('".hesk_dbEscape($myatt['saved_name'])."','".hesk_dbEscape($myatt['real_name'])."','".intval($myatt['size'])."')");
	        $myattachments .= hesk_dbInsertID() . '#' . $myatt['real_name'] .',';
	    }
	}

	/* Get the latest reply_order */
	$res = hesk_dbQuery("SELECT `art_order` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_articles` WHERE `catid`='".intval($catid)."' AND `sticky` = '" . intval($sticky) . "' ORDER BY `art_order` DESC LIMIT 1");
	$row = hesk_dbFetchRow($res);
    $my_order = isset($row[0]) ? intval($row[0]) + 10 : 10;

    /* Insert article into database */
	hesk_dbQuery("INSERT INTO `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_articles` (`catid`,`dt`,`author`,`subject`,`content`,`keywords`,`type`,`html`,`sticky`,`art_order`,`history`,`attachments`) VALUES (
    '".intval($catid)."',
    NOW(),
    '".intval($_SESSION['id'])."',
    '".hesk_dbEscape($subject)."',
    '".hesk_dbEscape($content)."',
    '".hesk_dbEscape($keywords)."',
    '".intval($type)."',
    '".intval($html)."',
    '".intval($sticky)."',
    '".intval($my_order)."',
    '".hesk_dbEscape($revision)."',
    '".hesk_dbEscape($myattachments)."'
    )");

    $_SESSION['artord'] = hesk_dbInsertID();

	// Update category article count
    if ($type == 0)
    {
	    hesk_dbQuery("UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_categories` SET `articles`=`articles`+1 WHERE `id`='".intval($catid)."'");
	}
    else if ($type == 1)
    {
	    hesk_dbQuery("UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_categories` SET `articles_private`=`articles_private`+1 WHERE `id`='".intval($catid)."'");
	}
    else
    {
	    hesk_dbQuery("UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_categories` SET `articles_draft`=`articles_draft`+1 WHERE `id`='".intval($catid)."'");
	}

    // Clear KB cache
    hesk_purge_cache('kb');

    unset($_SESSION['hide']);

	$_SESSION['article_submitted']=1;

    hesk_process_messages($hesklang['your_kb_added'],'NOREDIRECT','SUCCESS');
    $_GET['catid'] = $catid;
    manage_category();
} // End new_article()


function remove_article()
{
	global $hesk_settings, $hesklang;

	/* A security check */
	hesk_token_check();

	$id = intval( hesk_GET('id') ) or hesk_error($hesklang['kb_art_id']);

    /* Get article details */
	$result = hesk_dbQuery("SELECT `catid`, `type`, `attachments` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_articles` WHERE `id`='".intval($id)."' LIMIT 1");

    if (hesk_dbNumRows($result) != 1)
    {
    	hesk_error($hesklang['kb_art_id']);
    }

    $article = hesk_dbFetchAssoc($result);
	$catid = intval($article['catid']);
    $from = hesk_GET('from');

    $result = hesk_dbQuery("DELETE FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_articles` WHERE `id`='".intval($id)."'");

    // Remove any attachments
    delete_kb_attachments($article['attachments']);

    // Update category article count
    if ($article['type'] == 0)
    {
	    hesk_dbQuery("UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_categories` SET `articles`=`articles`-1 WHERE `id`='{$catid}'");
	}
    else if ($article['type'] == 1)
    {
	    hesk_dbQuery("UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_categories` SET `articles_private`=`articles_private`-1 WHERE `id`='{$catid}'");
	}
    else
    {
	    hesk_dbQuery("UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_categories` SET `articles_draft`=`articles_draft`-1 WHERE `id`='{$catid}'");
	}

    // Clear KB cache
    hesk_purge_cache('kb');

    // Redirect to the correct page
    switch ($from)
    {
        case 'draft':
            $redirect_action = 'a=list_draft';
            break;
        case 'private':
            $redirect_action = 'a=list_private';
            break;
        default:
            $redirect_action = 'a=manage_cat&catid='.$catid;
            break;
    }

	hesk_process_messages($hesklang['your_kb_deleted'],'./manage_knowledgebase.php?'.$redirect_action,'SUCCESS');
} // End remove_article()


function order_category()
{
	global $hesk_settings, $hesklang;

	/* A security check */
	hesk_token_check();

	$catid = intval( hesk_GET('catid') ) or hesk_error($hesklang['kb_cat_inv']);
	$move  = intval( hesk_GET('move') );

    $_SESSION['newcat'] = $catid;

	$result = hesk_dbQuery("UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_categories` SET `cat_order`=`cat_order`+".intval($move)." WHERE `id`='".intval($catid)."'");
	if (hesk_dbAffectedRows() != 1)
    {
    	hesk_error($hesklang['kb_cat_inv']);
    }

    update_category_order();

	header('Location: manage_knowledgebase.php');
	exit();
} // End order_category()


function order_article()
{
	global $hesk_settings, $hesklang;

	/* A security check */
	hesk_token_check();

	$id    = intval( hesk_GET('id') ) or hesk_error($hesklang['kb_art_id']);
    $catid = intval( hesk_GET('catid') ) or hesk_error($hesklang['kb_cat_inv']);
	$move  = intval( hesk_GET('move') );

    $_SESSION['artord'] = $id;

	$result = hesk_dbQuery("UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_articles` SET `art_order`=`art_order`+".intval($move)." WHERE `id`='".intval($id)."'");
	if (hesk_dbAffectedRows() != 1)
    {
    	hesk_error($hesklang['kb_art_id']);
    }

    /* Update article order */
    update_article_order($catid);

	header('Location: manage_knowledgebase.php?a=manage_cat&catid='.$catid);
	exit();
} // End order_article()


function show_treeMenu() {
	global $hesk_settings, $hesklang, $treeMenu;
	?>
	<script src="<?php echo HESK_PATH; ?>inc/treemenu/TreeMenu_v25.js" language="JavaScript" type="text/javascript"></script>

	<h4 style="margin-top: 10px;padding-bottom:5px;font-size:1rem"><?php echo $hesklang['kbstruct']; ?></h4>
    <?php
    $treeMenu->printMenu();
    ?>
    <div style="margin-top: 15px">
        <svg style="fill: #9c9c9c" class="icon icon-add">
            <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-add"></use>
        </svg>
        <?php echo $hesklang['kb_p_art2']; ?>
    </div>
    <div>
        <svg style="fill: #9c9c9c" class="icon icon-settings">
            <use xlink:href="<?php echo HESK_PATH; ?>img/sprite.svg#icon-settings"></use>
        </svg>
        <?php echo $hesklang['kb_p_man2']; ?>
    </div>
    <div>
        (<span class="kb_published">1</span>, <span class="kb_private">2</span>, <span class="kb_draft">3</span>) = <?php echo $hesklang['xyz']; ?>
    </div>
    <?php
}


function show_subnav($hide='',$catid=1)
{
	global $hesk_settings, $hesklang;

	// If a category is selected, use it as default for articles and parents
	if (isset($_SESSION['KB_CATEGORY']))
	{
		$catid = intval($_SESSION['KB_CATEGORY']);
	}

    $link['view'] = '
        <a class="link not-underlined" href="knowledgebase_private.php">
            <svg class="icon icon-search">
                <use xlink:href="'.HESK_PATH.'img/sprite.svg#icon-search"></use>
            </svg>        
        </a> 
        <a class="link" href="knowledgebase_private.php">'.$hesklang['gopr'].'</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
    $link['newa'] = '
        <a class="link not-underlined" href="manage_knowledgebase.php?a=add_article&amp;catid='.$catid.'">
            <svg style="fill: #9c9c9c" class="icon icon-add">
              <use xlink:href="'.HESK_PATH.'img/sprite.svg#icon-add"></use>
            </svg>
        </a>
        <a class="link" href="manage_knowledgebase.php?a=add_article&amp;catid='.$catid.'">'.$hesklang['kb_i_art'].'</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
    $link['newc'] = '
        <a class="link not-underlined" href="manage_knowledgebase.php?a=add_category&amp;parent='.$catid.'">
            <svg style="fill: #9c9c9c" class="icon icon-categories">
                <use xlink:href="'.HESK_PATH.'img/sprite.svg#icon-categories"></use>
            </svg>
        </a> 
        <a class="link" href="manage_knowledgebase.php?a=add_category&amp;parent='.$catid.'">'.$hesklang['kb_i_cat'].'</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
    $link['fbid'] = '
        <svg style="fill: #9c9c9c" class="icon icon-edit">
            <use xlink:href="'.HESK_PATH.'img/sprite.svg#icon-edit"></use>        
        </svg>
        <form style="display: inline" class="form" method="get" action="manage_knowledgebase.php">
        <input type="hidden" name="a" value="edit_article">
        '. $hesklang['aid'] .': <input type="text" name="id" class="form-control" style="width: 75px; height: inherit"> <button type="submit" class="btn btn--blue-border" style="height: 26px;">'. $hesklang['edit'] .'</button>
        </form>
    ';

    if ($hide && isset($link[$hide]))
    {
    	$link[$hide] = preg_replace('#<a([^<]*)>#', '', $link[$hide]);
        $link[$hide] = str_replace('</a>','',$link[$hide]);
    }

    echo $link['view'];
    echo $link['newa'];
    echo $link['newc'];
    echo $link['fbid'];

    /* This will handle error, success and notice messages */
	hesk_handle_messages();

    return $catid;

} // End show_subnav()


function toggle_sticky()
{
	global $hesk_settings, $hesklang;

	/* A security check */
	hesk_token_check();

	$id    = intval( hesk_GET('id') ) or hesk_error($hesklang['kb_art_id']);
    $catid = intval( hesk_GET('catid') ) or hesk_error($hesklang['kb_cat_inv']);
    $sticky = empty($_GET['s']) ? 0 : 1;

    $_SESSION['artord'] = $id;

	/* Update article "sticky" status */
	hesk_dbQuery("UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_articles` SET `sticky`='" . intval($sticky) . " ' WHERE `id`='" . intval($id) . "'");

    /* Update article order */
    update_article_order($catid);

    $tmp = $sticky ? $hesklang['ason'] : $hesklang['asoff'];
	hesk_process_messages($tmp, './manage_knowledgebase.php?a=manage_cat&catid='.$catid,'SUCCESS');
} // END toggle_sticky()


function update_article_order($catid)
{
	global $hesk_settings, $hesklang;

	/* Get list of current articles ordered by sticky and article order */
	$res = hesk_dbQuery("SELECT `id`, `sticky` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_articles` WHERE `catid`='".intval($catid)."' ORDER BY `sticky` DESC, `art_order` ASC");

	$i = 10;
	$previous_sticky = 1;

	while ( $article = hesk_dbFetchAssoc($res) )
	{

		/* Different count for sticky and non-sticky articles */
		if ($previous_sticky != $article['sticky'])
		{
			$i = 10;
			$previous_sticky = $article['sticky'];
		}

	    hesk_dbQuery("UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_articles` SET `art_order`=".intval($i)." WHERE `id`='".intval($article['id'])."'");
	    $i += 10;
	}

	return true;
} // END update_article_order()


function update_category_order()
{
	global $hesk_settings, $hesklang;

	/* Get list of current articles ordered by sticky and article order */
	$res = hesk_dbQuery('SELECT `id`, `parent` FROM `'.hesk_dbEscape($hesk_settings['db_pfix']).'kb_categories` ORDER BY `parent` ASC, `cat_order` ASC');

	$i = 10;

	while ( $category = hesk_dbFetchAssoc($res) )
	{

	    hesk_dbQuery("UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_categories` SET `cat_order`=".intval($i)." WHERE `id`='".intval($category['id'])."'");
	    $i += 10;
	}

	return true;
} // END update_category_order()


function update_count($show_success=0)
{
	global $hesk_settings, $hesklang;

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
    }

	// Show a success message?
	if ($show_success)
	{
		hesk_process_messages($hesklang['acv'], 'NOREDIRECT','SUCCESS');
	}

	return true;
} // END update_count()


function delete_category_recursive($catid)
{
	global $hesk_settings, $hesklang;

    $catid = intval($catid);

    // Don't allow infinite loops... just in case
    $hesk_settings['recursive_loop'] = isset($hesk_settings['recursive_loop']) ? $hesk_settings['recursive_loop'] + 1 : 1;
    if ($hesk_settings['recursive_loop'] > 20)
    {
    	return false;
    }

	// Make sure any attachments are deleted
	$result = hesk_dbQuery("SELECT `attachments` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_articles` WHERE `catid`='{$catid}'");
    while ($article = hesk_dbFetchAssoc($result))
    {
		delete_kb_attachments($article['attachments']);
    }

   	// Remove articles from database
	hesk_dbQuery("DELETE FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_articles` WHERE `catid`='{$catid}'");

	// Delete all sub-categories
	$result = hesk_dbQuery("SELECT `id` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_categories` WHERE `parent`='{$catid}'");
    while ($cat = hesk_dbFetchAssoc($result))
    {
		delete_category_recursive($cat['id']);
    }

    return true;

} // END delete_category_recursive()


function delete_kb_attachments($attachments)
{
	global $hesk_settings, $hesklang;

	// If nothing to delete just return
    if (empty($attachments))
    {
    	return true;
    }

	// Do the delete
	$att = explode(',',substr($attachments, 0, -1));
	foreach ($att as $myatt)
	{
		list($att_id, $att_name) = explode('#', $myatt);

		// Get attachment saved name
		$result = hesk_dbQuery("SELECT `saved_name` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_attachments` WHERE `att_id`='".intval($att_id)."' LIMIT 1");

		if (hesk_dbNumRows($result) == 1)
		{
			$file = hesk_dbFetchAssoc($result);
			hesk_unlink(HESK_PATH.$hesk_settings['attach_dir'].'/'.$file['saved_name']);
		}

		$result = hesk_dbQuery("DELETE FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_attachments` WHERE `att_id`='".intval($att_id)."'");
	}

    return true;

} // delete_kb_attachments()


function hesk_stray_article($id)
{
	global $hesk_settings, $hesklang, $article;

	// Set article to category ID 1
	$article['catid'] = 1;

	// Update database
	hesk_dbQuery("UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_articles` SET `catid`=1 WHERE `id`='".intval($id)."'");

	// Update count of articles in categories
	update_count();

	// Return new category ID
	return 1;

} // END hesk_stray_article()

?>
